<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Cryptography\Stub;

use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;

final class KeyFactoryStub implements KeyFactory
{
    private(set) bool $key_created     = false;
    private(set) bool $permissions_set = false;

    #[\Override]
    public function getEncryptionKey(): EncryptionKey
    {
        return new EncryptionKey($this->getKeyMaterial());
    }

    private function getKeyMaterial(): ConcealedString
    {
        $this->key_created = true;
        return new ConcealedString(str_repeat('a', 32));
    }

    #[\Override]
    public function restoreOwnership(LoggerInterface $logger): void
    {
        $this->permissions_set = true;
    }
}
