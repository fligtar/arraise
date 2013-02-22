<?php
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is arraise.
 *
 * The Initial Developer of the Original Code is
 * Justin Scott <fligtar@gmail.com>.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

//Errors
define('ERROR_INSUFFICIENT_DATA', 'Insufficient data');
define('ERROR_DATASET_DNE', 'Dataset `%s` does not exist');
define('ERROR_DATASET_DE', 'Dataset `%s` already exists');
define('ERROR_TABLE_DNE', 'Table `%s` does not exist');
define('ERROR_TABLE_DE', 'Table `%s` already exists');
define('ERROR_DUPLICATE_KEY', 'Duplicate primary key `%s` in table `%s`');
define('ERROR_RECORD_DNE', 'Record with primary key `%s` does not exist in table `%s`');
define('ERROR_INVALID_OPERATOR', 'Invalid operator: %s');
define('ERROR_INVALID_ORDER', '`%s` is not a valid ORDER BY format');
define('ERROR_INVALID_QUERY', 'Invalid query: `%s`');
define('ERROR_VARIABLE_DNE', 'Variable `%s` does not exist');
define('ERROR_VARIABLE_NOTBOOL', 'Variable `%s` is not boolean; cannot be set this way');
define('ERROR_VARIABLE_VALUE_NOTBOOL', 'Variable `%s` cannot be set to `%s`; must be boolean value');


class arraise {
    var $name = 'data';
    var $dataset = array();
    
    //Query info
    var $lastInsertedId = 0;
    var $lastAffectedRows = 0;
    var $lastError = '';
    var $lastCall = array();
    
    //Persistent data
    var $autoLoad = true;
    var $autoSave = true;
    var $savePath = '';
    
    var $autoCreate = false;
    
    //Debugging
    var $logging = false;
    var $log = array();
    var $outputErrors = true;
    
    //Customization
    var $errorHTML = '';
    var $configFile = 'config.php';
    
   /**
    * Constructor that initializes the dataset
    * @param string $name name of this dataset
    * @param array $config array of config settings
    * @param array $dataset inital dataset
    */
    function arraise($name = '', $config = array(), $dataset = array()) {
        $arraise_loc = dirname(__FILE__);
        $this->savePath = $arraise_loc.'/data';
        
        //Set name
        if (!empty($name)) {
            $this->name = $name;
        }
        
        //Set config values
        if (!empty($config)) {
            $this->set($config, false);
        }
        
        //Store dataset if set
        if (!empty($dataset)) {
            $this->dataset = $dataset;
        }
        
        //Try to load existing dataset
        $this->load($this->name);
        
        //Use config file if found
        if (file_exists($this->configFile)) {
            include_once($this->configFile);
            $this->set($globalConfig, false);
        }
        
        //Include display class if found
        if (file_exists($arraise_loc.'/arraise_display.php')) {
            include_once($arraise_loc.'/arraise_display.php');
            $this->display = new arraise_display($this);
        }
    }

   /**
    * Performs an SQL-like syntax query on the dataset
    * @param string $query the SQL query
    * @return mixed
    */
    function query($query) {
        $this->_logCall();
        
        //Select queries
        if (preg_match('/^\s*SELECT\s+(.+?)\s+FROM\s+?(\S+?)\s*(WHERE\s+?(.+?)\s*)?(GROUP\s+?BY\s+?(.+?)\s*)?(ORDER\s+?BY\s+?(.+?))?(LIMIT\s+?(.+?)\s*)?;?$/is', $query, $matches)) {          
            $table = !empty($matches[2]) ? $matches[2] : null;
            $fields = !empty($matches[1]) ? $matches[1] : null;
            $criteria = !empty($matches[4]) ? $this->_criteriaToArray($matches[4]) : null;
            $order = !empty($matches[8]) ? explode(',', $matches[8]) : null;
            $limit = !empty($matches[10]) ? $matches[10] : null;
            
            return $this->select($table, $fields, $criteria, $order, $limit);
        }
        //Describe table
        elseif (preg_match('/^\s*DESC(RIBE)?\s+(.+?)\s*;?$/is', $query, $matches)) {          
            $table = !empty($matches[2]) ? $matches[2] : null;
            
            return $this->describe($table);
        }
        //Show information
        elseif (preg_match('/^\s*SHOW?\s+(TABLES|VARIABLES)\s*;?$/is', $query, $matches)) {
            if (strtolower($matches[1]) == 'tables') {
                return $this->showTables();
            }
            elseif (strtolower($matches[1]) == 'variables') {
                return $this->showVariables();
            }
            else {
                $this->_logError(sprintf(ERROR_INVALID_QUERY, $query));
                return false;
            }
        }
        else {
            $this->_logError(sprintf(ERROR_INVALID_QUERY, $query));
            return false;
        }
    }
    
