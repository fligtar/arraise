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
  
    function testSelect() {
        //Retrieve all users
        $results = $this->arraise->select('users');
        $this->assertEqual($results, $this->testData['users'], 'Retrieve all: correct data');
        
        //Retrieve specified fields from users
        $results = $this->arraise->select('users', array('fname'));
        $this->assertEqual($results[0], array('fname' => 'Justin'), 'Specific fields: correct fields');
        
        //Retrieve records based on criteria
        $results = $this->arraise->select('users', null, array('age' => array('test' => '<', 'value' => 20)));
        $this->assertEqual(count($results), 2, 'Criteria: number of results');
        
        //Retrieve records based on LIKE
        $results = $this->arraise->select('users', null, array('lname' => array('test' => 'LIKE', 'value' => '%avi%')));
        $this->assertEqual($results[1]['id'], 1, 'Criteria (LIKE): data match');
        
        //Retrieve records based on REGEX
        $results = $this->arraise->select('users', null, array('lname' => array('test' => 'REGEX', 'value' => '/avi/')));
        $this->assertEqual($results[1]['id'], 1, 'Criteria (REGEX): data match');
        
        //Retrieve records in certain order
        $results = $this->arraise->select('users', null, null, array('age'));
        $this->assertEqual($results[0]['id'], 1, 'Order (simple): data match 1');
        $this->assertEqual($results[2]['id'], 0, 'Order (simple): data match 2');
        
        //More complicated order
        $results = $this->arraise->select('users', null, null, array('age DESC', 'lname'));
        //$this->arraise->debug($results);
        $this->assertEqual($results[0]['id'], 0, 'Order (complex): data match 1');
        $this->assertEqual($results[2]['id'], 1, 'Order (complex): data match 2');
        
        //Retrieve a limited number of records
        $results = $this->arraise->select('users', null, null, null, 1);
        $this->assertEqual(count($results), 1, 'Limit: correct count');
        
        //Complex query
        $results = $this->arraise->select('users');
        $this->fail('Complex query: TODO');
        
        //Invalid order
        $results = $this->arraise->select('users', null, null, array('blah TEST'));
        $this->assertEqual($this->arraise->lastError, '`blah TEST` is not a valid ORDER BY format', 'Invalid order: correct error message');
        
        //No results
        $results = $this->arraise->select('users', null, array('lname' => 'Foo'));
        $this->assertFalse($results, 'No results: returned false');
    }
    
    function testExists() {
        //Valid record
        $this->assertTrue($this->arraise->exists('users', 1), 'Valid record: returned true');
        
        //Record DNE
        $this->assertFalse($this->arraise->exists('users', 5), 'Non-existant record: returned false');
    }
    
    function testInsert() {
        //Valid insertion (auto_increment)
        $this->assertTrue($this->arraise->insert('users', array('fname' => 'Joe', 'lname' => 'Calvasina')), 'Valid insertion (auto_increment): returned true');
        $this->assertEqual($this->arraise->lastError, '', 'Valid insertion (auto_increment): no errors');
        
        //Last insert id
        $id = $this->arraise->lastInsertedId;
        $this->assertTrue($id > 0, 'Valid insertion (auto_increment): lastInsertedId positive');
        
        //Make sure data is correct
        $this->assertEqual($this->arraise->dataset['users'][$id]['fname'], 'Joe', 'Valid insertion (auto_increment): data match');
        
        
        //Valid insertion (specific key)
        $this->assertTrue($this->arraise->insert('users', array('id' => 50, 'fname' => 'Joe', 'lname' => 'Calvasina')), 'Valid insertion (specific): returned true');
        $this->assertEqual($this->arraise->lastError, '', 'Valid insertion (specific): no errors');
        $this->assertEqual($this->arraise->dataset['users'][50]['fname'], 'Joe', 'Valid insertion (specific): data match');
        
        //Duplicate key
        $this->assertFalse($this->arraise->insert('users', array('id' => 2, 'fname' => 'Joe', 'lname' => 'Calvasina')), 'Duplicate key: returned false');
        $this->assertEqual($this->arraise->lastError, 'Duplicate primary key `2` in table `users`', 'Duplicate key: correct error message');
        
        //Table DNE
        $this->assertFalse($this->arraise->insert('groups', array('fname' => 'Joe', 'lname' => 'Calvasina')), 'Non-existant table: returned false');
        $this->assertEqual($this->arraise->lastError, 'Table `groups` does not exist', 'Non-existant table: correct error message');
        
        //No data
        $this->assertFalse($this->arraise->insert('', array()), 'No data: returned false');
        $this->assertEqual($this->arraise->lastError, 'Insufficient data', 'No data: correct error message');
        
    }
    
    function testUpdate() {
        //Valid update
        $this->assertTrue($this->arraise->update('users', array('fname' => 'Dawson'), array('id' => 1)), 'Valid update: returned true');
        $this->assertEqual($this->arraise->lastError, '', 'Valid update: no errors');
        $this->assertEqual($this->arraise->dataset['users'][1]['fname'], 'Dawson', 'Valid update: data match');
        
        //Limited update
        $this->arraise->update('users', array('fname' => 'Old'), 'age>5', 2);
        $this->assertEqual($this->arraise->lastAffectedRows, 2, 'Limited rows: correct count');
        
        //Table DNE
        $this->assertFalse($this->arraise->update('groups', array('fname' => 'Joe', 'lname' => 'Calvasina'), array('id' => 1)), 'Non-existant table: returned false');
        $this->assertEqual($this->arraise->lastError, 'Table `groups` does not exist', 'Non-existant table: correct error message');
        
        //No data
        $this->assertFalse($this->arraise->update(null, null), 'No data: returned false');
        $this->assertEqual($this->arraise->lastError, 'Insufficient data', 'No data: correct error message');
    }
    
    function testDelete() {
        //Valid update
        $this->assertTrue($this->arraise->delete('users', array('id' => 1)), 'Valid deletion: returned true');
        $this->assertEqual($this->arraise->lastError, '', 'Valid deletion: no errors');
        $this->assertFalse(array_key_exists(1, $this->arraise->dataset['users']), 'Valid deletion: data match');
        
        //Limited update
        $this->setUp();
        $this->arraise->delete('users', null, 2);
        $this->assertEqual($this->arraise->lastAffectedRows, 2, 'Limited rows: correct count');
        
        //Table DNE
        $this->assertFalse($this->arraise->delete('groups'), 'Non-existant table: returned false');
        $this->assertEqual($this->arraise->lastError, 'Table `groups` does not exist', 'Non-existant table: correct error message');
        
        //No data
        $this->assertFalse($this->arraise->delete(''), 'No data: returned false');
        $this->assertEqual($this->arraise->lastError, 'Insufficient data', 'No data: correct error message');
    }
}

$test = &new arraiseTest();
$test->run(new HtmlReporter());
?>