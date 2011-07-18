<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

require_once(dirname(__FILE__).'/../include/GitRepository.class.php');
require_once(dirname(__FILE__).'/../include/Git_Backend_Gitolite.class.php');
Mock::generate('Git_Backend_Gitolite');
require_once(dirname(__FILE__).'/../include/GitBackend.class.php');
Mock::generate('GitBackend');

class GitRepositoryTest extends UnitTestCase {

    public function test_isNameValid() {
        $gitolite = new MockGit_Backend_Gitolite();
        $gitolite->setReturnValue('getAllowedCharsInNamePattern', 'a-zA-Z0-9/_-');
        
        $gitshell = new MockGitBackend();
        $gitshell->setReturnValue('getAllowedCharsInNamePattern', 'a-zA-Z0-9_-');
        
        $repo = new GitRepository();
        
        $repo->setBackend($gitolite);
        $this->assertFalse($repo->isNameValid(''));
        $this->assertTrue($repo->isNameValid('jambon'));
        $this->assertTrue($repo->isNameValid('jambon/beurre'));
        $this->assertFalse($repo->isNameValid(str_pad('name_with_more_than_255_chars_', 256, '_')));
        
        $repo->setBackend($gitshell);
        $this->assertFalse($repo->isNameValid(''));
        $this->assertTrue($repo->isNameValid('jambon'));
        $this->assertFalse($repo->isNameValid('jambon/beurre'));
        $this->assertFalse($repo->isNameValid(str_pad('name_with_more_than_255_chars_', 256, '_')));
    }


}

?>
