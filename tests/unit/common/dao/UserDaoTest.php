<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\DB\DBConnection;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class UserDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testReplaceStringInList(): void
    {
        $dao = new UserDao($this->createStub(DBConnection::class));

        self::assertEquals('tutu', $dao->replaceStringInList('foo', 'foo', 'tutu'));
        self::assertEquals('   tutu', $dao->replaceStringInList('   foo', 'foo', 'tutu'));
        self::assertEquals('tutu   ', $dao->replaceStringInList('foo   ', 'foo', 'tutu'));

        self::assertEquals('tutu,bar', $dao->replaceStringInList('foo,bar', 'foo', 'tutu'));
        self::assertEquals('tutu, bar', $dao->replaceStringInList('foo, bar', 'foo', 'tutu'));
        self::assertEquals('tutu ,bar', $dao->replaceStringInList('foo ,bar', 'foo', 'tutu'));

        self::assertEquals('bar,tutu,toto', $dao->replaceStringInList('bar,foo,toto', 'foo', 'tutu'));
        self::assertEquals('bar  ,  tutu  ,  toto', $dao->replaceStringInList('bar  ,  foo  ,  toto', 'foo', 'tutu'));

        self::assertEquals('bar,wwwfoo,toto', $dao->replaceStringInList('bar,wwwfoo,toto', 'foo', 'tutu'));
        self::assertEquals('bar,  wwwfoo,toto ', $dao->replaceStringInList('bar,  wwwfoo,toto ', 'foo', 'tutu'));

        self::assertEquals('bar,foowww,tutu', $dao->replaceStringInList('bar,foowww,foo', 'foo', 'tutu'));
        self::assertEquals('bar, foowww, tutu', $dao->replaceStringInList('bar, foowww, foo', 'foo', 'tutu'));

        self::assertEquals('tutu,tutu', $dao->replaceStringInList('foo,foo', 'foo', 'tutu'));
        self::assertEquals('tutu,bar,tutu', $dao->replaceStringInList('foo,bar,foo', 'foo', 'tutu'));
    }
}
