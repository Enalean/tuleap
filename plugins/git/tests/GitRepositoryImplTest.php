<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


require_once(dirname(__FILE__).'/../include/constants.php');
require_once GIT_BASE_DIR. '/Git_Backend_Gitolite.class.php';
abstract class GitRepositoryImplTest extends TuleapTestCase {


    public function test_isNameValid() {
        $creator = $this->newCreator();
        $this->assertFalse($creator->isNameValid(''));
        $this->assertFalse($creator->isNameValid('/'));
        $this->assertFalse($creator->isNameValid('/jambon'));
        $this->assertFalse($creator->isNameValid('jambon/'));
        $this->assertTrue($creator->isNameValid('jambon'));
        $this->assertTrue($creator->isNameValid('jambon.beurre'));
        $this->assertTrue($creator->isNameValid('jambon-beurre'));
        $this->assertTrue($creator->isNameValid('jambon_beurre'));
        $this->assertFalse($creator->isNameValid('jambon/.beurre'));
        $this->assertFalse($creator->isNameValid('jambon..beurre'));
        $this->assertFalse($creator->isNameValid('jambon...beurre'));
        $this->assertFalse($creator->isNameValid(str_pad('name_with_more_than_255_chars_', 256, '_')));
        $this->assertFalse($creator->isNameValid('repo.git'));
        $this->assertFalse($creator->isNameValid('u/toto'));
        $this->assertTrue($creator->isNameValid('jambon/beurre'));
    }
    
    public function itAllowsLettersNumbersDotsUnderscoresSlashesAndDashes() {
        $creator = $this->newCreator();
        $this->assertEqual($creator->getAllowedCharsInNamePattern(), 'a-zA-Z0-9/_.-');
    }
    
    /**
     * @return GitRepositoryCreator 
     */
    abstract function newCreator();
 
}

class Git_Backend_Gitolite_isNameValidTest extends GitRepositoryImplTest {
    public function newCreator() {
        return new Git_Backend_Gitolite(mock('Git_GitoliteDriver'));
    }
}
?>
