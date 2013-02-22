#!/usr/local/bin/php -q
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

//Output help if requested
if (isset($_GET['help']) || isset($_GET['-help']) || isset($_GET['--help']) || isset($_GET['/?'])) {
    help();
    exit;
}

require_once('arraise.php');
require_once('arraise_cli.php');
$cli = new arraise_cli($_GET);

echo "Welcome to the arraise command line interface.\n";
echo "Type 'quit' to exit or 'help' for help.\n";
echo "\nUsing dataset '{$cli->name}'\n";

//Stop the script giving time out errors
set_time_limit(0);

//This opens standard in ready for interactive input
define('STDIN', fopen("php://stdin", "r"));

$quit = false;

while (!$quit) {
    echo "\narraise> ";
    
    //Wait for input and direct it appropriately
    $input = trim(fgets(STDIN, 256));
    switch ($input) {
        //Entered nothing, display prompt again
        case '':
            break;
        
        //Exit the interface
        case 'quit':
        case 'quit;':
        case 'exit':
        case 'exit;':
            echo "\nExiting...";
            $quit = true;
            break;
        
        //Display help
        case 'help':
        case 'help;':
            help();
            break;
        
        //Default is to pass command to arraise
        default:
            echo $cli->command($input);
            break;
    }
}

fclose(STDIN);
echo "\n";
exit;

function help() {
    echo "arraise Help\n\n";
    echo "For full documentation, visit http://arraise.fligtar.com\n";
    echo "\nSyntax:\tcli.php [d=dataset] [CONFIG]\n";
    echo "d\tThe dataset to load or create\n";
    echo "CONFIG is a space separated list of configuration options to set, for example:\n";
    echo "\tcli.php d=test autoSave=false logging=true\n";
    echo "\nCommands:";
    echo "\nhelp\tDisplay this screen";
    echo "\nquit\tExit\n\n";
    echo "Any other input will be considered a query/statement and parsed by arraise\n";
}
?>