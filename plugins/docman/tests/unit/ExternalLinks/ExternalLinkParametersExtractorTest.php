<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\ExternalLinks;

use HTTPRequest;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ExternalLinkParametersExtractorTest extends TestCase
{
    public function testItReturnZeroWhenFolderIdIsNotProvided(): void
    {
        $request         = new HTTPRequest();
        $request->params = [];
        $extractor       = new ExternalLinkParametersExtractor();
        self::assertEquals(0, $extractor->extractFolderIdFromParams($request));
    }

    public function testItExtractFolderIdFromParameters(): void
    {
        $request         = new HTTPRequest();
        $request->params = [
            'action' => 'show',
            'id'     => 100,
        ];
        $extractor       = new ExternalLinkParametersExtractor();
        self::assertEquals(100, $extractor->extractFolderIdFromParams($request));
    }

    public function testItShouldReturnTrueAndProcessEventIfSwitchOldUIParameterIsNotPresent(): void
    {
        $request         = new HTTPRequest();
        $request->params = [];
        $extractor       = new ExternalLinkParametersExtractor();
        self::assertTrue($extractor->extractRequestIsForOldUIParams($request));
    }

    public function testItShouldReturnFalseAndNotRaiseEventWhenSwitchToOldUIIsPresent(): void
    {
        $request         = new HTTPRequest();
        $request->params = ['switcholdui' => true];
        $extractor       = new ExternalLinkParametersExtractor();
        self::assertTrue($extractor->extractRequestIsForOldUIParams($request));
    }
}
