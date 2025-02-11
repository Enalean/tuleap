<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace TuleapCfg\Command\SiteDeploy;

use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;
use TuleapCfg\Command\ProcessFactory;
use Tuleap\TemporaryTestDirectory;
use TuleapCfg\Command\SiteDeploy\Gitolite3\SiteDeployGitolite3Hooks;

final class SiteDeployGitolite3HooksTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    private \PHPUnit\Framework\MockObject\MockObject&ProcessFactory $process_factory;
    private string $gitolite_base_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $base_dir                = $this->getTmpDir();
        $this->gitolite_base_dir = $base_dir . '/var/lib/gitolite';

        $this->process_factory = $this->createStub(ProcessFactory::class);
        $this->process_factory->method('getProcessWithoutTimeout')->willReturn(new Process(['/bin/true']));
    }

    public function testCreatePostReceiveHookSymlinkWorks(): void
    {
        mkdir($this->gitolite_base_dir . '/.gitolite/hooks/common', 0777, true);
        mkdir($this->gitolite_base_dir . '/.gitolite/conf', 0777, true);
        file_put_contents($this->gitolite_base_dir . '/.gitolite/conf/gitolite.conf', 'definitely not empty');

        $deploy = new SiteDeployGitolite3Hooks($this->process_factory, $this->gitolite_base_dir);
        $deploy->deploy(new NullLogger());

        self::assertFileExists($this->gitolite_base_dir . '/.gitolite/hooks/common/post-receive');
        self::assertFileExists($this->gitolite_base_dir . '/.gitolite/hooks/common/pre-receive');
    }

    public function testAbortWhenGitolite3NotSetup(): void
    {
        $deploy = new SiteDeployGitolite3Hooks($this->process_factory, $this->gitolite_base_dir);
        $deploy->deploy(new NullLogger());

        self::assertFileDoesNotExist($this->gitolite_base_dir . '/.gitolite/hooks/common/post-receive');
        self::assertFileDoesNotExist($this->gitolite_base_dir . '/.gitolite/hooks/common/pre-receive');
    }

    public function testSymlinkFailGivesError(): void
    {
        mkdir($this->gitolite_base_dir . '/.gitolite/conf', 0777, true);
        file_put_contents($this->gitolite_base_dir . '/.gitolite/conf/gitolite.conf', 'definitely not empty');

        $deploy = new SiteDeployGitolite3Hooks($this->process_factory, $this->gitolite_base_dir);
        $this->expectExceptionMessage('symlink(): No such file or directory');
        $deploy->deploy(new NullLogger());
    }
}
