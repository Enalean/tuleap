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

final class VariableStringTest extends TestCase
{
    public function testStringConversion(): void
    {
        self::assertEquals('baz', VariableString::withSameName('foo', 'bar')->getValueAsString('baz'));
    }

    public function testStringWithDefaultValue(): void
    {
        self::assertEquals('boo', VariableString::withSameName('foo', 'boo')->getValueAsString([]));
    }

    public function testSameName(): void
    {
        $var = VariableString::withSameName('my_var', '');
        self::assertEquals('my_var', $var->getNameInFile());
        self::assertEquals('my_var', $var->getNameInDb());
    }

    public function testNewNameInDb(): void
    {
        $var = VariableString::withNewName('my_var', 'db_name', '');
        self::assertEquals('my_var', $var->getNameInFile());
        self::assertEquals('db_name', $var->getNameInDb());
    }
}
