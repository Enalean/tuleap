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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_GitoliteHousekeeping_GitoliteHousekeepingGitGcTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao    = Mockery::spy(Git_GitoliteHousekeeping_GitoliteHousekeepingDao::class);
        $this->logger = \Mockery::spy(\Psr\Log\LoggerInterface::class);

        $this->gitgc = \Mockery::mock(
            \Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc::class,
            [
                $this->dao,
                $this->logger,
                '/path/to/gitolite_admin_working_copy'
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testItRunsGitGcIfItIsAllowed(): void
    {
        $this->dao->shouldReceive('isGitGcEnabled')->andReturns(true);

        $this->logger->shouldReceive('info')->with('Running git gc on gitolite admin working copy.')->once();
        $this->gitgc->shouldReceive('execGitGcAsAppAdm')->once();

        $this->gitgc->cleanUpGitoliteAdminWorkingCopy();
    }

    public function testItDoesNotRunGitGcIfItIsNotAllowed(): void
    {
        $this->dao->shouldReceive('isGitGcEnabled')->andReturns(false);

        $this->logger->shouldReceive('warning')->with('Cannot run git gc on gitolite admin working copy. ' .
        'Please run as root: /usr/share/tuleap/src/utils/php-launcher.sh ' .
        '/usr/share/tuleap/plugins/git/bin/gl-admin-housekeeping.php')->once();
        $this->gitgc->shouldReceive('execGitGcAsAppAdm')->never();

        $this->gitgc->cleanUpGitoliteAdminWorkingCopy();
    }
}
