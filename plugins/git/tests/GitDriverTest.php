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

require_once 'bootstrap.php';


class GitDriverTest extends UnitTestCase {

    public function setUp() {
        $this->curDir = getcwd();
        $this->fixturesPath = dirname(__FILE__).'/_fixtures';
    }

    public function tearDown() {
        chdir($this->curDir);
        @unlink($this->fixturesPath.'/tmp/hooks/blah');
        @unlink($this->fixturesPath.'/tmp/config');
        @exec('/bin/rm -rf '.$this->fixturesPath.'/tmp/repo.git');
        @exec('/bin/rm -rf '.$this->fixturesPath.'/tmp/fork.git');
    }

    public function testInitBareRepo() {
        $path = $this->fixturesPath.'/tmp/repo.git';
        $driver = new GitDriver();
        mkdir($path, 0770, true);
        chdir($path);
        $driver->init(true);
        $this->assertTrue(file_exists($path.'/HEAD'));
        $this->assertEqual(file_get_contents($path.'/description'), 'Default description for this project'.PHP_EOL);
    }

    public function testInitStdRepo() {
        $path = $this->fixturesPath.'/tmp/repo.git';
        $driver = new GitDriver();
        mkdir($path, 0770, true);
        chdir($path);
        $driver->init(false);
        $this->assertTrue(file_exists($path.'/.git/HEAD'));
    }

    public function testForkRepo() {
        $srcPath = $this->fixturesPath.'/tmp/repo.git';
        $dstPath = $this->fixturesPath.'/tmp/fork.git';

        mkdir($srcPath, 0770, true);
        @exec('GIT_DIR='.$srcPath.' git --bare init --shared=group');

        $driver = new GitDriver();
        $driver->fork($srcPath, $dstPath);
        
        $this->assertTrue(file_exists($dstPath.'/HEAD'));
        $this->assertEqual(file_get_contents($dstPath.'/description'), 'Default description for this project'.PHP_EOL);
    }

    public function testSetRepositoryAccessPublic() {
        $srcPath = $this->fixturesPath.'/tmp/repo.git';

        mkdir($srcPath, 0770, true);
        @exec('GIT_DIR='.$srcPath.' git --bare init --shared=group');

        $driver = new GitDriver();
        $driver->setRepositoryAccess($srcPath, GitRepository::PUBLIC_ACCESS);
        
        clearstatcache();
        $stat = stat($srcPath);
        //system('/bin/ls -ld '.$srcPath);
        $this->assertEqual(base_convert($stat['mode'], 10, 8), 42775);
    }

    public function testSetRepositoryAccessPrivate() {
        $srcPath = $this->fixturesPath.'/tmp/repo.git';

        mkdir($srcPath, 0770, true);
        @exec('GIT_DIR='.$srcPath.' git --bare init --shared=group');

        $driver = new GitDriver();
        $driver->setRepositoryAccess($srcPath, GitRepository::PRIVATE_ACCESS);
        
        clearstatcache();
        $stat = stat($srcPath);
        //system('/bin/ls -ld '.$srcPath);
        $this->assertEqual(base_convert($stat['mode'], 10, 8), 42770);
    }

    public function testForkRepoUnixPermissions() {
        $srcPath = $this->fixturesPath.'/tmp/repo.git';
        $dstPath = $this->fixturesPath.'/tmp/fork.git';

        mkdir($srcPath, 0770, true);
        @exec('GIT_DIR='.$srcPath.' git --bare init --shared=group');

        $driver = new GitDriver();
        $driver->fork($srcPath, $dstPath);
        
        clearstatcache();
        $stat = stat($dstPath.'/HEAD');
        //system('/bin/ls -ld '.$dstPath.'/HEAD');
        $this->assertEqual(base_convert($stat['mode'], 10, 8), 100664, '/HEAD must be writable by group');

        $stat = stat($dstPath.'/refs');
        //system('/bin/ls -ld '.$dstPath.'/refs');
        $this->assertEqual(base_convert($stat['mode'], 10, 8), 42775, '/refs must have setgid bit');

        $stat = stat($dstPath.'/refs/heads');
        $this->assertEqual(base_convert($stat['mode'], 10, 8), 42775, '/refs/heads must have setgid bit');
    }
   
    public function testActivateHook() {
        copy($this->fixturesPath.'/hooks/post-receive', $this->fixturesPath.'/tmp/hooks/blah');

        $driver = new GitDriver();
        $driver->activateHook('blah', $this->fixturesPath.'/tmp');

        $this->assertTrue(is_executable($this->fixturesPath.'/tmp/hooks/blah'));
    }

    public function testSetConfigSimple() {
        copy($this->fixturesPath.'/config', $this->fixturesPath.'/tmp/config');

        $driver = new GitDriver();
        $driver->setConfig($this->fixturesPath.'/tmp', 'hooks.showrev', 'abcd');

        $config = parse_ini_file($this->fixturesPath.'/tmp/config', true);
        $this->assertEqual($config['hooks']['showrev'], 'abcd');
    }

    public function testSetConfigComplex() {
        copy($this->fixturesPath.'/config', $this->fixturesPath.'/tmp/config');

        $val = "t=%s; git log --name-status --pretty='format:URL:    https://codendi.org/plugins/git/index.php/1750/view/290/?p=git.git&a=commitdiff&h=%%H%%nAuthor: %%an <%%ae>%%nDate:   %%aD%%n%%n%%s%%n%%b' \$t~1..\$t";

        $driver = new GitDriver();
        $driver->setConfig($this->fixturesPath.'/tmp', 'hooks.showrev', $val);

        $config = parse_ini_file($this->fixturesPath.'/tmp/config', true);
        $this->assertEqual($config['hooks']['showrev'], 't=%s; git log --name-status --pretty=\'format:URL:    https://codendi.org/plugins/git/index.php/1750/view/290/?p=git.git&a=commitdiff&h=%%H%%nAuthor: %%an <%%ae>%%nDate:   %%aD%%n%%n%%s%%n%%b\' $t~1..$t');
    }

    public function testSetConfigWithSpace() {
        copy($this->fixturesPath.'/config', $this->fixturesPath.'/tmp/config');

        $driver = new GitDriver();
        $driver->setConfig($this->fixturesPath.'/tmp', 'hooks.showrev', '[MyVal] ');

        $config = parse_ini_file($this->fixturesPath.'/tmp/config', true);
        $this->assertEqual($config['hooks']['showrev'], '[MyVal] ');
    }

    public function testSetEmptyConfig() {
        copy($this->fixturesPath.'/config', $this->fixturesPath.'/tmp/config');

        $driver = new GitDriver();
        $driver->setConfig($this->fixturesPath.'/tmp', 'hooks.showrev', '');

        $config = parse_ini_file($this->fixturesPath.'/tmp/config', true);
        $this->assertEqual($config['hooks']['showrev'], '');
    }
}
?>