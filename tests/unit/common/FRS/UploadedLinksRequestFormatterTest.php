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

use HTTPRequest;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UploadedLinksRequestFormatterTest extends TestCase
{
    public function testItExtractsOneArrayFromLinksProvidedInRequest(): void
    {
        $request = $this->createMock(HTTPRequest::class);
        $matcher = $this->exactly(3);
        $request->expects($matcher)->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('uploaded-link-name', $parameters[0]);
                return ['test', ''];
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('uploaded-link', $parameters[0]);
                return ['http://example.com', 'ftp://example.com'];
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame('uploaded-link', $parameters[0]);
                return ['http://example.com', 'ftp://example.com'];
            }
        });
        $request->method('validArray')->willReturn(true);

        $formatter      = new UploadedLinksRequestFormatter();
        $expected_links = [
            ['link' => 'http://example.com', 'name' => 'test'],
            ['link' => 'ftp://example.com', 'name' => ''],
        ];

        self::assertSame($expected_links, $formatter->formatFromRequest($request));
    }

    public function testItThrowsAnExceptionWhenRequestDoesNotProvideCorrectInput(): void
    {
        $request = $this->createMock(HTTPRequest::class);
        $matcher = $this->exactly(3);
        $request->expects($matcher)->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('uploaded-link-name', $parameters[0]);
                return ['test'];
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('uploaded-link', $parameters[0]);
                return ['http://example.com', 'https://example.com'];
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame('uploaded-link', $parameters[0]);
                return ['http://example.com', 'https://example.com'];
            }
        });
        $request->method('validArray')->willReturn(true);

        $this->expectException(UploadedLinksInvalidFormException::class);
        $formatter = new UploadedLinksRequestFormatter();
        $formatter->formatFromRequest($request);
    }

    public function testItDoesNotAcceptInvalidLinks(): void
    {
        $request = $this->createMock(HTTPRequest::class);
        $matcher = $this->exactly(3);
        $request->expects($matcher)->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('uploaded-link-name', $parameters[0]);
                return ['invalid'];
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('uploaded-link', $parameters[0]);
                return ['example.com'];
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame('uploaded-link', $parameters[0]);
                return ['example.com'];
            }
        });
        $request->method('validArray')->willReturn(true);

        $formatter = new UploadedLinksRequestFormatter();

        $this->expectException(UploadedLinksInvalidFormException::class);
        $formatter->formatFromRequest($request);
    }

    public function testItDoesNotEmptyLinks(): void
    {
        $request = $this->createMock(HTTPRequest::class);
        $matcher = $this->exactly(3);
        $request->expects($matcher)->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('uploaded-link-name', $parameters[0]);
                return [''];
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('uploaded-link', $parameters[0]);
                return [''];
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame('uploaded-link', $parameters[0]);
                return [''];
            }
        });
        $request->method('validArray')->willReturn(true);

        $formatter      = new UploadedLinksRequestFormatter();
        $expected_links = [];

        self::assertSame($expected_links, $formatter->formatFromRequest($request));
    }
}
