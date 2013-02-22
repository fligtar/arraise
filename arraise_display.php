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

class arraise_display extends arraise {
    var $arraise;
    var $dataset = array();
    
   /**
    * Constructor that sets references to the main arraise class
    * @param object &$arraise the main arraise class
    */
    function arraise_display(&$arraise) {
        $this->arraise =& $arraise;
        $this->dataset =& $arraise->dataset;
    }

   /**
    * Displays the rows of a table
    * @param string $table the name of the table to display
    * @return boolean
    */
    function displayTable($table) {
        $this->arraise->_logCall();
        
        //Make sure table exists
        if (!empty($this->dataset[$table])) {
            return $this->displayData($this->dataset[$table], "Table `{$table}`");
        }
        else {
            $this->arraise->_logError(sprintf(ERROR_TABLE_DNE, $table));
            return false;
        }
    }
    
   /**
    * Displays the data of an array
    * @param array $data the data to display, in arraise form
    * @param string $title the title of the display
    * @return boolean
    */
    function displayData($data = array(), $title = 'Data') {
        $this->arraise->_logCall();
        
        $keys = array();
        
        //Store all keys in an array
        if (!empty($data)) {
            foreach ($data as $row) {
                $keys = array_merge($keys, array_keys($row));
            }
        }
        else {
            $this->arraise->_logError(ERROR_INSUFFICIENT_DATA);
            return false;
        }
        
        //Clear duplicates
        $keys = array_unique($keys);
        
        echo "<span style=\"font-weight: bold; font-size: 120%;\">{$title}</span>";
        echo '<table cellspacing=0 cellpadding=1 style="width: 100%; border: 1px solid black;">';
        //Loop through keys for the header
            echo '<tr>';
                echo '<th style="width: 2em; background-color: #ddddff; border-bottom: 1px solid black;">id</th>';
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        if ($key == 'id') continue;
                        echo "<th style=\"background-color: #ddddff; border-left: 1px solid gray; border-bottom: 1px solid black;\">{$key}</th>";
                    }
                }
            echo '</tr>';
            
            $i = 0;
            //Loop through rows
            foreach ($data as $row) {
                echo '<tr'.($i % 2 == 1 ? ' bgcolor="#eeeeee"' : '').'>';
                echo "<td style=\"text-align: center; font-style: italic; font-weight: bold;\">{$row['id']}</td>";
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        if ($key == 'id') continue;
                        
                        if (!empty($row[$key])) {
                            echo "<td style=\"border-left: 1px solid gray;\">{$row[$key]}</td>";
                        }
                        else {
                            echo '<td style="border-left: 1px solid gray;">&nbsp;</td>';
                        }
                    }
                }
                echo '</tr>';
                
                $i++;
            }
        echo '</table>';
        
        return true;
    }

  
   /**
    * Displays the data of an array in ascii form for CLI
    * @param array $data the data to display, in arraise form
    * @return string
    */
    function displayDataAscii($data = array()) {
        $keys = array();
        
        //Store all keys in an array
        if (!empty($data)) {
            foreach ($data as $row) {
                $keys = array_merge($keys, array_keys($row));
            }
        }
        
        //Clear duplicates
        $keys = array_unique($keys);
        
        //Set initial column lengths to be the length of the key
        if (!empty($keys)) {
            foreach ($keys as $key) {
                $columnLengths[$key] = strlen($key);
            }
        }
        
        //Go through each cell and widen the column length as needed
        foreach ($data as $row) {
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    if (strlen($row[$key]) > $columnLengths[$key]) {
                        $size = strlen($row[$key]);
                        if ($size > 20) {
                            $size = 20;
                        }
                        $columnLengths[$key] = $size;
                    }
                }
            }
        }
        
        //Create the divider based on column lengths
        $divider = '';
        foreach ($columnLengths as $k => $columnLength) {
            $divider .= '+--'.str_repeat('-', $columnLength);
        }
        $divider .= "+\n";
        
        $return = $divider;
        
        //Loop through keys for header
        if (!empty($keys)) {
            foreach ($keys as $key) {
                $return .= '| '.$key.str_repeat(' ', $columnLengths[$key] - strlen($key)).' ';
            }
            $return .= "|\n";
        }
        $return .= $divider;
        
        //Loop through rows
        foreach ($data as $row) {
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    if (!empty($row[$key])) {
                        $value = $row[$key];
                        $value = str_replace("\n", ' ', $value);
                        $value = str_replace("\r", ' ', $value);
                        $value = substr($value, 0, 20);
                        
                        $return .= '| '.$value.str_repeat(' ', $columnLengths[$key] - strlen($value)).' ';
                    }
                    else {
                        $return .= '| '.str_repeat(' ', $columnLengths[$key]).' ';
                    }
                }
            }
            $return .= "|\n";
        }
        $return .= $divider;
        
        return $return;
    }
}

?>