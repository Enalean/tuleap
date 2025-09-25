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

namespace Tuleap\Cryptography\Symmetric;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EncryptionAdditionalDataTest extends TestCase
{
    public function testCanonicalizesValues(): void
    {
        $ad = new EncryptionAdditionalData('table_name', 'field_name', 'id');

        self::assertSame('7461626c655f6e616d65_6669656c645f6e616d65_6964_', $ad->canonicalize());
    }

    public function testCanonicalizesWithExtraValues(): void
    {
        $ad = new EncryptionAdditionalData('t1', 'f1', 'id', ['extra1', 'extra2']);

        self::assertSame('7431_6631_6964_657874726131_657874726132', $ad->canonicalize());
    }
}
