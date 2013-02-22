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

require_once('simpletest/unit_tester.php');
require_once('reporter.php');
require_once('../arraise.php');

class arraiseTest extends UnitTestCase {
    var $testData = array(
                         'users' => array(
                                          0 => array(
                                                     'id' => 0,
                                                     'fname' => 'Justin',
                                                     'lname' => 'Scott',
                                                     'age' => 20
                                                    ),
                                          1 => array(
                                                     'id' => 1,
                                                     'fname' => 'Devin',
                                                     'lname' => 'Davis',
                                                     'age' => 19
                                                    ),
                                          2 => array(
                                                     'id' => 2,
                                                     'fname' => 'Erin',
                                                     'lname' => 'Wilbanks',
                                                     'age' => 19
                                                    )
                                         )
                        );
    
    function setUp() {
        $this->arraise = new arraise('test', array('logging' => true), $this->testData);
        //$this->arraise->debug();
    }
    
    function testExecute() {
        //Update
        $result = $this->arraise->execute("UPDATE users SET fname='Foo', lname='Bar' WHERE id=0 LIMIT 1");
        $this->assertTrue($result, 'Execute update: returned true');
        $this->assertEqual($this->arraise->dataset['users'][0]['fname'], 'Foo', 'Execute update: matching data');
        
        //Delete
        $result = $this->arraise->execute("DELETE FROM users WHERE id=3 LIMIT 1");
        $this->assertTrue($result, 'Execute delete: returned true');
        $this->assertFalse(isset($this->arraise->dataset['users'][3]), 'Execute delete: matching data');
        
        //Insert
        $result = $this->arraise->execute("INSERT INTO users (fname, lname) VALUES('Bob', 'Barker')");
        $this->assertTrue($result, 'Execute insert: returned true');
        $this->assertEqual($this->arraise->dataset['users'][$this->arraise->lastInsertedId]['fname'], 'Bob', 'Execute insert: matching data');
        
        //Create table
        $result = $this->arraise->execute("CREATE TABLE groups");
        $this->assertTrue($result, 'Execute create table: returned true');
        $this->assertEqual($this->arraise->lastAffectedRows, 1, 'Execute create table: 1 affected row');
        
        //Create table if not exists
        $result = $this->arraise->execute("CREATE TABLE IF NOT EXISTS groups");
        $this->assertTrue($result, 'Execute create table if not exists: returned true');
        $this->assertEqual($this->arraise->lastAffectedRows, 0, 'Execute create table: 0 affected rows %s');
        
        //Create table (already exists)
        $result = $this->arraise->execute("CREATE TABLE groups");
        $this->assertFalse($result, 'Execute create table (exists): returned false');
        $this->assertEqual($this->arraise->lastError, 'Table `groups` already exists', 'Execute create table (exists): correct error message');
        
        //Drop table
        $result = $this->arraise->execute("DROP TABLE groups");
        $this->assertTrue($result, 'Execute drop table: returned true');
        $this->assertEqual($this->arraise->lastAffectedRows, 1, 'Execute drop table: 1 affected row');
        
        //Drop table if exists
        $result = $this->arraise->execute("DROP TABLE IF EXISTS groups");
        $this->assertTrue($result, 'Execute drop table if exists: returned true');
        $this->assertEqual($this->arraise->lastAffectedRows, 0, 'Execute drop table: 0 affected rows %s');
        
        //Drop table (does not exist)
        $result = $this->arraise->execute("DROP TABLE groups");
        $this->assertFalse($result, 'Execute drop table (does not exist): returned false');
        $this->assertEqual($this->arraise->lastError, 'Table `groups` does not exist', 'Execute drop table (does not exist): correct error message');
        
        //Truncate table
        $result = $this->arraise->execute("TRUNCATE TABLE users");
        $this->assertTrue($result, 'Execute truncate table: returned true');
        $this->assertFalse(isset($this->arraise->dataset['users'][0]), 'Execute truncate table: no data set');
        
        //Rename table
        $this->setUp();
        $result = $this->arraise->execute("RENAME TABLE users TO members");
        $this->assertTrue($result, 'Execute rename table: returned true');
        $this->assertFalse(isset($this->arraise->dataset['users'][0]), 'Execute rename table: no data set in old table');
        $this->assertTrue(isset($this->arraise->dataset['members'][0]), 'Execute rename table: data set in new table');
    }
    
    function testQuery() {
        //Select
        $results = $this->arraise->query("SELECT * FROM users WHERE fname NOT LIKE 'Devin' ORDER BY fname DESC LIMIT 5");
        $this->assertIsA($results, 'Array', 'Query: type match');
        $this->assertEqual($results[1]['id'], 2, 'Query: data match');
    }
}

$test = &new arraiseTest();
$test->run(new HtmlReporter());
?>