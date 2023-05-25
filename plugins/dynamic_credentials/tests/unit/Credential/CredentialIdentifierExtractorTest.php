<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\Credential;

require_once __DIR__ . '/../bootstrap.php';

final class CredentialIdentifierExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testExtractionOfTheIdentifierFromProperlyFormattedUsername(): void
    {
        $extractor = new CredentialIdentifierExtractor();
        $username  = 'forge__dynamic_credential-identifier';

        $this->assertEquals('identifier', $extractor->extract($username));
    }

    /**
     * @dataProvider incorrectlyFormattedUsernameProvider
     */
    public function testRejectionWhenUsernameIsIncorrectlyFormatted(string $username): void
    {
        $extractor = new CredentialIdentifierExtractor();

        $this->expectException(CredentialInvalidUsernameException::class);

        $extractor->extract($username);
    }

    public static function incorrectlyFormattedUsernameProvider(): array
    {
        return [['forge__dynamic_credential-'], ['wrong_prefix-identifier']];
    }
}
