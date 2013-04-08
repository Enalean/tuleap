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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'bootstrap.php';
require_once (dirname(__FILE__) . '/../hooks/ExtractCrossRef.class.php');

/**
 * Description of ExtractCrossRefTest
 */
class ExtractCrossRefTest extends UnitTestCase {

    public function setUp() {
        $this->extractor = new ExtractCrossRef();
    }

    function testExtractsGroupNameFromProjectRepos() {
        $this->assertEqual('myproject', $this->extractor->getProjectName('/gitroot/myproject/stuff.git'));
        $this->assertEqual('gpig', $this->extractor->getProjectName('/gitolite/repositories/gpig/dalvik.git'));
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/codendi/gitolite/repositories/gpig/dalvik.git'));
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/codendi/gitroot/gpig/dalvik.git'));
    }
    
    function testExtractsTheNameAfterTheFirstOccurrenceOfRootPath() {
        $this->assertEqual('gitroot', $this->extractor->getProjectName('/gitroot/gitroot/stuff.git'));
    }

    function testExtractsGroupNameFromPersonalRepos() {
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/codendi/gitolite/repositories/gpig/u/manuel/dalvik.git'));
        $this->assertEqual('gpig', $this->extractor->getProjectName('/var/lib/codendi/gitroot/gpig/u/manuel/dalvik.git'));
    }
    
    function testExtractsGroupNameFromSymlinkedRepo() {
        $this->assertEqual('chene', $this->extractor->getProjectName('/data/codendi/gitroot/chene/gitshell.git'));
    }
    
    function testExtractsGroupNameThrowsAnExceptionWhenNoProjectNameFound() {
        $this->expectException('GitNoProjectFoundException');
        $this->extractor->getProjectName('/non_existing_path/dalvik.git');
    }
}
?>
