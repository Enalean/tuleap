<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__).'/../../../../../../src/common/frs/FRSPackageFactory.class.php');
Mock::generatePartial('FRSPackageFactory', 'FRSPackageFactoryTestVersion', array('_getFRSPackageDao'));
require_once(dirname(__FILE__).'/../../../../../../src/common/dao/include/DataAccess.class.php');
Mock::generatePartial('DataAccess', 'DataAccessTestVersion', array('DataAccess'));
require_once(dirname(__FILE__).'/../../../../../../src/common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once(dirname(__FILE__).'/../../../../../../src/common/dao/FRSPackageDao.class.php');
Mock::generatePartial('FRSPackageDao', 'FRSPackageDaoTestVersion', array('retrieve'));

class FRSPackageFactoryTest extends UnitTestCase {

    function testGetFRSPackageFromDb() {
        $packageArray1 = array('package_id'       => 1,
                               'group_id'         => 1,
                               'name'             => 'pkg1',
                               'status_id'        => 2,
                               'rank'             => null,
                               'approve_license'  => null,
                               'data_array'       => null,
                               'package_releases' => null,
                               'error_state'      => null,
                               'error_message'    => null
                               );
        $package1 = FRSPackageFactory::getFRSPackageFromArray($packageArray1);
        $dar1 = new MockDataAccessResult($this);
        $dar1->setReturnValue('isError', false);
        $dar1->setReturnValue('current', $packageArray1);
        $dar1->setReturnValueAt(0, 'valid', true);
        $dar1->setReturnValueAt(1, 'valid', false);
        $dar1->setReturnValue('rowCount', 1);

        $packageArray2 = array('package_id'       => 2,
                               'group_id'         => 2,
                               'name'             => 'pkg2',
                               'status_id'        => 1,
                               'rank'             => null,
                               'approve_license'  => null,
                               'data_array'       => null,
                               'package_releases' => null,
                               'error_state'      => null,
                               'error_message'    => null
                               );
        $package2 = FRSPackageFactory::getFRSPackageFromArray($packageArray2);
        $dar2 = new MockDataAccessResult($this);
        $dar2->setReturnValue('isError', false);
        $dar2->setReturnValue('current', $packageArray2);
        $dar2->setReturnValueAt(0, 'valid', true);
        $dar2->setReturnValueAt(1, 'valid', false);
        $dar2->setReturnValue('rowCount', 1);

        $dar3 = new MockDataAccessResult($this);
        $dar3->setReturnValue('isError', false);
        $dar3->setReturnValue('current', array());
        $dar3->setReturnValueAt(0, 'valid', true);
        $dar3->setReturnValueAt(1, 'valid', false);
        $dar3->setReturnValue('rowCount', 0);

        $dao = new FRSPackageDaoTestVersion();
        $da = new DataAccessTestVersion();
        $dao->da = $da;
        $dao->setReturnValue('retrieve', $dar1, array('SELECT p.*  FROM frs_package AS p  WHERE  p.package_id = 1  ORDER BY rank DESC LIMIT 1'));
        $dao->setReturnValue('retrieve', $dar2, array('SELECT p.*  FROM frs_package AS p  WHERE  p.package_id = 2  AND p.status_id != 0  ORDER BY rank DESC LIMIT 1'));
        $dao->setReturnValue('retrieve', $dar3);

        $PackageFactory = new FRSPackageFactoryTestVersion();
        $PackageFactory->setReturnValue('_getFRSPackageDao', $dao);
        $this->assertEqual($PackageFactory->getFRSPackageFromDb(1, null, 0x0001), $package1);
        $this->assertEqual($PackageFactory->getFRSPackageFromDb(2), $package2);
    }
}
?>