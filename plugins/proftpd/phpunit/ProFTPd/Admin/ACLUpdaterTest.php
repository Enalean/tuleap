<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once dirname(__FILE__) . '/../../bootstrap.php';

class ACLUpdaterTest extends PHPUnit\Framework\TestCase
{

    private $backend;

    private $acl_updater;

    private $path;

    protected function setUp() : void
    {
        parent::setUp();
        $this->backend     = $this->getMockBuilder('Backend')->disableOriginalConstructor()->getMock();
        $this->acl_updater = new Tuleap\ProFTPd\Admin\ACLUpdater($this->backend);
        $this->path        = realpath(dirname(__FILE__) . '/../_fixtures/project_name');
        $this->http_user   = 'httpuser';
        $this->writers     = 'gpig-ftp_writers';
        $this->readers     = 'gpig-ftp_readers';
    }

    public function testAllDirectoriesHaveDefaultAndEffectiveACLAndAllFilesOnlyHaveEffectiveACL()
    {
        $root_path = $this->path;

        $this->backend->expects($this->any())->method('resetacl')->will($this->returnCallback(function ($path) use ($root_path) {
            switch ($path) {
                case $root_path:
                case $root_path . '/SomeFile':
                case $root_path . '/SomeDirectory':
                case $root_path . '/SomeDirectory/AnotherFile':
                    return true;
                default:
                    throw new Exception('invalid value for resetacl ' . $path);
            }
        }));

        $this->backend->expects($this->any())->method('modifyacl')->will($this->returnCallback(function ($acl, $path) use ($root_path) {
            switch ($path) {
                case $root_path:
                case $root_path . '/SomeDirectory':
                    if ($acl == 'd:u:httpuser:rwx,d:g:gpig-ftp_writers:rwx,d:g:gpig-ftp_readers:rx,u:httpuser:rwx,g:gpig-ftp_writers:rwx,g:gpig-ftp_readers:rx') {
                        break;
                    }
                    // Fall-through seems to be intentional for the test...
                case $root_path . '/SomeFile':
                case $root_path . '/SomeDirectory/AnotherFile':
                    if ($acl == 'u:httpuser:rw,g:gpig-ftp_writers:rw,g:gpig-ftp_readers:r') {
                        break;
                    }
                    // Fall-through seems to be intentional for the test...
                default:
                    throw new Exception('invalid value for modifyacl ' . $path . ' ' . $acl);
            }
        }));

        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, $this->writers, $this->readers);
        $this->addToAssertionCount(1);
    }

    public function testItSetsAclOn4Elements()
    {
        $this->backend->expects($this->exactly(4))->method('resetacl');
        $this->backend->expects($this->exactly(4))->method('modifyacl');
        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, $this->writers, $this->readers);
    }

    public function testItSetsACLOnDirectoryWhenNoReaders()
    {
        $this->backend->expects($this->at(1))->method('modifyacl')->with('d:u:httpuser:rwx,d:g:gpig-ftp_writers:rwx,u:httpuser:rwx,g:gpig-ftp_writers:rwx', $this->path);

        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, $this->writers, '');
    }

    public function testItSetsACLOnDirectoryWhenNoWriters()
    {
        $this->backend->expects($this->at(1))->method('modifyacl')->with('d:u:httpuser:rwx,d:g:gpig-ftp_readers:rx,u:httpuser:rwx,g:gpig-ftp_readers:rx', $this->path);

        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, '', $this->readers);
    }

    public function testItSetsACLOnDirectoryWhenNoReadersNorWriters()
    {
        $this->backend->expects($this->at(1))->method('modifyacl')->with('d:u:httpuser:rwx,u:httpuser:rwx', $this->path);

        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, '', '');
    }
}
