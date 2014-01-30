<?php

/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

require_once dirname(__FILE__).'/../../bootstrap.php';

class ACLUpdaterTest extends PHPUnit_Framework_TestCase {

    private $backend;

    private $acl_updater;

    private $path;

    protected function setUp() {
        parent::setUp();
        $this->backend     = $this->getMockBuilder('Backend')->disableOriginalConstructor()->getMock();
        $this->acl_updater = new Tuleap\ProFTPd\Admin\ACLUpdater($this->backend);
        $this->path        = realpath(dirname(__FILE__).'/../_fixtures/project_name');
        $this->http_user   = 'httpuser';
        $this->writers     = 'gpig-ftp_writers';
        $this->readers     = 'gpig-ftp_readers';
    }

    public function testRootDirectoryHaveDefaultAndEffectiveACL() {
        $this->backend->expects($this->at(0))->method('resetacl');
        $this->backend->expects($this->at(1))->method('modifyacl')->with('d:u:httpuser:rx,d:g:gpig-ftp_writers:rwx,d:g:gpig-ftp_readers:rx,u:httpuser:rx,g:gpig-ftp_writers:rwx,g:gpig-ftp_readers:rx', $this->path);

        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, $this->writers, $this->readers);
    }

    public function testAllDirectoriesHaveDefaultAndEffectiveACL() {
        $this->backend->expects($this->at(0))->method('resetacl')->with($this->path);
        $this->backend->expects($this->at(1))->method('modifyacl')->with('d:u:httpuser:rx,d:g:gpig-ftp_writers:rwx,d:g:gpig-ftp_readers:rx,u:httpuser:rx,g:gpig-ftp_writers:rwx,g:gpig-ftp_readers:rx', $this->path);
        $this->backend->expects($this->at(2))->method('resetacl')->with($this->path . '/SomeDirectory');
        $this->backend->expects($this->at(3))->method('modifyacl')->with('d:u:httpuser:rx,d:g:gpig-ftp_writers:rwx,d:g:gpig-ftp_readers:rx,u:httpuser:rx,g:gpig-ftp_writers:rwx,g:gpig-ftp_readers:rx', $this->path . '/SomeDirectory');

        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, $this->writers, $this->readers);
    }

    public function testItSetsAclOn4Elements() {
        $this->backend->expects($this->exactly(4))->method('resetacl');
        $this->backend->expects($this->exactly(4))->method('modifyacl');
        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, $this->writers, $this->readers);
    }

    public function testAllFilesHaveOnlyEffectiveACL() {
        $this->backend->expects($this->at(4))->method('resetacl')->with($this->path . '/SomeDirectory/AnotherFile');
        $this->backend->expects($this->at(5))->method('modifyacl')->with('u:httpuser:r,g:gpig-ftp_writers:rw,g:gpig-ftp_readers:r', $this->path . '/SomeDirectory/AnotherFile');
        $this->backend->expects($this->at(6))->method('resetacl')->with($this->path . '/SomeFile');
        $this->backend->expects($this->at(7))->method('modifyacl')->with('u:httpuser:r,g:gpig-ftp_writers:rw,g:gpig-ftp_readers:r', $this->path . '/SomeFile');

        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, $this->writers, $this->readers);
    }

    public function testItSetsACLOnDirectoryWhenNoReaders() {
        $this->backend->expects($this->at(1))->method('modifyacl')->with('d:u:httpuser:rx,d:g:gpig-ftp_writers:rwx,u:httpuser:rx,g:gpig-ftp_writers:rwx', $this->path);

        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, $this->writers, '');
    }

    public function testItSetsACLOnDirectoryWhenNoWriters() {
        $this->backend->expects($this->at(1))->method('modifyacl')->with('d:u:httpuser:rx,d:g:gpig-ftp_readers:rx,u:httpuser:rx,g:gpig-ftp_readers:rx', $this->path);

        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, '', $this->readers);
    }

    public function testItSetsACLOnDirectoryWhenNoReadersNorWriters() {
        $this->backend->expects($this->at(1))->method('modifyacl')->with('d:u:httpuser:rx,u:httpuser:rx', $this->path);

        $this->acl_updater->recursivelyApplyACL($this->path, $this->http_user, '', '');
    }
}
