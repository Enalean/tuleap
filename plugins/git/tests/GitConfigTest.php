<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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

class GitConfigTest extends TuleapTestCase {

    private $gitconfig;
    private $plugin;

    public function setUp() {
        parent::setUp();

        $this->plugin    = mock('gitPlugin');
        $this->driver    = mock('GitDriver');
        $this->gitconfig = new GitConfig($this->plugin, $this->driver);
    }

    public function itReturnsTrueWhenConfigParameterIs1() {
        stub($this->driver)->getGitVersion()->returns("1.7.4");
        stub($this->plugin)->getConfigurationParameter('enable_online_edit')->returns('1');

        $this->assertTrue($this->gitconfig->isOnlineEditEnabled());
    }

    public function itReturnsFalseWhenConfigParameterIS0() {
        stub($this->driver)->getGitVersion()->returns("1.7.4");
        stub($this->plugin)->getConfigurationParameter('enable_online_edit')->returns('0');

        $this->assertFalse($this->gitconfig->isOnlineEditEnabled());
    }

    public function itReturnsFalseWhenGitIsLowerThan1_7_4() {
        stub($this->driver)->getGitVersion()->returns("1.7.3");
        stub($this->plugin)->getConfigurationParameter('enable_online_edit')->returns('1');

        $this->assertFalse($this->gitconfig->isOnlineEditEnabled());
    }
}