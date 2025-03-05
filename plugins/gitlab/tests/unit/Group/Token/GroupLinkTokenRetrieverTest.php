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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Gitlab\Test\Builder\GroupLinkBuilder;
use Tuleap\Gitlab\Test\Stubs\GetTokenByGroupLinkIdStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GroupLinkTokenRetrieverTest extends TestCase
{
    private const STORED_TOKEN = 'L4l4';

    /**
     * @var KeyFactory&MockObject
     */
    private $key_factory;
    private GetTokenByGroupLinkIdStub $get_token_by_group_id;

    protected function setUp(): void
    {
        $this->key_factory = $this->createMock(KeyFactory::class);
    }

    private function retrieveGroupLinkToken(): GroupLinkApiToken
    {
        $group_link_token_retriever = new GroupLinkTokenRetriever(
            $this->get_token_by_group_id,
            $this->key_factory
        );

        return $group_link_token_retriever->retrieveToken(GroupLinkBuilder::aGroupLink(10)->build());
    }

    public function testItReturnsOkWhenTheTokenIsFound(): void
    {
        $this->get_token_by_group_id = GetTokenByGroupLinkIdStub::withStoredToken(self::STORED_TOKEN, $this->key_factory);

        $encryption_key = new EncryptionKey(new ConcealedString(str_repeat('a', 32)));
        $this->key_factory->expects(self::atLeastOnce())->method('getEncryptionKey')->willReturn($encryption_key);

        $result = $this->retrieveGroupLinkToken();

        self::assertSame(self::STORED_TOKEN, $result->getToken()->getString());
    }
}
