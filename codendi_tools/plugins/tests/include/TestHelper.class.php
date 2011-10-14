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
        $partialName = $className.'Partial'.uniqid();
        Mock::generatePartial($className, $partialName, $methods);
        return new $partialName();
    }
    
    /**
     * Generate a DataAccessResult
     *
     * @return Mock
     */
    public static function arrayToDar() {
        $argList  = func_get_args();
        $dar      = new MockDataAccessResult();
        $rowCount = 0;
        foreach ($argList as $row) {
            $dar->setReturnValueAt($rowCount, 'valid', true);
            $dar->setReturnValueAt($rowCount, 'current', $row);
            $rowCount++;
        }
        $dar->setReturnValueAt($rowCount, 'valid', false);
        $dar->setReturnValue('rowCount', $rowCount);
        $dar->setReturnValue('isError', false);
        return $dar;
    }
}

?>