   /**
    * Performs an SQL-like syntax statement on the dataset
    * @param string $statement the SQL statement
    * @return boolean
    */
    function execute($statement) {
        $this->_logCall();
        
        //Update row(s)
        if (preg_match('/^\s*UPDATE\s+?(.+?)\s+?SET\s+?(.+?)\s*(WHERE\s+?(.+?)\s*)?(LIMIT\s+?(.+?)\s*)?;?$/is', $statement, $matches)) {          
            $table = !empty($matches[1]) ? $matches[1] : null;
            $fields = !empty($matches[2]) ? $this->_fieldsToArray($matches[2]) : null;
            $criteria = !empty($matches[4]) ? $this->_criteriaToArray($matches[4]) : null;
            $limit = !empty($matches[6]) ? $matches[6] : null;
            
            return $this->update($table, $fields, $criteria, $limit);
        }
        //Delete row(s)
        elseif (preg_match('/^\s*DELETE\s+?FROM\s+?(.+?)\s*(WHERE\s+?(.+?)\s*)?(LIMIT\s+?(.+?)\s*)?;?$/is', $statement, $matches)) {
            $table = !empty($matches[1]) ? $matches[1] : null;
            $criteria = !empty($matches[3]) ? $this->_criteriaToArray($matches[3]) : null;
            $limit = !empty($matches[5]) ? $matches[5] : null;
            
            return $this->delete($table, $criteria, $limit);
        }
        //Insert row
        elseif (preg_match('/^\s*INSERT\s+?INTO\s+?(.+?)\s*\((.+?)\)\s+?VALUES\s*\((.+?)\)\s*;?$/is', $statement, $matches)) {
            $table = !empty($matches[1]) ? $matches[1] : null;
            
            if (!empty($matches[2]) && !empty($matches[3])) {
                preg_match_all('/\s*\'?([^,\']+)\'?\s*,?\s*/is', $matches[2], $fieldNames);
                preg_match_all('/\s*\'?([^,\']+)\'?\s*,?\s*/is', $matches[3], $fieldValues);
                
                for ($i = 0; $i < count($fieldNames[1]); $i++) {
                    $fields[$fieldNames[1][$i]] = $fieldValues[1][$i];
                }
            }
            
            return $this->insert($table, $fields);
        }
        //Create table
        elseif (preg_match('/^\s*CREATE\s+?TABLE(\s+?IF\s+?NOT\s+?EXISTS)?\s+?(.+?)\s*;?$/is', $statement, $matches)) {
            $table = !empty($matches[2]) ? $matches[2] : null;
            $ifNotExists = !empty($matches[1]) ? true : false;
            
            return $this->createTable($table, $ifNotExists);
        }
        //Drop table
        elseif (preg_match('/^\s*DROP\s+?TABLE(\s+?IF\s+?EXISTS)?\s+?(.+?)\s*;?$/is', $statement, $matches)) {
            $table = !empty($matches[2]) ? $matches[2] : null;
            $ifExists = !empty($matches[1]) ? true : false;
            
            return $this->dropTable($table, $ifExists); 
        }
        //Truncate table
        elseif (preg_match('/^\s*TRUNCATE(\s+?TABLE)?\s+?(.+?)\s*;?$/is', $statement, $matches)) {
            $table = !empty($matches[2]) ? $matches[2] : null;
            
            return $this->truncateTable($table);
        }
        //Rename table
        elseif (preg_match('/^\s*RENAME\s+?TABLE\s+?(.+?)\s+?TO\s+?(.+?)\s*;?$/is', $statement, $matches)) {
            $oldTable = !empty($matches[1]) ? $matches[1] : null;
            $newTable = !empty($matches[2]) ? $matches[2] : null;
            
            return $this->renameTable($oldTable, $newTable);
        }
        //Load/use table
        elseif (preg_match('/^\s*USE\s+?(.+?);?$/is', $statement, $matches)) {
            $table = !empty($matches[1]) ? $matches[1] : null;
            
            return $this->load($table);
        }
        //Set variable
        elseif (preg_match('/^\s*SET\s+?(.+?)\s*;?$/is', $statement, $matches)) {
            $variables = !empty($matches[1]) ? $this->_fieldsToArray($matches[1]) : null;
            
            return $this->set($variables);
        }
        //Create dataset
        elseif (preg_match('/^\s*CREATE\s+?DATA(SET|BASE)(\s+?IF\s+?NOT\s+?EXISTS)?\s+?(.+?)\s*;?$/is', $statement, $matches)) {
            $dataset = !empty($matches[3]) ? $matches[3] : null;
            $ifNotExists = !empty($matches[2]) ? true : false;
            
            return $this->createDataset($dataset, $ifNotExists);
        }
        //Drop dataset
        elseif (preg_match('/^\s*DROP\s+?DATA(SET|BASE)(\s+?IF\s+?EXISTS)?\s+?(.+?)\s*;?$/is', $statement, $matches)) {
            $dataset = !empty($matches[3]) ? $matches[3] : null;
            $ifExists = !empty($matches[2]) ? true : false;
            
            return $this->dropDataset($dataset, $ifExists); 
        }
        else {
            $this->_logError(sprintf(ERROR_INVALID_QUERY, $statement));
            return false;
        }
    }
    
