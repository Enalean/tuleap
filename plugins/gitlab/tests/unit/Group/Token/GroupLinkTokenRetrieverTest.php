<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Group\Token;

use Tuleap\Gitlab\Test\Builder\GroupLinkBuilder;
use Tuleap\Gitlab\Test\Stubs\GetTokenByGroupLinkIdStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GroupLinkTokenRetrieverTest extends TestCase
{
    private const string STORED_TOKEN = 'L4l4';

    public function testItReturnsOkWhenTheTokenIsFound(): void
    {
        $get_token_by_group_id = GetTokenByGroupLinkIdStub::withStoredToken(self::STORED_TOKEN);

        $group_link_token_retriever = new GroupLinkTokenRetriever(
            $get_token_by_group_id,
        );

        $result = $group_link_token_retriever->retrieveToken(GroupLinkBuilder::aGroupLink(10)->build());


        self::assertSame(self::STORED_TOKEN, $result->getToken()->getString());
    }
}
