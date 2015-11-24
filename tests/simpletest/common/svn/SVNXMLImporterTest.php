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
    private $svn_notification_dao;
    private $svn_accessfile_dao;
    private $old_homedir;

    public function setUp() {
        parent::setUp();

        $dao1 = mock("SvnNotificationDao");
        $dao1->setReturnValue('setSvnMailingList', true);
        $this->svn_notification_dao = $dao1;

        $dao2 = mock("SVN_AccessFile_DAO");
        $dao2->setReturnValue('saveNewAccessFileVersionInProject', true);
        $this->svn_accessfile_dao = $dao2;

        $this->importer = new SVNXMLImporter(mock('Logger'), $this->svn_notification_dao, $this->svn_accessfile_dao);

        // Create a temporary home, else svnadmin will complain it cannot access
        // its ~/.svnadmin directory
        $this->old_homedir = getenv('HOME');
        putenv("HOME=" . parent::getTmpDir());


        // Create a temp dir with subversion repositories
        $tmp_dir = parent::getTmpDir();
        ForgeConfig::store();
        ForgeConfig::set('svn_prefix', $tmp_dir);
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

    public function itShouldImportOneRevision() {
        copy(__DIR__ . '/_fixtures/svn_2revs.dump', parent::getTmpDir() . DIRECTORY_SEPARATOR . 'svn.dump');
        $project = new Project(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml_element = new SimpleXMLElement('<project><svn dump-file="svn.dump"/></project>');
        $this->importer->import($project, $xml_element, parent::getTmpDir());
        $this->assertRevision(1, $this->temp_project_dir);
    }

    public function itShouldDoNothingIfNoSvnNode() {
        $project = new Project(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml_element = new SimpleXMLElement('<project></project>');
        $this->importer->import($project, $xml_element, 'an_extraction_path');
        $this->assertRevision(0, $this->temp_project_dir);
    }

    public function itShouldFailToImportIfTheSVNFileIsNotPresent() {
        $project = new Project(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml_element = new SimpleXMLElement('<project><svn dump-file="svn.dump"/></project>');
        try {
            $this->importer->import($project, $xml_element, parent::getTmpDir());
            $this->assertTrue(false);
        } catch (SVNXMLImporterException $e) {
            $this->assertTrue(true);
        }
        $this->assertRevision(0, $this->temp_project_dir);
    }

    public function itShouldImportNotifications(){
        $project = new Project(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml_document = <<<XML
            <project>
                <svn>
                    <notification path="/trunk" emails="test1@domain1, test2@domain2"/>
                    <notification path="/tags" emails="tags@domain3"/>
                </svn>
            </project>
XML;
        $this->importer->import(
            $project,
            new SimpleXMLElement($xml_document),
            parent::getTmpDir());

        $dao = $this->svn_notification_dao;
        $dao->expectAt(0, 'setSvnMailingList', array(123, "test1@domain1, test2@domain2", "/trunk"));
        $dao->expectAt(1, 'setSvnMailingList', array(123, "tags@domain3", "/tags"));
        $dao->expectCallCount('setSvnMailingList', 2);
    }

    public function itShouldImportSvnAccessFile() {
        $project = new Project(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $access_file = "[groups]\nmembers = usernameTOTO123\n\n\n[/]\n* = r\n@members = rw\n";
        $xml_document = <<<XML
            <project>
                <svn>
                    <access-file>$access_file</access-file>
                </svn>
            </project>
XML;
        $this->importer->import(
            $project,
            new SimpleXMLElement($xml_document),
            parent::getTmpDir());

        $dao = $this->svn_accessfile_dao;
        $dao->expectCallCount('saveNewAccessFileVersionInProject', 1);
        $dao->expectAt(0, 'saveNewAccessFileVersionInProject', array(123, $access_file));

        $svnroot = $project->getSVNRootPath();
        $accessfile = "$svnroot/.SVNAccessFile";
        $found = strstr(file_get_contents($accessfile), "TOTO123") !== false;
        $this->assertTrue($found);
    }

    private function assertRevision($expected, $svn_dir) {
        $svn_arg = escapeshellarg("file://$svn_dir");
        $cmd_line = "(svn info $svn_arg | grep Revision) 2>&1";
        $last_changed_revision = shell_exec($cmd_line);
        $this->assertEqual("Revision: $expected\n", $last_changed_revision);
    }
}

?>
