<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UploadedLinksRequestFormatterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItExtractsOneArrayFromLinksProvidedInRequest()
    {
        $request = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('get')->with('uploaded-link-name')->andReturns(array('test', ''));
        $request->shouldReceive('get')->with('uploaded-link')->andReturns(array('http://example.com', 'ftp://example.com'));
        $request->shouldReceive('validArray')->andReturns(true);

        $formatter      = new UploadedLinksRequestFormatter();
        $expected_links = array(
            array('link' => 'http://example.com', 'name' => 'test'),
            array('link' => 'ftp://example.com', 'name' => '')
        );

        $this->assertSame($expected_links, $formatter->formatFromRequest($request));
    }

    public function testItThrowsAnExceptionWhenRequestDoesNotProvideCorrectInput()
    {
        $request = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('get')->with('uploaded-link-name')->andReturns(array('test'));
        $request->shouldReceive('get')->with('uploaded-link')->andReturns(array('http://example.com', 'https://example.com'));
        $request->shouldReceive('validArray')->andReturns(true);

        $this->expectException('Tuleap\FRS\UploadedLinksInvalidFormException');
        $formatter = new UploadedLinksRequestFormatter();
        $formatter->formatFromRequest($request);
    }

    public function testItDoesNotAcceptInvalidLinks()
    {
        $request = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('get')->with('uploaded-link-name')->andReturns(array('invalid'));
        $request->shouldReceive('get')->with('uploaded-link')->andReturns(array('example.com'));
        $request->shouldReceive('validArray')->andReturns(true);

        $formatter = new UploadedLinksRequestFormatter();

        $this->expectException('Tuleap\FRS\UploadedLinksInvalidFormException');
        $formatter->formatFromRequest($request);
    }

    public function testItDoesNotEmptyLinks()
    {
        $request = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('get')->with('uploaded-link-name')->andReturns(array());
        $request->shouldReceive('get')->with('uploaded-link')->andReturns(array());
        $request->shouldReceive('validArray')->andReturns(true);

        $formatter      = new UploadedLinksRequestFormatter();
        $expected_links = array();

        $this->assertSame($expected_links, $formatter->formatFromRequest($request));
    }
}
