<?php
/**
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once (dirname(__FILE__).'/../include/GitActions.class.php');
Mock::generatePartial('GitActions', 'GitActionsTestVersion', array('getController', 'getText', 'addData', 'getGitRepository'));
require_once (dirname(__FILE__).'/../include/Git.class.php');
Mock::generate('Git');
require_once (dirname(__FILE__).'/../include/GitRepository.class.php');
Mock::generate('GitRepository');

class GitActionsTest extends UnitTestCase {

    function testRepoManagement() {
        $gitAction = new GitActionsTestVersion();
        $git = new MockGit($this);
        $gitAction->setReturnValue('getController', $git);
        $gitRepository = new MockGitRepository($this);
        $gitAction->setReturnValue('getGitRepository', $gitRepository);
        $this->assertFalse($gitAction->repoManagement(1, null));
        $this->assertTrue($gitAction->repoManagement(1, 1));
        $git->expectOnce('addError');
    }

}

?>