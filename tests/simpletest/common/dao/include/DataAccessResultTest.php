<?php
require_once('common/dao/include/DataAccessResult.class.php');
require_once('common/dao/include/DataAccessResultEmpty.class.php');
Mock::generatePartial('DataAccessResult', 'DataAccessResultTestVersion', array('current', 'valid', 'next', 'rewind', 'key'));




/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * Tests the class DataAccessResult
 */
class DataAccessResultTest extends TuleapTestCase {

    function testGetRow() {
        $dar = new DataAccessResultTestVersion($this);
        $dar->expectOnce('current');
        $dar->expectOnce('next');
        $tab = array('col' => 'value');
        $dar->setReturnReference('current', $tab);

        $this->assertIdentical($dar->getRow(), $tab);
    }


}

class FakeInstanciator {
    public $row;

    public function __construct(array $row) {
        $this->row = $row;
    }

    public function getInstanceData() {
        return $this->row;
    }
}

class DataAccessResult_InstanciatorTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->data_access = mock('DataAccess');
        $this->data_array  = array('a', 'b', 'c');
        stub($this->data_access)->fetch()->returns($this->data_array);
        stub($this->data_access)->numRows()->returns(1);
        $this->result = new stdClass();
        $this->dar = new DataAccessResult($this->data_access, $this->result);
    }

    function itReturnsADatabaseRow() {
        $this->assertEqual($this->dar->current(), $this->data_array);
    }

    function instanciate($row) {
        return new FakeInstanciator($row);
    }

    function itReturnsAnObjectInsteadOfARow() {
        $this->dar->instanciateWith(array($this, 'instanciate'));

        $fake_instanciator = $this->dar->current();
        $this->assertIsA($fake_instanciator, 'FakeInstanciator');
        $this->assertEqual($fake_instanciator->getInstanceData(), $this->data_array);
    }
}

class DataAccessResultEmptyTest extends TuleapTestCase {

    /** @var DataAccessResultEmptyDar */
    private $empty_dar;

    public function setUp() {
        parent::setUp();

        $this->empty_dar = new DataAccessResultEmpty();
    }

    public function itReturnsNullOnInstanciateWith() {
        $this->assertIsA($this->empty_dar->instanciateWith('test'), 'DataAccessResultEmpty');
    }
}

?>
