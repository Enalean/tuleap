<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\GitoliteHousekeeping;

use ColinODell\PsrTestLogger\TestLogger;
use Git_GitoliteHousekeeping_GitoliteHousekeepingDao;
use Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitoliteHousekeepingGitGcTest extends TestCase
{
    private Git_GitoliteHousekeeping_GitoliteHousekeepingDao&MockObject $dao;
    private TestLogger $logger;
    private Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc&MockObject $gitgc;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(Git_GitoliteHousekeeping_GitoliteHousekeepingDao::class);
        $this->logger = new TestLogger();

        $this->gitgc = $this->getMockBuilder(Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc::class)
            ->setConstructorArgs([
                $this->dao,
                $this->logger,
                '/path/to/gitolite_admin_working_copy',
            ])
            ->onlyMethods(['execGitGcAsAppAdm'])
            ->getMock();
    }

    public function testItRunsGitGcIfItIsAllowed(): void
    {
        $this->dao->method('isGitGcEnabled')->willReturn(true);

        $this->gitgc->expects($this->once())->method('execGitGcAsAppAdm');

        $this->gitgc->cleanUpGitoliteAdminWorkingCopy();
        self::assertTrue($this->logger->hasInfoThatContains('Running git gc on gitolite admin working copy.'));
    }

    public function testItDoesNotRunGitGcIfItIsNotAllowed(): void
    {
        $this->dao->method('isGitGcEnabled')->willReturn(false);

        $this->gitgc->expects(self::never())->method('execGitGcAsAppAdm');

        $this->gitgc->cleanUpGitoliteAdminWorkingCopy();
        self::assertTrue($this->logger->hasWarningThatContains(
            'Cannot run git gc on gitolite admin working copy. ' .
            'Please run as root: /usr/share/tuleap/src/utils/php-launcher.sh ' .
            '/usr/share/tuleap/plugins/git/bin/gl-admin-housekeeping.php'
        ));
    }
}
