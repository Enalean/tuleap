<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
use Mockery;
use PHPUnit\Framework\TestCase;

class ExternalLinkParametersExtractorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItReturnZeroWhenFolderIdIsNotProvided()
    {
        $request         = Mockery::mock(HTTPRequest::class);
        $request->params = [];

        $extractor = new ExternalLinkParametersExtractor();
        $this->assertEquals(
            $extractor->extractFolderIdFromParams($request),
            0
        );
    }

    public function testItExtractFolderIdFromParameters()
    {
        $request         = Mockery::mock(HTTPRequest::class);
        $request->params = [
            'action' => 'show',
            'id'     => 100
        ];
        $extractor       = new ExternalLinkParametersExtractor();
        $this->assertEquals(
            $extractor->extractFolderIdFromParams($request),
            100
        );
    }

    public function testItShouldReturnTrueAndProcessEventIfSwitchOldUIParameterIsNotPresent()
    {
        $request         = Mockery::mock(HTTPRequest::class);
        $request->params = [];
        $extractor       = new ExternalLinkParametersExtractor();
        $this->assertTrue($extractor->extractRequestIsForOldUIParams($request));
    }

    public function testItShouldReturnFalseAndNotRaiseEventWhenSwitchToOldUIIsPresent()
    {
        $request         = Mockery::mock(HTTPRequest::class);
        $request->params = [
            'switcholdui' => true
        ];
        $extractor       = new ExternalLinkParametersExtractor();
        $this->assertTrue($extractor->extractRequestIsForOldUIParams($request));
    }
}
