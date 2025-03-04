<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\REST\v1;

use TestDataBuilder;
use Tuleap\REST\ForgeAccessSandbox;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediawikiStandaloneProjectResourceTest extends \RestBase
{
    use ForgeAccessSandbox;

    public function testOPTIONS(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'projects/' . $this->getProjectId('mediawiki-standalone-test') . '/mediawiki_standalone_permissions'),
            TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getPermissionsData')]
    public function testGetOnPlatformOpenToAnonymous(?string $user, array $expected): void
    {
        $this->setForgeToAnonymous();
        if ($user === null) {
            $response = $this->getResponseWithoutAuth(
                $this->request_factory->createRequest('GET', 'projects/' . $this->getProjectId('mediawiki-standalone-test') . '/mediawiki_standalone_permissions'),
            );
        } else {
            $response = $this->getResponse(
                $this->request_factory->createRequest('GET', 'projects/' . $this->getProjectId('mediawiki-standalone-test') . '/mediawiki_standalone_permissions'),
                $user
            );
        }
        $this->assertEquals(200, $response->getStatusCode());

        $payload = \json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            [
                'permissions' => $expected,
            ],
            $payload
        );
    }

    public static function getPermissionsData(): iterable
    {
        return [
            'project admin has full access' => [
                'user' => TestDataBuilder::TEST_USER_1_NAME,
                'expected' => [
                    'is_reader' => true,
                    'is_writer' => true,
                    'is_bot'    => false,
                    'is_admin'  => true,
                ],
            ],
            'project member is writer' => [
                'user' => TestDataBuilder::TEST_USER_3_NAME,
                'expected' => [
                    'is_reader' => true,
                    'is_writer' => true,
                    'is_bot'    => false,
                    'is_admin'  => false,
                ],
            ],
            'external user is nothing even if project is public because by default only project members are readers' => [
                'user' => TestDataBuilder::TEST_USER_2_NAME,
                'expected' => [
                    'is_reader' => false,
                    'is_writer' => false,
                    'is_bot'    => false,
                    'is_admin'  => false,
                ],
            ],
            'anonymous user is nothing even if project is public and platform accessible to anonymous because by default only project members are readers' => [
                'user' => null,
                'expected' => [
                    'is_reader' => false,
                    'is_writer' => false,
                    'is_bot'    => false,
                    'is_admin'  => false,
                ],
            ],
        ];
    }
}
