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

namespace Tuleap\Tus;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class TusServerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    private $data_store;
    private $file_information_provider;

    protected function setUp(): void
    {
        $this->response_factory          = HTTPFactoryBuilder::responseFactory();
        $this->data_store                = $this->createMock(TusDataStore::class);
        $this->file_information_provider = $this->createMock(TusFileInformationProvider::class);
        $this->data_store->method('getFileInformationProvider')->willReturn($this->file_information_provider);
    }

    public function testInformationAboutTheServerCanBeGathered(): void
    {
        $this->data_store->method('getTerminater')->willReturn(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('OPTIONS');

        $response = $server->handle($incoming_request);

        self::assertEquals(204, $response->getStatusCode());
        self::assertTrue($response->hasHeader('Tus-Version'));
        self::assertFalse($response->hasHeader('Tus-Extension'));
    }

    public function testInformationAboutExtensionsAreGivenIfThereIsAnAvailableExtension(): void
    {
        $this->data_store->method('getTerminater')->willReturn($this->createMock(TusTerminaterDataStore::class));
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('OPTIONS');

        $response = $server->handle($incoming_request);

        self::assertTrue($response->hasHeader('Tus-Extension'));
        self::assertNotEmpty(\explode(',', $response->getHeaderLine('Tus-Extension')));
    }

    public function testInformationAboutTheFileBeingUploadedCanBeGathered(): void
    {
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('HEAD');
        $incoming_request->method('getHeaderLine')->with('Tus-Resumable')->willReturn('1.0.0');

        $file_information = $this->createMock(TusFileInformation::class);
        $file_information->method('getLength')->willReturn(123456);
        $file_information->method('getOffset')->willReturn(123);
        $this->file_information_provider->method('getFileInformation')->willReturn($file_information);

        $response = $server->handle($incoming_request);

        self::assertEquals(204, $response->getStatusCode());
        self::assertTrue($response->hasHeader('Tus-Resumable'));
        self::assertEquals(123456, $response->getHeaderLine('Upload-Length'));
        self::assertEquals(123, $response->getHeaderLine('Upload-Offset'));
        self::assertEquals('no-cache', $response->getHeaderLine('Cache-Control'));
    }

    /**
     * @dataProvider validUploadRequestProvider
     */
    public function testFileCanBeUploaded(int $upload_offset, string $body_content, string $content_type, bool $has_finisher): void
    {
        $data_writer = $this->createMock(TusWriter::class);
        $this->data_store->method('getWriter')->willReturn($data_writer);
        $finisher_data_store = $this->createMock(TusFinisherDataStore::class);
        if ($has_finisher) {
            $this->data_store->method('getFinisher')->willReturn($finisher_data_store);
        } else {
            $this->data_store->method('getFinisher')->willReturn(null);
        }
        $locker = $this->createMock(TusLocker::class);
        $this->data_store->method('getLocker')->willReturn($locker);
        $server = new TusServer($this->response_factory, $this->data_store);

        $upload_request = $this->createMock(ServerRequestInterface::class);
        $upload_request->method('getMethod')->willReturn('PATCH');
        $upload_request->method('hasHeader')->with('Upload-Offset')->willReturn(true);

        $upload_request->method('getHeaderLine')->willReturnMap([
            ['Upload-Offset', $upload_offset],
            ['Tus-Resumable', '1.0.0'],
            ['Content-Type', $content_type],
        ]);

        $request_body        = $this->createMock(StreamInterface::class);
        $request_body_stream = fopen('php://memory', 'rb+');
        fwrite($request_body_stream, $body_content);
        rewind($request_body_stream);
        $request_body->method('detach')->willReturn($request_body_stream);
        $upload_request->method('getBody')->willReturn($request_body);

        $file_information = $this->createMock(TusFileInformation::class);
        $file_information->method('getLength')->willReturn(strlen($body_content));
        $file_information->method('getOffset')->willReturn($upload_offset);
        $this->file_information_provider->method('getFileInformation')->willReturn($file_information);

        $data_writer->expects(self::once())->method('writeChunk')
            ->with(
                $file_information,
                $upload_offset,
                self::callback(
                    function ($input_resource) use ($body_content): bool {
                        return stream_get_contents($input_resource) === $body_content;
                    }
                )
            )->willReturn(strlen($body_content));
        if ($has_finisher) {
            $finisher_data_store->expects(self::once())->method('finishUpload')->with($file_information);
        }
        $locker->expects(self::once())->method('lock')->willReturn(true);
        $locker->expects(self::once())->method('unlock');

        $response = $server->handle($upload_request);

        self::assertEquals(204, $response->getStatusCode());

        self::assertEquals($upload_offset + strlen($body_content), $response->getHeaderLine('Upload-Offset'));
    }

    public static function validUploadRequestProvider(): array
    {
        return [
            [0, 'Content to upload', 'application/offset+octet-stream', false],
            [0, 'Content', 'application/offset+octet-stream', false],
            [1, 'Content to upload', 'application/offset+octet-stream', false],
            [1, 'Content', 'application/offset+octet-stream', false],
            [0, 'Content to upload', 'application/offset+octet-stream; charset=utf-8', false],
            [0, 'Content to upload', 'application/offset+octet-stream', true],
            [1, 'Content to upload', 'application/offset+octet-stream', true],
        ];
    }

    public function testRequestWithANonSupportedVersionOfTheProtocolIsRejected(): void
    {
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('HEAD');
        $incoming_request->method('getHeaderLine')->with('Tus-Resumable')->willReturn('0.2.2');

        $response = $server->handle($incoming_request);

        self::assertEquals(412, $response->getStatusCode());
        self::assertTrue($response->hasHeader('Tus-Version'));
    }

    public function testAnUploadRequestWithAnIncorrectOffsetIsRejected(): void
    {
        $this->data_store->method('getLocker')->willReturn(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('PATCH');
        $incoming_request->method('hasHeader')->with('Upload-Offset')->willReturn(true);

        $incoming_request->method('getHeaderLine')->willReturnMap([
            ['Upload-Offset', 10],
            ['Tus-Resumable', '1.0.0'],
            ['Content-Type', 'application/offset+octet-stream'],
        ]);

        $request_body        = $this->createMock(StreamInterface::class);
        $request_body_stream = fopen('php://memory', 'rb+');
        $request_body->method('detach')->willReturn($request_body_stream);
        $incoming_request->method('getBody')->willReturn($request_body);

        $file_information = $this->createMock(TusFileInformation::class);
        $file_information->method('getOffset')->willReturn(20);
        $this->file_information_provider->method('getFileInformation')->willReturn($file_information);

        $response = $server->handle($incoming_request);

        self::assertEquals(409, $response->getStatusCode());
    }

    public function testAnUploadRequestWithoutTheOffsetIsRejected(): void
    {
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('PATCH');
        $incoming_request->method('hasHeader')->with('Upload-Offset')->willReturn(false);

        $incoming_request->method('getHeaderLine')->willReturnMap([
            ['Tus-Resumable', '1.0.0'],
            ['Content-Type', 'application/offset+octet-stream'],
        ]);

        $this->file_information_provider->method('getFileInformation')->willReturn($this->createMock(TusFileInformation::class));

        $response = $server->handle($incoming_request);

        self::assertEquals(400, $response->getStatusCode());
    }

    public function testAnUploadRequestWithAnIncorrectContentTypeIsRejected(): void
    {
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('PATCH');

        $incoming_request->method('getHeaderLine')->willReturnMap([
            ['Tus-Resumable', '1.0.0'],
            ['Content-Type', 'image/png'],
        ]);

        $this->file_information_provider->method('getFileInformation')->willReturn($this->createMock(TusFileInformation::class));

        $response = $server->handle($incoming_request);

        self::assertEquals(415, $response->getStatusCode());
    }

    public function testAnErrorIsGivenWhenTheIncomingRequestBodyCannotBeRead(): void
    {
        $data_writer = $this->createMock(TusWriter::class);
        $this->data_store->method('getWriter')->willReturn($data_writer);
        $this->data_store->method('getLocker')->willReturn(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('PATCH');
        $incoming_request->method('hasHeader')->with('Upload-Offset')->willReturn(true);

        $incoming_request->method('getHeaderLine')->willReturnMap([
            ['Upload-Offset', 0],
            ['Tus-Resumable', '1.0.0'],
            ['Content-Type', 'application/offset+octet-stream'],
        ]);

        $request_body = $this->createMock(StreamInterface::class);
        $request_body->method('detach')->willReturn(null);
        $incoming_request->method('getBody')->willReturn($request_body);

        $file_information = $this->createMock(TusFileInformation::class);
        $this->file_information_provider->method('getFileInformation')->willReturn($file_information);

        $response = $server->handle($incoming_request);
        self::assertEquals(500, $response->getStatusCode());
    }

    public function testAnErrorIsGivenWhenTheFileCanNotBeSaved(): void
    {
        $data_writer = $this->createMock(TusWriter::class);
        $this->data_store->method('getWriter')->willReturn($data_writer);
        $this->data_store->method('getLocker')->willReturn(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('PATCH');
        $incoming_request->method('hasHeader')->with('Upload-Offset')->willReturn(true);

        $incoming_request->method('getHeaderLine')->willReturnMap([
            ['Upload-Offset', 0],
            ['Tus-Resumable', '1.0.0'],
            ['Content-Type', 'application/offset+octet-stream'],
        ]);

        $request_body        = $this->createMock(StreamInterface::class);
        $request_body_stream = fopen('php://memory', 'rb+');
        $request_body->method('detach')->willReturn($request_body_stream);
        $incoming_request->method('getBody')->willReturn($request_body);

        $file_information = $this->createMock(TusFileInformation::class);
        $file_information->method('getLength')->willReturn(123456);
        $file_information->method('getOffset')->willReturn(0);
        $this->file_information_provider->method('getFileInformation')->willReturn($file_information);
        $data_writer->method('writeChunk')->willThrowException(new CannotWriteFileException());

        $response = $server->handle($incoming_request);

        self::assertEquals(500, $response->getStatusCode());
        self::assertTrue($response->hasHeader('Tus-Resumable'));
    }

    public function testANotFoundErrorIsGivenWhenTheFileCanNotBeProvided(): void
    {
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('PATCH');

        $incoming_request->method('getHeaderLine')->willReturnMap([
            ['Tus-Resumable', '1.0.0'],
            ['Content-Type', 'application/offset+octet-stream'],
        ]);

        $this->file_information_provider->method('getFileInformation')->willReturn(null);

        $response = $server->handle($incoming_request);

        self::assertEquals(404, $response->getStatusCode());
    }

    public function testAnUploadCanBeTerminatedWhenTheTerminationExtensionIsEnabled(): void
    {
        $terminater = $this->createMock(TusTerminaterDataStore::class);
        $this->data_store->method('getTerminater')->willReturn($terminater);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('DELETE');
        $incoming_request->method('getHeaderLine')->with('Tus-Resumable')->willReturn('1.0.0');

        $this->file_information_provider->method('getFileInformation')->willReturn($this->createMock(TusFileInformation::class));

        $terminater->expects(self::once())->method('terminateUpload');

        $response = $server->handle($incoming_request);

        self::assertEquals(204, $response->getStatusCode());
    }

    public function testAnUploadCanNotBeTerminatedWhenTheTerminationExtensionIsNotEnabled(): void
    {
        $this->data_store->method('getTerminater')->willReturn(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('DELETE');
        $incoming_request->method('getHeaderLine')->with('Tus-Resumable')->willReturn('1.0.0');

        $response = $server->handle($incoming_request);

        self::assertEquals(405, $response->getStatusCode());
    }

    public function testAnUploadIsNotFinishedWhenAllDataHasNotBeenCopied(): void
    {
        $data_writer = $this->createMock(TusWriter::class);
        $this->data_store->method('getWriter')->willReturn($data_writer);
        $finisher = $this->createMock(TusFinisherDataStore::class);
        $this->data_store->method('getFinisher')->willReturn($finisher);
        $this->data_store->method('getLocker')->willReturn(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incomplete_upload_request = $this->createMock(ServerRequestInterface::class);
        $incomplete_upload_request->method('getMethod')->willReturn('PATCH');
        $incomplete_upload_request->method('hasHeader')->with('Upload-Offset')->willReturn(true);

        $incomplete_upload_request->method('getHeaderLine')->willReturnMap([
            ['Upload-Offset', 0],
            ['Tus-Resumable', '1.0.0'],
            ['Content-Type', 'application/offset+octet-stream'],
        ]);

        $request_body = $this->createMock(StreamInterface::class);
        $body_size    = 12;
        $request_body->method('detach')->willReturn(fopen('php://memory', 'rb+'));
        $incomplete_upload_request->method('getBody')->willReturn($request_body);

        $file_information = $this->createMock(TusFileInformation::class);
        $file_information->method('getLength')->willReturn($body_size * 100);
        $file_information->method('getOffset')->willReturn(0);
        $this->file_information_provider->method('getFileInformation')->willReturn($file_information);

        $data_writer->method('writeChunk')->willReturn($body_size);

        $finisher->expects(self::never())->method('finishUpload')->with($file_information);

        $server->handle($incomplete_upload_request);
    }

    public function testALockedUploadIsNotOverwritten(): void
    {
        $locker = $this->createMock(TusLocker::class);
        $this->data_store->method('getLocker')->willReturn($locker);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = $this->createMock(ServerRequestInterface::class);
        $incoming_request->method('getMethod')->willReturn('PATCH');
        $incoming_request->method('hasHeader')->with('Upload-Offset')->willReturn(true);

        $incoming_request->method('getHeaderLine')->willReturnMap([
            ['Upload-Offset', 0],
            ['Tus-Resumable', '1.0.0'],
            ['Content-Type', 'application/offset+octet-stream'],
        ]);

        $request_body        = $this->createMock(StreamInterface::class);
        $request_body_stream = fopen('php://memory', 'rb+');
        $request_body->method('detach')->willReturn($request_body_stream);
        $incoming_request->method('getBody')->willReturn($request_body);

        $file_information = $this->createMock(TusFileInformation::class);
        $file_information->method('getLength')->willReturn(123456);
        $file_information->method('getOffset')->willReturn(0);
        $this->file_information_provider->method('getFileInformation')->willReturnOnConsecutiveCalls(
            $file_information,
            $this->createMock(TusFileInformation::class)
        );

        $locker->method('lock')->willReturn(false);
        $locker->method('unlock');
        $this->data_store->expects(self::never())->method('getWriter');

        $response = $server->handle($incoming_request);

        self::assertEquals(423, $response->getStatusCode());
    }
}
