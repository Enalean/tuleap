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
class Git_GitoliteHousekeeping_GitoliteHousekeepingRunnerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->process_manager  = \Mockery::spy(\SystemEventProcessManager::class);
        $this->process          = \Mockery::spy(\SystemEventProcess::class);
        $this->housekeeping_dao = Mockery::mock(Git_GitoliteHousekeeping_GitoliteHousekeepingDao::class);
        $this->response         = \Mockery::spy(\Git_GitoliteHousekeeping_GitoliteHousekeepingResponse::class);
        $this->backend_service  = \Mockery::spy(\BackendService::class);

        $this->gitolite_var_path       = 'gitolite_var_path';
        $this->remote_admin_repository = 'remote_admin_repository';

        $this->runner = new Git_GitoliteHousekeeping_GitoliteHousekeepingRunner(
            $this->process_manager,
            $this->process,
            $this->housekeeping_dao,
            $this->response,
            $this->backend_service,
            $this->gitolite_var_path,
            $this->remote_admin_repository
        );
    }

    public function testItLoadsAllCommandsInTheRightOrder(): void
    {
        $expected_commands = array(
            'Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceStopper',
            'Git_GitoliteHousekeeping_ChainOfResponsibility_CheckRunningEvents',
            'Git_GitoliteHousekeeping_ChainOfResponsibility_CleanUpGitoliteAdminRepo',
            'Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGc',
            'Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceRestarter',
            'Git_GitoliteHousekeeping_ChainOfResponsibility_DoNothing'
        );

        $chain = $this->runner->getChain();

        foreach ($expected_commands as $expected_command) {
            $this->assertInstanceOf($expected_command, $chain);
            $chain = $chain->getNextCommand();
        }
    }
}
