<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git;

use GitDriver;
use GitRepository;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\TemporaryTestDirectory;

final class GitDriverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    private $curDir;
    private $fixtures_path;
    private $destination_path;
    private $sourcePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->curDir        = getcwd();
        $this->fixtures_path = __DIR__ . '/_fixtures';

        $this->sourcePath = $this->getTmpDir() . '/source';
        mkdir($this->sourcePath, 0770);
        $this->destination_path = $this->getTmpDir() . '/destination';
        mkdir($this->destination_path, 0770);
    }

    protected function tearDown(): void
    {
        chdir($this->curDir);
        parent::tearDown();
    }

    public function testForkRepo(): void
    {
        $srcPath = $this->getTmpDir() . '/tmp/repo.git';
        $dstPath = $this->getTmpDir() . '/tmp/fork.git';

        mkdir($srcPath, 0770, true);
        @exec('GIT_DIR=' . $srcPath . ' ' . \Git_Exec::getGitCommand() . ' --bare init --initial-branch=master --shared=group');

        $driver = new GitDriver();
        $driver->fork($srcPath, $dstPath);

        $this->assertFileExists($dstPath . '/HEAD');
        $this->assertStringEqualsFile($dstPath . '/description', 'Default description for this project' . PHP_EOL);
    }

    public function testSetRepositoryAccessPublic(): void
    {
        $srcPath = $this->getTmpDir() . '/tmp/repo.git';

        mkdir($srcPath, 0770, true);
        @exec('GIT_DIR=' . $srcPath . ' ' . \Git_Exec::getGitCommand() .  ' --bare init --initial-branch=master --shared=group');

        $driver = new GitDriver();
        $driver->setRepositoryAccess($srcPath, GitRepository::PUBLIC_ACCESS);

        clearstatcache();
        $stat = stat($srcPath);
        //system('/bin/ls -ld '.$srcPath);
        $this->assertEquals(base_convert((string) $stat['mode'], 10, 8), 42775);
    }

    public function testSetRepositoryAccessPrivate(): void
    {
        $srcPath = $this->getTmpDir() . '/tmp/repo.git';

        mkdir($srcPath, 0770, true);
        @exec('GIT_DIR=' . $srcPath . ' ' . \Git_Exec::getGitCommand() . ' --bare init --initial-branch=master --shared=group');

        $driver = new GitDriver();
        $driver->setRepositoryAccess($srcPath, GitRepository::PRIVATE_ACCESS);

        clearstatcache();
        $stat = stat($srcPath);
        //system('/bin/ls -ld '.$srcPath);
        $this->assertEquals(base_convert((string) $stat['mode'], 10, 8), 42770);
    }

    public function testForkRepoUnixPermissions(): void
    {
        $srcPath = $this->getTmpDir() . '/tmp/repo.git';
        $dstPath = $this->getTmpDir() . '/tmp/fork.git';

        mkdir($srcPath, 0770, true);
        @exec('GIT_DIR=' . $srcPath . ' ' . \Git_Exec::getGitCommand() . ' --bare init --initial-branch=master --shared=group');

        $driver = new GitDriver();
        $driver->fork($srcPath, $dstPath);

        clearstatcache();
        $stat = stat($dstPath . '/HEAD');
        //system('/bin/ls -ld '.$dstPath.'/HEAD');
        $this->assertEquals(base_convert((string) $stat['mode'], 10, 8), 100664, '/HEAD must be writable by group');

        $stat = stat($dstPath . '/refs');
        //system('/bin/ls -ld '.$dstPath.'/refs');
        $this->assertEquals(base_convert((string) $stat['mode'], 10, 8), 42775, '/refs must have setgid bit');

        $stat = stat($dstPath . '/refs/heads');
        $this->assertEquals(base_convert((string) $stat['mode'], 10, 8), 42775, '/refs/heads must have setgid bit');
    }

    public function testActivateHook(): void
    {
        mkdir($this->getTmpDir() . '/hooks', 0770, true);
        copy($this->fixtures_path . '/hooks/post-receive', $this->getTmpDir() . '/hooks/blah');

        $driver = new GitDriver();
        $driver->activateHook('blah', $this->getTmpDir());

        $this->assertEquals(substr(sprintf('%o', fileperms($this->getTmpDir() . '/hooks/blah')), -4), '0755');
    }

    public function testSetConfigSimple(): void
    {
        copy($this->fixtures_path . '/config', $this->getTmpDir() . '/config');

        $driver = new GitDriver();
        $driver->setConfig($this->getTmpDir(), 'hooks.showrev', 'abcd');

        $config = parse_ini_file($this->getTmpDir() . '/config', true);
        $this->assertEquals($config['hooks']['showrev'], 'abcd');
    }

    public function testSetConfigComplex(): void
    {
        copy($this->fixtures_path . '/config', $this->getTmpDir() . '/config');

        $val = "t=%s; " . \Git_Exec::getGitCommand() . " log --name-status --pretty='format:URL:    https://codendi.org/plugins/git/index.php/1750/view/290/?p=git.git&a=commitdiff&h=%%H%%nAuthor: %%an <%%ae>%%nDate:   %%aD%%n%%n%%s%%n%%b' \$t~1..\$t";

        $driver = new GitDriver();
        $driver->setConfig($this->getTmpDir(), 'hooks.showrev', $val);

        $config = parse_ini_file($this->getTmpDir() . '/config', true);
        $this->assertEquals($config['hooks']['showrev'], 't=%s; ' . \Git_Exec::getGitCommand() . ' log --name-status --pretty=\'format:URL:    https://codendi.org/plugins/git/index.php/1750/view/290/?p=git.git&a=commitdiff&h=%%H%%nAuthor: %%an <%%ae>%%nDate:   %%aD%%n%%n%%s%%n%%b\' $t~1..$t');
    }

    public function testSetConfigWithSpace(): void
    {
        copy($this->fixtures_path . '/config', $this->getTmpDir() . '/config');

        $driver = new GitDriver();
        $driver->setConfig($this->getTmpDir(), 'hooks.showrev', '[MyVal] ');

        $config = parse_ini_file($this->getTmpDir() . '/config', true);
        $this->assertEquals($config['hooks']['showrev'], '[MyVal] ');
    }

    public function testSetEmptyConfig(): void
    {
        copy($this->fixtures_path . '/config', $this->getTmpDir() . '/config');

        $driver = new GitDriver();
        $driver->setConfig($this->getTmpDir(), 'hooks.showrev', '');

        $config = parse_ini_file($this->getTmpDir() . '/config', true);
        $this->assertEquals($config['hooks']['showrev'], '');
    }
}