   /**
    * Performs a query on the dataset
    * @param string $table the table name
    * @param array $fields the fields to retrieve
    * @param array $criteria the criteria for selection
    * @param mixed $order the field(s) to order results by
    * @param int $limit the limit of records to return
    * @return array
    */
    function select($table, $fields = '*', $criteria = array(), $order = '', $limit = -1) {
        $this->_logCall();
        
        if (!empty($this->dataset[$table])) {
            foreach ($this->dataset[$table] as $key => $data) {
                foreach ($data as $field => $value) {
                    //If criteria includes the current field, make sure it matches
                    if (!empty($criteria[$field])) {
                        //If the criterion is an array, use appropriate comparison
                        if (is_array($criteria[$field])) {
                            switch ($criteria[$field]['test']) {
                                case '=':   if ($criteria[$field]['value'] == $value) {
                                                $matches[] = $key;
                                            }
                                            break;
                                
                                case '<>':
                                case '!=':  if ($criteria[$field]['value'] != $value) {
                                                $matches[] = $key;
                                            }
                                            break;
                                
                                case '>':   if ($value > $criteria[$field]['value']) {
                                                $matches[] = $key;
                                            }
                                            break;
                                
                                case '>=':  if ($value >= $criteria[$field]['value']) {
                                                $matches[] = $key;
                                            }
                                            break;
                                
                                case '<':   if ($value < $criteria[$field]['value']) {
                                                $matches[] = $key;
                                            }
                                            break;
                                
                                case '<=':  if ($value <= $criteria[$field]['value']) {
                                                $matches[] = $key;
                                            }
                                            break;
                                
                                case 'REGEX':
                                            if (preg_match($criteria[$field]['value'], $value)) {
                                                $matches[] = $key;
                                            }
                                            break;
                                
                                case 'LIKE':
                                            $regex = '/'.$criteria[$field]['value'].'/';
                                            $regex = str_replace('%', '.*', $regex);
                                            $regex = str_replace('_', '.', $regex);
                                            if (preg_match($regex, $value)) {
                                                $matches[] = $key;
                                            }
                                            break;
                                        
                                case 'NOT LIKE':
                                            $regex = '/'.$criteria[$field]['value'].'/';
                                            $regex = str_replace('%', '.*', $regex);
                                            $regex = str_replace('_', '.', $regex);
                                            if (!preg_match($regex, $value)) {
                                                $matches[] = $key;
                                            }
                                            break;  
                                
                                default:    $this->_logError(sprintf(ERROR_INVALID_OPERATOR, $criteria[$field]['test']));
                                            return false;
                                            break;
                            }
                        }
                        else {
                            if ($value == $criteria[$field]) {
                                $matches[] = $key;
                            }
                        }
                    }
                    elseif (empty($criteria)) {
                        $matches[] = $key;
                    }
                }
            }
        }
        
        //If we have criteria matches, filter them a bit. Otherwise, return false
        if (!empty($matches)) {
            //Strip out duplicate entries
            $matches = array_unique($matches);
            
            $i = 0;
            foreach ($matches as $match) {
                //Make sure we don't go over the results limit
                if ($limit > -1 && !empty($results) && count($results) >= $limit) {
                    break;
                }
                
                //Only return specified fields
                if (empty($fields) || $fields == '*') {
                    $results[$i] = $this->dataset[$table][$match];
                }
                else {
                    foreach ($fields as $field) {
                        $results[$i][$field] = $this->dataset[$table][$match][$field];
                    }
                }
                $i++;
            }
            
            //Order results appropriately
            if (!empty($order)) {
                //If order is a single string, convert to array
                if (is_string($order)) {
                    $order = array($order);
                }
                
                //Use regex to split field and irection, then add to an orderBy array
                foreach ($order as $orderString) {
                    if (preg_match('/^\s*(\S+)\s*(ASC|DESC)?(ENDING)?\s*$/i', $orderString, $orderInfo)) {
                        $column = array();
                        foreach ($results as $key => $fields) {
                            $column[] = $fields[$orderInfo[1]];
                        }
                        
                        $orderBy[] = array('field' => $orderInfo[1],
                                           'direction' => !empty($orderInfo[2]) ? $orderInfo[2] : 'ASC',
                                           'column' => $column
                                          );
                    }
                    else {
                        $this->_logError(sprintf(ERROR_INVALID_ORDER, $orderString));
                        return false;
                    }
                }
                
                //Construct the multisort call for eval
                $sort = 'array_multisort(';
                foreach ($orderBy as $k => $orderData) {
                    $sort .= '$orderBy[$k]["column"], ';
                    if ($orderData['direction'] == 'ASC') {
                        $sort .= 'SORT_ASC';
                    }
                    elseif ($orderData['direction'] == 'DESC') {
                        $sort .= 'SORT_DESC';
                    }
                    $sort .= ', ';
                }                
                $sort .= '$results);';
                
                eval($sort);
            }
            
            $this->lastAffectedRows = count($results);
            return $results;
        }
        else {
            $this->lastAffectedRows = 0;
            return false;
        }
        
    }
    
