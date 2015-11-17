<?php
/**
 * Copyright (c) Sogilis, 2015. All Rights Reserved.
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

class SVNXMLImporterTest extends TuleapTestCase {
    private $importer;
    private $temp_project_dir;
    private $old_homedir;

    public function setUp() {
        parent::setUp();

        // Create a temporary home, else svnadmin will complain it cannot access
        // its ~/.svnadmin directory
        $this->old_homedir = getenv('HOME');
        putenv("HOME=" . parent::getTmpDir());

        $tmp_dir = parent::getTmpDir();
        ForgeConfig::store();
        ForgeConfig::set('svn_prefix', $tmp_dir);
        $this->importer = SVNXMLImporter::build(mock('Logger'));
        $this->temp_project_dir = $tmp_dir . DIRECTORY_SEPARATOR . 'test_project';
        $return_status = 0;
        $out = array();
        exec('svnadmin create ' . escapeshellarg($this->temp_project_dir) . ' 2>&1', $out, $return_status);
        $this->assertEqual(0, $return_status, implode($out));
    }

    public function tearDown() {
        parent::tearDown();
        ForgeConfig::restore();
        putenv("HOME=" . $this->old_homedir);
    }

    public function itShouldImportOneRevisionAndReturnTrue() {
        copy(__DIR__ . '/_fixtures/svn_2revs.dump', parent::getTmpDir() . DIRECTORY_SEPARATOR . 'svn.dump');
        $project = new Project(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml_element = new SimpleXMLElement('<project><svn dump-file="svn.dump"/></project>');
        $res = $this->importer->import($project, $xml_element, parent::getTmpDir());
        $this->assertRevision(1, $this->temp_project_dir);
        $this->assertTrue($res);
    }

    public function itShouldDoNothingIfNoSvnNodeAndReturnTrue() {
        $project = new Project(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml_element = new SimpleXMLElement('<project></project>');
        $res = $this->importer->import($project, $xml_element, 'an_extraction_path');
        $this->assertRevision(0, $this->temp_project_dir);
        $this->assertTrue($res);
    }

    public function itShouldFailToImportIfTheSVNFileIsNotPresentAndReturnFalse() {
        $project = new Project(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml_element = new SimpleXMLElement('<project><svn dump-file="svn.dump"/></project>');
        $res = $this->importer->import($project, $xml_element, parent::getTmpDir());
        $this->assertRevision(0, $this->temp_project_dir);
        $this->assertFalse($res);
    }

    private function assertRevision($expected, $svn_dir) {
        $svn_arg = escapeshellarg("file://$svn_dir");
        $cmd_line = "(svn info $svn_arg | grep Revision) 2>&1";
        $last_changed_revision = shell_exec($cmd_line);
        $this->assertEqual("Revision: $expected\n", $last_changed_revision);
    }
}

?>
