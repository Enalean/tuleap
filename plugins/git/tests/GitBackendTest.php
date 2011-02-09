<?php
/*
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../include/GitBackend.class.php';
Mock::generatePartial('GitBackend', 'GitBackendTestVersion', array('getDao', 'getDriver'));

Mock::generate('GitDriver');


class GitBackendTest extends UnitTestCase {

    public function setUp() {
        $this->fixturesPath = dirname(__FILE__).'/_fixtures';
    }

    public function tearDown() {
        @unlink($this->fixturesPath.'/tmp/post-receive');
    }

    public function testIncludePostReceive() {
        // Copy reference hook to temporay path
        $hookPath = $this->fixturesPath.'/tmp/post-receive';
        copy($this->fixturesPath.'/hooks/post-receive', $hookPath);
             
        $driver = new MockGitDriver($this);

        $backend = new GitBackendTestVersion($this);
        $backend->setReturnValue('getDriver', $driver);

        $backend->deployPostReceive($hookPath);

        // verify that post-receive codendi hook is added
        $expect = '. '.$GLOBALS['sys_pluginsroot'].'git/hooks/post-receive 2>/dev/null';
        $lineFound = false;
        $lines = file($hookPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, $expect) !== false) {
                $lineFound = true;
            }
        }
        $this->assertTrue($lineFound, "post-receive hook must contains $expect");
    }

    public function testPostReceiveIsExecutable() {
        // Copy reference hook to temporay path
        $hookPath = $this->fixturesPath.'/tmp/post-receive';
        copy($this->fixturesPath.'/hooks/post-receive', $hookPath);

        $driver = new MockGitDriver($this);
        $driver->expectOnce('activateHook', array('post-receive', dirname($hookPath)));

        $backend = new GitBackendTestVersion($this);
        $backend->setReturnValue('getDriver', $driver);

        $backend->deployPostReceive($hookPath);
    }
}

?>
