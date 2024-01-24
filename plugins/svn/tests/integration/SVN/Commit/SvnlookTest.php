<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVN\Commit;

use ForgeConfig;
use Symfony\Component\Process\Process;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use function PHPUnit\Framework\assertEquals;

final class SvnlookTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    // Cannot use ForgeConfigSandbox here, you will generate an instance of CodendiDataAccess with invalid DB creds.
    // It will break in other tests as soon as you try to access something with CodendiDataAccess
    // use ForgeConfigSandbox;

    private string $working_copy;
    private string $svnrepo;
    private string|bool $initial_sys_data_dir;

    protected function setUp(): void
    {
        parent::setUp();

        $project         = ProjectTestBuilder::aProject()->build();
        $repository_name = 'somerepo';
        $this->svnrepo   = $this->getTmpDir() . '/svn_plugin/' . $project->getID() . '/' . $repository_name;

        mkdir(dirname($this->svnrepo), 0755, true);

        $this->working_copy = $this->getTmpDir() . '/working_copy';

        (new Process(['svnadmin', 'create', $this->svnrepo]))->mustRun();
        (new Process(['svn', 'mkdir', '-m', 'Base layout', "file://$this->svnrepo/trunk"]))->mustRun();
        (new Process(['svn', 'checkout', "file://$this->svnrepo", $this->working_copy]))->mustRun();

        $this->initial_sys_data_dir = ForgeConfig::get('sys_data_dir');
        ForgeConfig::set('sys_data_dir', $this->getTmpDir());
    }

    protected function tearDown(): void
    {
        ForgeConfig::set('sys_data_dir', $this->initial_sys_data_dir);
    }

    /**
     * @requires PHP >= 7.4
     */
    public function testItGetFileSizeDuringTransaction(): void
    {
        $data = 'abc';
        symlink(__DIR__ . '/_fixtures/pre-commit.php', $this->svnrepo . '/hooks/pre-commit');

        file_put_contents($this->working_copy . '/trunk/README', $data);
        (new Process(['svn', 'add', "$this->working_copy/trunk/README"]))->mustRun();
        (new Process(['svn', 'commit', '-m', "add a file", "$this->working_copy/trunk/README"]))->mustRun();

        assertEquals(strlen($data), (int) file_get_contents($this->svnrepo . '/filesize'));
    }
}
