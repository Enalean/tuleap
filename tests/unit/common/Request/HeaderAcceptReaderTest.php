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

namespace Tuleap\Request;

use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;

final class HeaderAcceptReaderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var HTTPRequest&MockObject
     */
    private $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(\HTTPRequest::class);
    }

    public function testItReturnsTrueWhenTextHasImplicitWeight(): void
    {
        $this->request->method('getFromServer')->willReturn('application/xhtml+xml,text/html');

        self::assertTrue(HeaderAcceptReader::doesClientPreferHTMLResponse($this->request));
    }

    public function testItReturnsTrueWhenTextWeightIsHeavier(): void
    {
        $this->request->method('getFromServer')->willReturn('application/xhtml+xml;q=0.1,text/html;q=0.9');

        self::assertTrue(HeaderAcceptReader::doesClientPreferHTMLResponse($this->request));
    }

    public function testItReturnsTrueWhenAcceptMixHeightAndNoHeight(): void
    {
        $this->request->method('getFromServer')->willReturn(
            'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'
        );

        self::assertTrue(HeaderAcceptReader::doesClientPreferHTMLResponse($this->request));
    }

    public function testItReturnsFalseWhenAcceptHeaderIsSetToAcceptAll(): void
    {
        $this->request->method('getFromServer')->willReturn('*/*');

        self::assertFalse(HeaderAcceptReader::doesClientPreferHTMLResponse($this->request));
    }

    public function testItReturnsFalseOtherwise(): void
    {
        $this->request->method('getFromServer')->willReturn(
            'image/webp'
        );

        self::assertFalse(HeaderAcceptReader::doesClientPreferHTMLResponse($this->request));
    }
}
