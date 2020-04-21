<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class JenkinsTuleapPluginHookPayloadTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsThePayload(): void
    {
        $git_repository = \Mockery::mock(\GitRepository::class);
        $git_repository->shouldReceive('getProjectId')->andReturn('35');
        $git_repository->shouldReceive('getName')->andReturn('Aufrecht_Melcher_Großaspach');

        $payload = new JenkinsTuleapPluginHookPayload($git_repository, 'refs/heads/chaise');

        $expected_payload =
            [
                'tuleapProjectId' => '35',
                'repositoryName'  => 'Aufrecht_Melcher_Großaspach',
                'branchName'      => 'chaise'
            ];

        $this->assertEquals($expected_payload, $payload->getPayload());
    }
}
