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

use Tuleap\Cryptography\ConcealedString;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JenkinsTuleapPluginHookPayloadTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsThePayload(): void
    {
        $git_repository = $this->createStub(\GitRepository::class);
        $git_repository->method('getProjectId')->willReturn('35');
        $git_repository->method('getName')->willReturn('Aufrecht_Melcher_Großaspach');

        $payload = new JenkinsTuleapPluginHookPayload(
            $git_repository,
            'refs/heads/chaise',
            new class implements JenkinsTuleapPluginHookTokenGenerator {
                public function generateTriggerToken(\DateTimeImmutable $now): ConcealedString
                {
                    return new ConcealedString((string) $now->getTimestamp());
                }
            },
            fn (): \DateTimeImmutable => new \DateTimeImmutable('@10')
        );

        $expected_payload =
            [
                'tuleapProjectId' => '35',
                'repositoryName'  => 'Aufrecht_Melcher_Großaspach',
                'branchName'      => 'chaise',
                'token' => '10',
            ];

        $this->assertEquals($expected_payload, $payload->getPayload());
    }
}
