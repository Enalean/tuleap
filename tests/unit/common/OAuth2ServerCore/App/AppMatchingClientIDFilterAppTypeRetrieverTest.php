<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2ServerCore\App;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AppMatchingClientIDFilterAppTypeRetrieverTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&AppDao
     */
    private $app_dao;
    private AppMatchingClientIDFilterAppTypeRetriever $app_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->app_dao       = $this->createStub(AppDao::class);
        $this->app_retriever = new AppMatchingClientIDFilterAppTypeRetriever($this->app_dao, 'wanted_app_type');
    }

    public function testReturnsDataWhenItIsTheAppropriateType(): void
    {
        $this->setupAppDAO('wanted_app_type');
        $data = $this->app_retriever->searchByClientId(ClientIdentifier::fromClientId('tlp-client-id-1'));
        self::assertEquals(1, $data['id']);
    }

    public function testDoesNotReturnDataWhenTheAppDoesNotHaveTheExpectedType(): void
    {
        $this->setupAppDAO('another_app_type');
        $data = $this->app_retriever->searchByClientId(ClientIdentifier::fromClientId('tlp-client-id-1'));
        self::assertNull($data);
    }

    public function testReturnsNoDataWhenNoAppMatches(): void
    {
        $this->app_dao->method('searchByClientId')->willReturn(null);
        $data = $this->app_retriever->searchByClientId(ClientIdentifier::fromClientId('tlp-client-id-404'));
        self::assertNull($data);
    }

    private function setupAppDAO(string $app_type): void
    {
        $this->app_dao->method('searchByClientId')->willReturn(
            [
                'id' => 1,
                'project_id' => null,
                'name' => 'name',
                'redirect_endpoint' => 'https://example.com',
                'use_pkce' => 1,
                'app_type' => $app_type,
            ]
        );
    }
}
