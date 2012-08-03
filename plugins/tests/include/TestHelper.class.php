<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/dao/include/DataAccessResult.class.php';

Mock::generate('DataAccessResult');

/**
 * Various tools to assist test in her duty
 */
class TestHelper {
    /**
     * Generate a partial mock.
     *
     * @param String $className The class to mock
     * @param Array  $methods   The list of methods to mock
     * 
     * @return Object
     */
    public static function getPartialMock($className, $methods) {
        $partialName = $className.'_Partial'.uniqid();
        Mock::generatePartial($className, $partialName, $methods);
        return new $partialName();
    }
    
    /**
     * Generate a DataAccessResult
     *
     * @return Mock
     */
    public static function arrayToDar() {
        return self::argListToDar(func_get_args());
    }

    public static function argListToDar($argList) {
        return new FakeDataAccessResult($argList);
    }

    public static function emptyDar() {
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('valid',    false);
        $dar->setReturnValue('current',  false);
        $dar->setReturnValue('rowCount', 0);
        $dar->setReturnValue('count', 0);
        $dar->setReturnValue('isError',  false);
        $dar->setReturnValue('getRow',   false);
        return $dar;
    }
    
    public static function errorDar() {
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('valid',    false);
        $dar->setReturnValue('current',  false);
        $dar->setReturnValue('rowCount', 0);
        $dar->setReturnValue('isError',  true);
        $dar->setReturnValue('getRow',   false);
        return $dar;
    }
}

class FakeDataAccessResult implements IProvideDataAccessResult {
    private $index = 0;
    private $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function count() {
        return count($this->data);
    }

    public function current() {
        return $this->data[$this->index];
    }

    public function getRow() {
        if (isset($this->data[$this->index])) {
            return $this->data[$this->index++];
        }
        return false;
    }

    public function isError() {
        return false;
    }

    public function key() {
        return $this->index;
    }

    public function next() {
        $this->index++;
    }

    public function rewind() {
        $this->index = 0;
    }

    public function rowCount() {
        return $this->count();
    }

    public function valid() {
        return $this->index < $this->count();
    }
}

?>
