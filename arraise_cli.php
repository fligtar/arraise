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

class arraise_cli extends arraise {
    
   /**
    * Constructor that initiates the main arraise items
    * @param array $params the parameters the CLI script was called with
    */
    function arraise_cli($params) {
        //Pull out dataset name if passed
        if (!empty($params['d'])) {
            $name = $params['d'];
            unset($params['d']);
        }
        else {
            $name = null;
        }
        
        //Turn error output off
        $params['outputErrors'] = false;
        
        $this->arraise($name, $params);
    }

   /**
    * Directs CLI input to the appropriate arraise functions
    * @param string $command the command
    * @return string
    */
    function command($command) {
        //Queries that use query()
        if (preg_match('/^\s*(SELECT|DESC|SHOW)/is', $command)) {
            $results = $this->query($command);
            
            if ($results !== false) {
                $rows = "{$this->lastAffectedRows} rows returned.";
                
                if (count($results) > 0) {
                    return $this->display->displayDataAscii($results).$rows;
                }
                else {
                    return $rows;
                }
            }
            else {
                return "Error: {$this->lastError}";
            }
        }
        //Statements that use execute()
        elseif (preg_match('/^\s*(INSERT|UPDATE|DELETE|DROP|CREATE|SET)/is', $command)) {
            if ($this->execute($command)) {
                return "Executed successfully; {$this->lastAffectedRows} rows affected.";
            }
            else {
                return "Error: {$this->lastError}";
            }
        }
        //Select a dataset
        elseif (preg_match('/^\s*USE/is', $command)) {
            if ($this->execute($command)) {
                return "Dataset `{$this->name}` loaded.";
            }
            else {
                return "Error: Could not load dataset.";
            }
        }
        //Save dataset
        elseif (preg_match('/^\s*SAVE/is', $command)) {
            if ($this->save()) {
                return "Dataset `{$this->name}` saved.";
            }
            else {
                return "Error: {$this->lastError}";
            }
        }
        
        return "\nUnknown Command: {$command}";
    }
}

?>