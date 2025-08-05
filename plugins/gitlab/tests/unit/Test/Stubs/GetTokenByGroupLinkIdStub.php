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

namespace Tuleap\Gitlab\Test\Stubs;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\SymmetricLegacy2025\SymmetricCrypto;
use Tuleap\Gitlab\Group\Token\GetTokenByGroupLinkId;

final class GetTokenByGroupLinkIdStub implements GetTokenByGroupLinkId
{
    /**
     * @param KeyFactory&MockObject $key_factory
     */
    private function __construct(private ConcealedString $token, private $key_factory)
    {
    }

    #[\Override]
    public function getTokenByGroupId(int $group_id): string
    {
        return SymmetricCrypto::encrypt($this->token, $this->key_factory->getEncryptionKey());
    }

    /**
     * @param KeyFactory&MockObject $key_factory
     */
    public static function withStoredToken(string $token, $key_factory): self
    {
        return new self(new ConcealedString($token), $key_factory);
    }
}
