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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Tuleap\ForgeConfigSandbox;
use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class FileSizeValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private Svnlook&MockObject $svnlook;
    private FileSizeValidator $validator;
    private SvnRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svnlook = $this->createMock(Svnlook::class);

        $this->repository = SvnRepository::buildActiveRepository(10, 'foo', ProjectTestBuilder::aProject()->build());

        $this->validator = new FileSizeValidator(
            $this->svnlook,
            new NullLogger(),
        );
    }

    public function testItAllowsCommitWhenNoLimitIsDefined(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->assertPathIsValid($this->repository, 't1-r1', 'U   trunk/README.mkd');
    }

    public function testItAllowsCommitWhenFileSizeIsUnderLimit(): void
    {
        ForgeConfig::set(FileSizeValidator::CONFIG_KEY, '200');

        $this->svnlook->expects(self::atLeast(1))->method('getFilesize')->with($this->repository, 't1-r1', 'trunk/README.mkd')->willReturn(150);

        $this->validator->assertPathIsValid($this->repository, 't1-r1', 'U   trunk/README.mkd');
    }

    public function testItBlockCommitWhenFileSizeIsUnderLimit(): void
    {
        ForgeConfig::set(FileSizeValidator::CONFIG_KEY, '1');

        $this->svnlook->method('getFilesize')->with($this->repository, 't1-r1', 'trunk/README.mkd')->willReturn(2097152);

        $this->expectException(CommittedFileTooLargeException::class);

        $this->validator->assertPathIsValid($this->repository, 't1-r1', 'U   trunk/README.mkd');
    }

    public function testItDoesntFailOnDirectories(): void
    {
        ForgeConfig::set(FileSizeValidator::CONFIG_KEY, '200');

        $failed_command = $this->getMockBuilder(Process::class)->onlyMethods(['getErrorOutput'])->disableOriginalConstructor()->getMock();
        $failed_command->method('getErrorOutput')->willReturn("svnlook: E160017: Path 'trunk/aaa' is not a file");

        $exception = $this->createMock(ProcessFailedException::class);
        $exception->method('getProcess')->willReturn($failed_command);

        $this->svnlook->expects(self::atLeast(1))->method('getFilesize')->with($this->repository, 't1-r1', 'trunk/add/')->willThrowException($exception);

        $this->validator->assertPathIsValid($this->repository, 't1-r1', 'A   trunk/add/');
    }

    public function testUnexpectedExceptionBubblesUp(): void
    {
        ForgeConfig::set(FileSizeValidator::CONFIG_KEY, '200');

        $failed_command = $this->getMockBuilder(Process::class)->onlyMethods(['getErrorOutput'])->disableOriginalConstructor()->getMock();
        $failed_command->method('getErrorOutput')->willReturn("svnlook: E160013: Path 'trunk/aaad' does not exist");

        $exception = $this->createMock(ProcessFailedException::class);
        $exception->method('getProcess')->willReturn($failed_command);

        $this->svnlook->method('getFilesize')->with($this->repository, 't1-r1', 'trunk/add/')->willThrowException($exception);

        $this->expectExceptionObject($exception);

        $this->validator->assertPathIsValid($this->repository, 't1-r1', 'A   trunk/add/');
    }
}
