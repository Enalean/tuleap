<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Save;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CallbackURLSaveTokenIdentifierExtractorTest extends TestCase
{
    public function testCanExtractTokenIdentifierFromACorrectCallbackURL(): void
    {
        $res = (new CallbackURLSaveTokenIdentifierExtractor())->extractSaveTokenIdentifierFromTheCallbackURL(
            new ConcealedString('https://example.com/onlyoffice/document_save?token=my_save_token_identifier')
        );
        self::assertEquals(
            'my_save_token_identifier',
            $res->unwrapOr(new ConcealedString(''))->getString()
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderInvalidURLs')]
    public function testReturnsAnErrorWhenTokenIdentifierCannotBeExtracted(string $url): void
    {
        $res = (new CallbackURLSaveTokenIdentifierExtractor())->extractSaveTokenIdentifierFromTheCallbackURL(
            new ConcealedString($url)
        );

        self::assertTrue(Result::isErr($res));
    }

    public static function dataProviderInvalidURLs(): array
    {
        return [
            'Broken URLs' => ['https://?'],
            'No query parameters in the URL' => ['https://example.com'],
            'No `token` query parameter' => ['https://example.com?a=a'],
        ];
    }
}
