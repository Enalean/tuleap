<?php
/*
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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Tuleap\ForgeConfigSandbox;
use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class FileSizeValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Svnlook
     */
    private $svnlook;
    /**
     * @var FileSizeValidator
     */
    private $validator;
    /**
     * @var SvnRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svnlook = Mockery::mock(Svnlook::class);

        $this->repository = SvnRepository::buildActiveRepository(10, 'foo', ProjectTestBuilder::aProject()->build());

        $this->validator = new FileSizeValidator(
            $this->svnlook,
            new NullLogger(),
        );
    }

    public function testItAllowsCommitWhenNoLimitIsDefined(): void
    {
        $this->svnlook->shouldReceive('getFilesize')->with($this->repository, 't1-r1', 'trunk/README.mkd')->andReturn(150);

        $this->validator->assertPathIsValid($this->repository, 't1-r1', 'U   trunk/README.mkd');
    }

    public function testItAllowsCommitWhenFileSizeIsUnderLimit(): void
    {
        ForgeConfig::set(FileSizeValidator::CONFIG_KEY, '200');

        $this->svnlook->shouldReceive('getFilesize')->with($this->repository, 't1-r1', 'trunk/README.mkd')->andReturn(150);

        $this->validator->assertPathIsValid($this->repository, 't1-r1', 'U   trunk/README.mkd');
    }

    public function testItBlockCommitWhenFileSizeIsUnderLimit(): void
    {
        ForgeConfig::set(FileSizeValidator::CONFIG_KEY, '1');

        $this->svnlook->shouldReceive('getFilesize')->with($this->repository, 't1-r1', 'trunk/README.mkd')->andReturn(2097152);

        $this->expectException(CommittedFileTooLargeException::class);

        $this->validator->assertPathIsValid($this->repository, 't1-r1', 'U   trunk/README.mkd');
    }

    public function testItDoesntFailOnDirectories(): void
    {
        ForgeConfig::set(FileSizeValidator::CONFIG_KEY, '200');

        $failed_command = Mockery::mock(Process::class, ['getErrorOutput' => "svnlook: E160017: Path 'trunk/aaa' is not a file"]);

        $exception = Mockery::mock(ProcessFailedException::class, ['getProcess' => $failed_command]);
        $this->svnlook->shouldReceive('getFilesize')->with($this->repository, 't1-r1', 'trunk/add/')->andThrow($exception);

        $this->validator->assertPathIsValid($this->repository, 't1-r1', 'A   trunk/add/');
    }

    public function testUnexpectedExceptionBubblesUp(): void
    {
        ForgeConfig::set(FileSizeValidator::CONFIG_KEY, '200');

        $failed_command = Mockery::mock(Process::class, ['getErrorOutput' => "svnlook: E160013: Path 'trunk/aaad' does not exist"]);

        $exception = Mockery::mock(ProcessFailedException::class, ['getProcess' => $failed_command]);
        $this->svnlook->shouldReceive('getFilesize')->with($this->repository, 't1-r1', 'trunk/add/')->andThrow($exception);

        $this->expectExceptionObject($exception);

        $this->validator->assertPathIsValid($this->repository, 't1-r1', 'A   trunk/add/');
    }
}
