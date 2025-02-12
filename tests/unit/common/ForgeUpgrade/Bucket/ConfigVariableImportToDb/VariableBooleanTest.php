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

final class VariableBooleanTest extends TestCase
{
    /**
     * @dataProvider dataForBooleanTests
     */
    public function testBooleanConversion(string $expected, mixed $value): void
    {
        self::assertEquals($expected, VariableBoolean::withSameName('foo', true)->getValueAsString($value));
    }

    public static function dataForBooleanTests(): iterable
    {
        yield ['1', true];
        yield ['0', false];
        yield ['1', '1'];
        yield ['0', '0'];
        yield ['1', 1];
        yield ['0', 0];
    }

    public function testBooleanWithDefaultValue(): void
    {
        self::assertEquals('1', VariableBoolean::withSameName('foo', true)->getValueAsString('voo'));
    }

    public function testSameName(): void
    {
        $var = VariableBoolean::withSameName('my_var', true);
        self::assertEquals('my_var', $var->getNameInFile());
        self::assertEquals('my_var', $var->getNameInDb());
    }

    public function testNewNameInDb(): void
    {
        $var = VariableBoolean::withNewName('my_var', 'db_name', false);
        self::assertEquals('my_var', $var->getNameInFile());
        self::assertEquals('db_name', $var->getNameInDb());
    }
}