   /**
    * Updates a specific record in a table
    * @param string $table the table name
    * @param array $fields the fields and their values to update
    * @param array $criteria the criteria for update
    * @param int $limit the limit of affected rows
    * @return boolean
    */
    function update($table, $fields, $criteria = array(), $limit = -1) {
        $this->_logCall();
        
        //Return if insufficient data
        if (empty($table) || empty($fields)) {
            $this->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        //Make sure the table exists
        if (!array_key_exists($table, $this->dataset)) {
            //If table does not exist, create it automatically if requested
            if ($this->autoCreate) {
                $this->createTable($table);
            }
            else {
                $this->_logError(sprintf(ERROR_TABLE_DNE, $table));
                return false;
            }
        }
        
        //Make sure criteria is in array form
        if (!is_array($criteria)) {
            $criteria = $this->_criteriaToArray($criteria);
        }
        
        //Make sure fields is an array
        if (!is_array($fields)) {
            $fields = $this->_fieldsToArray($fields);
        }
        
        $affectedRows = 0;
        
        if ($records = $this->select($table, array('id'), $criteria, null, $limit)) {
            foreach ($records as $record) {
                //Update values
                foreach ($fields as $field => $value) {
                    $this->dataset[$table][$record['id']][$field] = $value;
                }
                $affectedRows++;
            }
        }
        
        $this->lastAffectedRows = $affectedRows;
        
        if ($this->autoSave) {
            $this->save();
        }
        
        return true;
    }
    
   /**
    * Insert a new record into a table
    * @param string $table the table name
    * @param array $fields the fields and their values to set
    * @return boolean
    */
    function insert($table, $fields) {
        $this->_logCall();
        
        //Return if insufficient data
        if (empty($table) || empty($fields)) {
            $this->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        //Make sure the table exists
        if (!array_key_exists($table, $this->dataset)) {
            //If table does not exist, create it if requested
            if ($this->autoCreate) {
                $this->createTable($table);
            }
            else {
                $this->_logError(sprintf(ERROR_TABLE_DNE, $table));
                return false;
            }
        }
        
        //If id isn't set, auto_increment the id
        if (array_key_exists('id', $fields)) {
            if ($this->exists($table, $fields['id'])) {
                $this->_logError(sprintf(ERROR_DUPLICATE_KEY, $fields['id'], $table));
                return false;
            }
            $autoIncrement = false;
        }
        else {
            $autoIncrement = true;
        }
        
        //Insert the fields
        if ($autoIncrement == true) {
            //If empty, start at 1, otherwise start at next
            if (empty($this->dataset[$table])) {
                $this->dataset[$table][1] = $fields;
            }
            else {
                $this->dataset[$table][] = $fields;
            }
            end($this->dataset[$table]);
            $id = key($this->dataset[$table]);
            $this->dataset[$table][$id]['id'] = $id;
            $this->lastInsertedId = $id;
            reset($this->dataset[$table]);
        }
        else {
            $this->dataset[$table][$fields['id']] = $fields;
        }
        $this->lastAffectedRows = 1;
        
        if ($this->autoSave) {
            $this->save();
        }
        
        return true;
    }
    
   /**
    * Delete a record from a table
    * @param string $table the table name
    * @param mixed $criteria the criteria for deletion
    * @param int $limit the limit of affected rows
    * @return boolean
    */
    function delete($table, $criteria = array(), $limit = -1) {
        $this->_logCall();
        
        //Return if insufficient data
        if (empty($table)) {
            $this->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        //Make sure the table exists
        if (!array_key_exists($table, $this->dataset)) {
            //If table doesn't exist, create it if requested
            if ($this->autoCreate) {
                $this->createTable($table);
            }
            else {
                $this->_logError(sprintf(ERROR_TABLE_DNE, $table));
                return false;
            }
        }
        
        //Make sure criteria is in array form
        if (!is_array($criteria)) {
            $criteria = $this->_criteriaToArray($criteria);
        }
        
        $affectedRows = 0;
        
        if ($records = $this->select($table, array('id'), $criteria, null, $limit)) {
            foreach ($records as $record) {
                //Delete record
                unset($this->dataset[$table][$record['id']]);
                $affectedRows++;
            }
        }
        
        $this->lastAffectedRows = $affectedRows;
        
        if ($this->autoSave) {
            $this->save();
        }
        
        return true;
    }
    
   /**
    * Creates a new table
    * @param string $table the new table name
    * @param boolean $ifNotExists only create the table if it doesn't exist
    * @return boolean
    */
    function createTable($table, $ifNotExists = false) {
        $this->_logCall();
        
        //Make sure table name was passed
        if (empty($table)) {
            $this->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        //Make sure the table doesn't already exist
        if (array_key_exists($table, $this->dataset)) {
            //If we said only create if it doesn't exist, we shouldn't fail if it
            //does exist.
            if ($ifNotExists) {
                $this->lastAffectedRows = 0;
                return true;
            }
            else {
                $this->_logError(sprintf(ERROR_TABLE_DE, $table));
                return false;
            }
        }
        
        $this->dataset[$table] = array();
        $this->lastAffectedRows = 1;
        
        if ($this->autoSave) {
            $this->save();
        }
        
        return true;
    }
    
   /**
    * Alias for dropTable()
    * @alias dropTable
    */
    function deleteTable($table, $ifExists) {
        return $this->dropTable($table, $ifExists);
    }
    
   /**
    * Drops a table
    * @param string $table table name to drop
    * @param boolean $ifExists only drop if the table exists
    * @return boolean
    */
    function dropTable($table, $ifExists = false) {
        $this->_logCall();
        
        //Make sure table name was passed
        if (empty($table)) {
            $this->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        //Make sure the table exists
        if (!array_key_exists($table, $this->dataset)) {
            //If we said only drop if it exists, we shouldn't fail if it doesn't exist.
            if ($ifExists) {
                $this->lastAffectedRows = 0;
                return true;
            }
            else {
                $this->_logError(sprintf(ERROR_TABLE_DNE, $table));
                return false;
            }
        }
        
        unset($this->dataset[$table]);
        $this->lastAffectedRows = 1;
        
        if ($this->autoSave) {
            $this->save();
        }
        
        return true;
    }
    
   /**
    * Truncates (empties) a table
    * @param string $table table name to truncate
    * @return boolean
    */
    function truncateTable($table) {
        $this->_logCall();
        
        //Make sure table name was passed
        if (empty($table)) {
            $this->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        //Make sure the table exists
        if (!array_key_exists($table, $this->dataset)) {
            //If table doesn't exist, create it if requested
            if ($this->autoCreate) {
                $this->createTable($table);
            }
            else {
                $this->_logError(sprintf(ERROR_TABLE_DNE, $table));
                return false;
            }
        }
        
        $this->lastAffectedRows = count($this->dataset[$table]);
        $this->dataset[$table] = array();
        
        if ($this->autoSave) {
            $this->save();
        }
        
        return true;
    }
    
   /**
    * Renames a table
    * @param string $oldTable old table name
    * @param string $newTable new table name
    * @return boolean
    */
    function renameTable($oldTable, $newTable) {
        $this->_logCall();
        
        //Make sure table name was passed
        if (empty($oldTable) || empty($newTable)) {
            $this->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        //Make sure the old table exists
        if (!array_key_exists($oldTable, $this->dataset)) {
            $this->_logError(sprintf(ERROR_TABLE_DNE, $oldTable));
            return false;
        }
        
        //Make sure the new table doesn't exist
        if (array_key_exists($newTable, $this->dataset)) {
            $this->_logError(sprintf(ERROR_TABLE_DNE, $newTable));
            return false;
        }
        
        $this->lastAffectedRows = 1;
        $this->dataset[$newTable] = $this->dataset[$oldTable];
        
        //Drop old table
        $this->dropTable($oldTable);
        
        if ($this->autoSave) {
            $this->save();
        }
        
        return true;
    }
    
   /**
    * Describes the columns of a table
    * @param string $table the table name
    * @return array
    */
    function describe($table) {
        $info = array();
        
        //Store all keys in an array
        if (!empty($this->dataset[$table])) {
            $totalRows = count($this->dataset[$table]);
            
            foreach ($this->dataset[$table] as $row) {
                foreach ($row as $key => $value) {
                    if (!empty($info[$key])) {
                        $info[$key]['Rows Applied']++;
                        $info[$key]['Percentage'] = ($info[$key]['Rows Applied']/$totalRows*100).'%';
                    }
                    else {
                        $info[$key] = array(
                                            'Field Name' => $key,
                                            'Rows Applied' => 1,
                                            'Total Rows' => $totalRows,
                                            'Percentage' => (1/$totalRows*100).'%'
                                           );
                    }
                }
            }
        }
        else {
            $this->arraise->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        $this->lastAffectedRows = count($info);
        
        return $info;
    }
    
   /**
    * Lists the tables of the current dataset
    * @return array
    */
    function showTables() {
        $info = array();
        
        //Store all keys in an array
        if (!empty($this->dataset)) {
            foreach ($this->dataset as $table => $data) {
                $info[$table] = array(
                                      'Table' => $table,
                                      'Records' => count($data)
                                     );
            }
        }
        
        $this->lastAffectedRows = count($info);
        
        return $info;
    }
    
   /**
    * Lists the variables
    * @return array
    */
    function showVariables() {
        $variables = get_class_vars('arraise');
        $info = array();
        
        foreach ($variables as $name => $value) {
            //Need to get current value, not default
            $value = $this->{$name};
            if (is_bool($value)) {
                $value = ($value === true ? 'true' : 'false');
            }
            
            if (is_array($value)) {
                $value = '(array)';
            }
            
            $info[$name] = array(
                                 'Name' => $name,
                                 'Value' => $value
                                );
        }
        
        $this->lastAffectedRows = count($info);
        
        return $info;
    }
    
   /**
    * Determines whether a given record exists in a table
    * @param string $table the table name
    * @param string $key the primary key
    * @return boolean
    */
    function exists($table, $key) {
        $this->_logCall();
        
        //Return if insufficient data
        if (empty($table) || empty($key)) {
            $this->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        return array_key_exists($key, $this->dataset[$table]);
    }
    
   /**
    * Prints debug information about the dataset and object
    * @param array $array array to print
    */
    function debug($array = array()) {
        echo '<pre>';
        
        if (empty($array)) {
            echo "name: {$this->name}<br>";
            echo "savePath: {$this->savePath}<br>";
            echo "lastInsertedId: {$this->lastInsertedId}<br>";
            echo "lastAffectedRows: {$this->lastAffectedRows}<br>";
            echo "lastError: {$this->lastError}<br>";
            echo "lastCall: {$this->lastCall}<br>";
            echo 'logging: '.($this->logging == true ? 'true' : 'false').'<br>';
            echo 'outputErrors: '.($this->outputErrors == true ? 'true' : 'false').'<br>';
            echo 'autoSave: '.($this->autoSave == true ? 'true' : 'false').'<br><br>';
            
            if ($this->logging == true) {
                print_r($this->log);
            }
        }
        
        print_r(!empty($array) ? $array : $this->dataset);
        echo '</pre>';
    }

   /**
    * Set a boolean variable
    * @param array $variables array of variables and their new values
    * @param bool $requireBool whether to require boolean variables/values
    * @return boolean
    */
    function set($variables, $requireBool = true) {
        //Make sure something was passed
        if (empty($variables)) {
            $this->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        foreach ($variables as $variable => $value) {
            //Make sure variable exists
            if (!isset($this->{$variable})) {
                $this->_logError(sprintf(ERROR_VARIABLE_DNE, $variable));
                return false;
            }
            
            //Make sure variable is boolean
            if (!is_bool($this->{$variable}) && $requireBool) {
                $this->_logError(sprintf(ERROR_VARIABLE_NOTBOOL, $variable));
                return false;
            }
            
            //Make sure new value is boolean
            if ($value === true || $value === 'true') {
                $variables[$variable] = true;
            }
            elseif ($value === false || $value === 'false') {
                $variables[$variable] = false;
            }
            elseif ($requireBool) {
                $this->_logError(sprintf(ERROR_VARIABLE_VALUE_NOTBOOL, $variable, $value));
                return false;
            }
        }
        
        //Everything looks OK; set them
        foreach ($variables as $variable => $value) {
            $this->{$variable} = $value;
        }
        
        $this->lastAffectedRows = count($variables);
        
        return true;
    }
    
   /**
    * Save the dataset to file
    * @return boolean
    */
    function save() {
        return $this->_write($this->name, $this->dataset);
    }
    
   /**
    * Load the dataset from a file, if it exists
    * @param string $name the name of the dataset
    * @return boolean
    */
    function load($name) {
        $file = $this->_getFilename($name);
        
        if (file_exists($file)) {
            $data = file_get_contents($file);
            
            $this->dataset = unserialize($data);
            $this->name = $name;
            
            return true;
        }
        
        return false;
    }
    
   /**
    * Create a dataset
    * Dataset operations are automatically saved.
    * @param string $dataset the dataset to create
    * @param bool $ifNotExists whether the dataset should only be created if it doesn't exist
    * @return boolean
    */
    function createDataset($dataset, $ifNotExists = false) {
        if (empty($dataset)) {
            $this->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        //Check if it the dataset already exists
        $file = $this->_getFilename($dataset);
        if (file_exists($file)) {
            if ($ifNotExists) {
                $this->lastAffectedRows = 0;
                return true;
            }
            else {
                $this->_logError(sprintf(ERROR_DATASET_DE, $dataset));
                return false;
            }
        }
        $this->lastAffectedRows = 1;
        
        return $this->_write($dataset, array());
    }
    
   /**
    * Drop a dataset
    * Dataset operations are automatically saved.
    * @param string $dataset the dataset to drop, if not the current one
    * @param bool $ifExists whether the database should only be dropped if it exists
    * @return boolean
    */
    function dropDataset($dataset = '', $ifExists = false) {
        //If not set, use current dataset
        if (empty($dataset)) {
            $dataset = $this->name;
        }
        
        $file = $this->_getFilename($dataset);
        
        //Delete file if it exists
        if (file_exists($file)) {
            unlink($file);
        }
        else {
            if ($ifExists) {
                $this->lastAffectedRows = 0;
                return true;
            }
            else {
                $this->_logError(sprintf(ERROR_DATASET_DNE, $dataset));
                return false;
            }
        }
        
        //If dropping current dataset, clear it
        if (empty($dataset) || $dataset == $this->name) {
            $this->dataset = array();
        }
        
        $this->lastAffectedRows = 1;
        
        return true;
    }
    
   /**
    * Convert criteria string to array.
    * Example:
    *   fname='Justin' AND lname LIKE '%Scott%'
    *       becomes
    *   array('fname' => array('test' => '=', 'value' => 'Justin'),
    *         'lname' => array('test' => 'LIKE', 'value' => '%Scott%'));
    *
    * @param string $criteria the criteria string to convert
    * @return array
    */
    function _criteriaToArray($criteria) {
        $criteriaArray = array();
        
        if (!empty($criteria)) {
            $criteria = explode(' AND ', $criteria);
            foreach ($criteria as $criterion) {
                if (preg_match('/^\s*(.+?)\s*([=!><]{1,2}|LIKE|NOT LIKE|REGEX)\s*\'?(.+?)\'?\s*$/is', $criterion, $criteriaMatches)) {
                    $criteriaArray[$criteriaMatches[1]] = array('test' => $criteriaMatches[2],
                                                                'value' => $criteriaMatches[3]);
                }
            }
        }
        
        return $criteriaArray;
    }
    
   /**
    * Convert fields string to array.
    * Example:
    *   fname='Justin', lname='Scott'
    *       becomes
    *   array('fname' => 'Justin', 'lname' => 'Scott');
    *
    * @param string $fields the fields string to convert
    * @return array
    */
    function _fieldsToArray($fields) {
        $fieldsArray = array();
        
        if (!empty($fields)) {
            $fields = explode(',', $fields);
            foreach ($fields as $field) {
                if (preg_match('/^\s*(.+?)\s*=\s*\'?(.+?)\'?\s*$/is', $field, $fieldMatches)) {
                    $fieldsArray[$fieldMatches[1]] = $fieldMatches[2];
                }
            }
        }
        
        return $fieldsArray;
    }
    
   /**
    * Verify dataset
    * @return boolean
    */
    function _verifyData() {
        if (empty($this->dataset)) {
            $this->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        return true;
    }
    
   /**
    * Log errors and possibly output them
    * @param string $error The error string to log/output
    */
    function _logError($error) {
        $this->lastError = $error;
        $this->lastAffectedRows = 0;
        
        if ($this->logging) {
            $this->log['errors'][] = $error;
        }
        
        //Output error if requested
        if ($this->outputErrors) {
            if (!empty($this->errorHTML)) {
                $errorHTML = $this->errorHTML;
                
                $errorHTML = str_replace('%ERROR%', $error, $errorHTML);
                $errorHTML = str_replace('%FILE%', $this->lastCall['file'], $errorHTML);
                $errorHTML = str_replace('%LINE%', $this->lastCall['line'], $errorHTML);
                $errorHTML = str_replace('%FUNCTION%', $this->lastCall['function'], $errorHTML);
                echo $errorHTML;
            }
            else {
                echo '<div style="border: 1px solid salmon; font-family: Arial;">';
                    echo '<div style="background-color: salmon; text-indent: 1em;">';
                        echo "<b>arraise Error</b>: Your query could not be executed because:";
                    echo '</div>';
                    echo '<div>';
                        echo '<ul>';
                            echo "<li><b>{$error}</b></li>";
                            echo "<li>Occurred in <tt>{$this->lastCall['file']}, line {$this->lastCall['line']}</tt></li>";
                            echo "<li>Called method <tt>{$this->lastCall['function']}()</tt> with arguments:</li>";
                            echo '<ul>';
                                if (!empty($this->lastCall['args'])) {
                                    foreach ($this->lastCall['args'] as $arg) {
                                        echo '<li><pre>'.print_r($arg, true).'</pre></li>';
                                    }
                                }
                                else {
                                    echo '<li>None</li>';
                                }
                            echo '</ul>';
                        echo '</ul>';
                    echo '</div>';
                echo '</div>';
            }
        }
    }
    
   /**
    * Log function/method calls
    */
    function _logCall() {
        $backtrace = debug_backtrace();
        
        $this->lastCall = $backtrace[1];
        
        if ($this->logging) {
            $this->log['calls'][] = $backtrace[1];
        }
    }
    
   /**
    * Serialize data and write to a file
    * @param string $dataset the dataset name
    * @param array $data the data to write
    * @return boolean
    */
    function _write($dataset, $data) {
        $file = $this->_getFilename($dataset);
        
        $data = serialize($data);
        
        $fp = fopen($file, 'w');
        fwrite($fp, $data);
        fclose($fp);
        
        return true;
    }

   /**
    * Returns the filename of the dataset
    * @param string $dataset the dataset name
    * @return boolean
    */
    function _getFilename($dataset) {
        return sprintf($this->savePath.'/%s.arraise', $dataset);
    }
}

?>