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
use Mockery;
use PHPUnit\Framework\TestCase;

final class HeaderAcceptReaderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var HTTPRequest
     */
    private $request;

    protected function setUp(): void
    {
        $this->request = Mockery::mock(\HTTPRequest::class);
    }

    public function testItReturnsTrueWhenTextHasImplicitWeight(): void
    {
        $this->request->shouldReceive('getFromServer')->andReturn('application/xhtml+xml,text/html');

        $this->assertTrue(HeaderAcceptReader::doesClientPreferHTMLResponse($this->request));
    }

    public function testItReturnsTrueWhenTextWeightIsHeavier(): void
    {
        $this->request->shouldReceive('getFromServer')->andReturn('application/xhtml+xml;q=0.1,text/html;q=0.9');

        $this->assertTrue(HeaderAcceptReader::doesClientPreferHTMLResponse($this->request));
    }

    public function testItReturnsTrueWhenAcceptMixHeightAndNoHeight(): void
    {
        $this->request->shouldReceive('getFromServer')->andReturn(
            'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'
        );

        $this->assertTrue(HeaderAcceptReader::doesClientPreferHTMLResponse($this->request));
    }

    public function testItReturnsFalseWhenAcceptHeaderIsSetToAcceptAll(): void
    {
        $this->request->shouldReceive('getFromServer')->andReturn('*/*');

        $this->assertFalse(HeaderAcceptReader::doesClientPreferHTMLResponse($this->request));
    }

    public function testItReturnsFalseOtherwise(): void
    {
        $this->request->shouldReceive('getFromServer')->andReturn(
            'image/webp'
        );

        $this->assertFalse(HeaderAcceptReader::doesClientPreferHTMLResponse($this->request));
    }
}
