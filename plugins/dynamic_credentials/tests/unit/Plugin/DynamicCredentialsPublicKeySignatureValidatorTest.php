<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\Plugin;

use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\Test\PHPUnit\TestCase;

final class DynamicCredentialsPublicKeySignatureValidatorTest extends TestCase
{
    private const VALID_PUBLIC_KEY = 'ka7Gcvo3RO0FeksfVkBCgTndCz/IMLfwCQA3DoN8k68=';
    private \Tuleap\Config\ValueValidator $validator;

    protected function setUp(): void
    {
        $this->validator = DynamicCredentialsPublicKeySignatureValidator::buildSelf();
    }

    public function testValidatesAcceptablePubKey(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->checkIsValid(self::VALID_PUBLIC_KEY);
    }

    public function testRejectsKeyThatDoesNotHaveTheCorrectLength(): void
    {
        $this->expectException(InvalidConfigKeyValueException::class);
        $this->validator->checkIsValid('aa');
    }

    public function testRejectsNotCorrectlyEncodedKey(): void
    {
        $this->expectException(InvalidConfigKeyValueException::class);
        $this->validator->checkIsValid('Z');
    }
}
