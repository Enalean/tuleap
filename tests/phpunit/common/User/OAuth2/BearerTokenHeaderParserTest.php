<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\User\OAuth2;

use PHPUnit\Framework\TestCase;

final class BearerTokenHeaderParserTest extends TestCase
{
    /**
     * @dataProvider dataProviderValidHeaderLines
     * @dataProvider dataProviderInvalidHeaderLinesLookingLikeABearerToken
     */
    public function testDetectsHeaderLinesThatMayContainABearerToken(string $header_line): void
    {
        $this->assertTrue((new BearerTokenHeaderParser())->doesHeaderLineContainsBearerTokenInformation($header_line));
    }

    /**
     * @dataProvider dataProviderInvalidHeaderLinesThatDoesNotEvenHaveABearerTag
     */
    public function testDoesNotDetectHeaderLinesThatCannotContainABearerToken(string $header_line): void
    {
        $this->assertFalse((new BearerTokenHeaderParser())->doesHeaderLineContainsBearerTokenInformation($header_line));
    }

    /**
     * @dataProvider dataProviderValidHeaderLines
     */
    public function testExtractBearerTokenFromValidHeaderLine(string $header_line, string $expected_identifier): void
    {
        $parser = new BearerTokenHeaderParser();

        $extracted_token = $parser->parseHeaderLine($header_line);

        $this->assertEquals($expected_identifier, $extracted_token->getString());
    }

    public function dataProviderValidHeaderLines(): array
    {
        $identifier = 'tlp-oauth2-at1-12.bde4f708ebda6fade1887c66867eceae95328e0d71dfc317c99e898fe802a4a0';
        return [
            ['Bearer ' . $identifier, $identifier],
            ['                     Bearer ' . $identifier, $identifier],
            ["Bearer\t$identifier", $identifier],
        ];
    }

    /**
     * @dataProvider dataProviderInvalidHeaderLinesLookingLikeABearerToken
     * @dataProvider dataProviderInvalidHeaderLinesThatDoesNotEvenHaveABearerTag
     */
    public function testDoesNotExtractBearerTokenFromInvalidHeaderLine(string $header_line): void
    {
        $parser = new BearerTokenHeaderParser();

        $this->assertNull($parser->parseHeaderLine($header_line));
    }

    public function dataProviderInvalidHeaderLinesLookingLikeABearerToken(): array
    {
        return [
            ['Bearer  a'],
            ['Bearer a foo'],
            ['Bearer $NotBase64Charset$'],
        ];
    }

    public function dataProviderInvalidHeaderLinesThatDoesNotEvenHaveABearerTag(): array
    {
        return [
            ['Basic aaaaaaaaa'],
            ['']
        ];
    }
}
