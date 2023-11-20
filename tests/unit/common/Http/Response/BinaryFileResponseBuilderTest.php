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
 */

declare(strict_types=1);

namespace Tuleap\Http\Response;

use org\bovigo\vfs\vfsStream;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;

final class BinaryFileResponseBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testFileResponseCanBeBuiltFromFilepath(): void
    {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $directory = vfsStream::setup()->url();
        $file      = $directory . '/my_file';
        file_put_contents($file, 'ABCD');

        $request = (new NullServerRequest())
            ->withHeader('Range', '');

        $response = $builder->fromFilePath($request, $file);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('ABCD', $response->getBody()->getContents());
        self::assertEquals(filesize($file), (int) $response->getHeaderLine('Content-Length'));
        self::assertEquals('bytes', $response->getHeaderLine('Accept-Ranges'));
        self::assertTrue($response->hasHeader('Content-Type'));
        self::assertEquals('private', $response->getHeaderLine('Cache-Control'));
        self::assertEquals('no-cache', $response->getHeaderLine('Pragma'));
    }

    /**
     * @testWith ["archive.zip", "attachment; filename=\"archive.zip\""]
     *           ["bépo.zip", "attachment; filename=\"bpo.zip\"; filename*=UTF-8''b%C3%A9po.zip"]
     *           ["playa-🌴.zip", "attachment; filename=\"playa-.zip\"; filename*=UTF-8''playa-%F0%9F%8C%B4.zip"]
     *           ["per%cent.zip", "attachment; filename=\"per%cent.zip\""]
     *           ["sl/ash.zip", "attachment; filename=\"sl-ash.zip\""]
     *           ["back\\slash.zip", "attachment; filename=\"back-slash.zip\""]
     *           ["qu\"o”te.zip", "attachment; filename=\"qu\\\"ote.zip\"; filename*=UTF-8''qu%22o%E2%80%9Dte.zip"]
     */
    public function testFilenameIsSentInBothISO88691AndUTF8(string $name, string $expected): void
    {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $callback = static function (): void {
            echo 'Foo';
        };

        $response = $builder->fromCallback(new NullServerRequest(), $callback, $name, 'application/zip');

        self::assertEquals($expected, $response->getHeaderLine('Content-Disposition'));
    }

    public function testFileResponseCanBeBuiltFromACallback(): void
    {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $callback = static function (): void {
            echo 'Foo';
        };

        $response = $builder->fromCallback(new NullServerRequest(), $callback, 'archive.zip', 'application/zip');

        self::assertEquals(200, $response->getStatusCode());
        self::assertFalse($response->hasHeader('Content-Length'));
        self::assertFalse($response->hasHeader('Accept-Ranges'));
        self::assertEquals('application/zip', $response->getHeaderLine('Content-Type'));
        self::assertEquals('private', $response->getHeaderLine('Cache-Control'));
        self::assertEquals('no-cache', $response->getHeaderLine('Pragma'));
    }

    public function testResponseInstructsToNotCachePubliclyTheAnswer(): void
    {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $file = vfsStream::setup()->url() . '/file';
        touch($file);

        $response = $builder->fromFilePath(new NullServerRequest(), $file);

        self::assertEquals('private', $response->getHeaderLine('Cache-Control'));
        self::assertEquals('no-cache', $response->getHeaderLine('Pragma'));
    }

    /**
     * @testWith [4, "bytes=0-", "bytes 0-3/4", "4"]
     *           [4, "bytes=1-", "bytes 1-3/4", "3"]
     *           [4, "bytes=0-0", "bytes 0-0/4", "1"]
     *           [4, "bytes=2-3", "bytes 2-3/4", "2"]
     */
    public function testPartialFileResponse(
        int $total_content_size,
        string $range_header,
        string $expected_content_range_header,
        string $expected_content_length_header,
    ): void {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $directory = vfsStream::setup()->url();
        $file      = $directory . '/my_file';
        file_put_contents($file, str_repeat('A', $total_content_size));

        $request = (new NullServerRequest())
            ->withHeader('Range', $range_header);

        $response = $builder->fromFilePath($request, $file);

        self::assertEquals(206, $response->getStatusCode());
        self::assertEquals($expected_content_range_header, $response->getHeaderLine('Content-Range'));
        self::assertEquals($expected_content_length_header, $response->getHeaderLine('Content-Length'));
    }

    /**
     * @testWith ["xxxxxx=0-"]
     *           ["bytes=0-999999999999"]
     *           ["bytes=-0"]
     */
    public function testPartialFileResponseWithUnsupportedRangeHeader(string $range_header): void
    {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $directory = vfsStream::setup()->url();
        $file      = $directory . '/my_file';
        file_put_contents($file, 'AAA');

        $request = (new NullServerRequest())
            ->withHeader('Range', $range_header);

        $response = $builder->fromFilePath($request, $file);

        self::assertEquals(200, $response->getStatusCode());
        self::assertFalse($response->hasHeader('Content-Range'));
    }
}
