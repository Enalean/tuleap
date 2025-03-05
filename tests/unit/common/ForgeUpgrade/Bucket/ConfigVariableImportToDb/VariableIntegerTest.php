<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ForgeUpgrade\Bucket\ConfigVariableImportToDb;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VariableIntegerTest extends TestCase
{
    public function testStringConversion(): void
    {
        self::assertEquals('42', VariableInteger::withSameName('foo', 55)->getValueAsString(42));
    }

    public function testStringToString(): void
    {
        self::assertEquals('33', VariableInteger::withSameName('foo', 55)->getValueAsString('33'));
    }

    public function testStringWithDefaultValue(): void
    {
        self::assertEquals(55, VariableInteger::withSameName('foo', 55)->getValueAsString([]));
    }

    public function testSameName(): void
    {
        $var = VariableInteger::withSameName('my_var', 55);
        self::assertEquals('my_var', $var->getNameInFile());
        self::assertEquals('my_var', $var->getNameInDb());
    }

    public function testNewNameInDb(): void
    {
        $var = VariableInteger::withNewName('my_var', 'db_name', 55);
        self::assertEquals('my_var', $var->getNameInFile());
        self::assertEquals('db_name', $var->getNameInDb());
    }
}
