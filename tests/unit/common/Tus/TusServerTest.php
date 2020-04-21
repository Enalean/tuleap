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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class TusServerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    private $data_store;
    private $file_information_provider;

    protected function setUp(): void
    {
        $this->response_factory          = HTTPFactoryBuilder::responseFactory();
        $this->data_store                = \Mockery::mock(TusDataStore::class);
        $this->file_information_provider = \Mockery::mock(TusFileInformationProvider::class);
        $this->data_store->shouldReceive('getFileInformationProvider')->andReturns($this->file_information_provider);
    }

    public function testInformationAboutTheServerCanBeGathered(): void
    {
        $this->data_store->shouldReceive('getTerminater')->andReturns(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('OPTIONS');

        $response = $server->handle($incoming_request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Tus-Version'));
        $this->assertFalse($response->hasHeader('Tus-Extension'));
    }

    public function testInformationAboutExtensionsAreGivenIfThereIsAnAvailableExtension(): void
    {
        $this->data_store->shouldReceive('getTerminater')->andReturns(\Mockery::mock(TusTerminaterDataStore::class));
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('OPTIONS');

        $response = $server->handle($incoming_request);

        $this->assertTrue($response->hasHeader('Tus-Extension'));
        $this->assertNotEmpty(\explode(',', $response->getHeaderLine('Tus-Extension')));
    }

    public function testInformationAboutTheFileBeingUploadedCanBeGathered(): void
    {
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('HEAD');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');

        $file_information = \Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive('getLength')->andReturns(123456);
        $file_information->shouldReceive('getOffset')->andReturns(123);
        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns($file_information);

        $response = $server->handle($incoming_request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Tus-Resumable'));
        $this->assertEquals(123456, $response->getHeaderLine('Upload-Length'));
        $this->assertEquals(123, $response->getHeaderLine('Upload-Offset'));
        $this->assertEquals('no-cache', $response->getHeaderLine('Cache-Control'));
    }

    /**
     * @dataProvider validUploadRequestProvider
     */
    public function testFileCanBeUploaded(int $upload_offset, string $body_content, string $content_type, bool $has_finisher): void
    {
        $data_writer = \Mockery::mock(TusWriter::class);
        $this->data_store->shouldReceive('getWriter')->andReturns($data_writer);
        $finisher_data_store = \Mockery::mock(TusFinisherDataStore::class);
        if ($has_finisher) {
            $this->data_store->shouldReceive('getFinisher')->andReturns($finisher_data_store);
        } else {
            $this->data_store->shouldReceive('getFinisher')->andReturns(null);
        }
        $locker = \Mockery::mock(TusLocker::class);
        $this->data_store->shouldReceive('getLocker')->andReturns($locker);
        $server = new TusServer($this->response_factory, $this->data_store);

        $upload_request = \Mockery::mock(ServerRequestInterface::class);
        $upload_request->shouldReceive('getMethod')->andReturns('PATCH');
        $upload_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $upload_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns($content_type);
        $upload_request->shouldReceive('hasHeader')->with('Upload-Offset')->andReturns(true);
        $upload_request->shouldReceive('getHeaderLine')->with('Upload-Offset')->andReturns($upload_offset);
        $request_body = \Mockery::mock(StreamInterface::class);
        $request_body_stream = fopen('php://memory', 'rb+');
        fwrite($request_body_stream, $body_content);
        rewind($request_body_stream);
        $request_body->shouldReceive('detach')->andReturns($request_body_stream);
        $upload_request->shouldReceive('getBody')->andReturns($request_body);

        $file_information = \Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive('getLength')->andReturns(strlen($body_content));
        $file_information->shouldReceive('getOffset')->andReturns($upload_offset);
        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns($file_information);

        $data_writer->shouldReceive('writeChunk')->once()
            ->with(
                $file_information,
                $upload_offset,
                \Mockery::on(
                    function ($input_resource) use ($body_content): bool {
                        return stream_get_contents($input_resource) === $body_content;
                    }
                )
            )->andReturns(strlen($body_content));
        if ($has_finisher) {
            $finisher_data_store->shouldReceive('finishUpload')->with($file_information)->once();
        }
        $locker->shouldReceive('lock')->andReturns(true)->once();
        $locker->shouldReceive('unlock')->once();

        $response = $server->handle($upload_request);

        $this->assertEquals(204, $response->getStatusCode());

        $this->assertEquals($upload_offset + strlen($body_content), $response->getHeaderLine('Upload-Offset'));
    }

    public function validUploadRequestProvider(): array
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

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('HEAD');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('0.2.2');

        $response = $server->handle($incoming_request);

        $this->assertEquals(412, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Tus-Version'));
    }

    public function testAnUploadRequestWithAnIncorrectOffsetIsRejected(): void
    {
        $this->data_store->shouldReceive('getLocker')->andReturns(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('application/offset+octet-stream');
        $incoming_request->shouldReceive('hasHeader')->with('Upload-Offset')->andReturns(true);
        $incoming_request->shouldReceive('getHeaderLine')->with('Upload-Offset')->andReturns(10);
        $request_body = \Mockery::mock(StreamInterface::class);
        $request_body_stream = fopen('php://memory', 'rb+');
        $request_body->shouldReceive('detach')->andReturns($request_body_stream);
        $incoming_request->shouldReceive('getBody')->andReturns($request_body);

        $file_information = \Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive('getOffset')->andReturns(20);
        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns($file_information);

        $response = $server->handle($incoming_request);

        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testAnUploadRequestWithoutTheOffsetIsRejected(): void
    {
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('application/offset+octet-stream');
        $incoming_request->shouldReceive('hasHeader')->with('Upload-Offset')->andReturns(false);

        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns(\Mockery::mock(TusFileInformation::class));

        $response = $server->handle($incoming_request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAnUploadRequestWithAnIncorrectContentTypeIsRejected(): void
    {
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('image/png');

        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns(\Mockery::mock(TusFileInformation::class));

        $response = $server->handle($incoming_request);

        $this->assertEquals(415, $response->getStatusCode());
    }

    public function testAnErrorIsGivenWhenTheIncomingRequestBodyCannotBeRead(): void
    {
        $data_writer = \Mockery::mock(TusWriter::class);
        $this->data_store->shouldReceive('getWriter')->andReturns($data_writer);
        $this->data_store->shouldReceive('getLocker')->andReturns(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('application/offset+octet-stream');
        $incoming_request->shouldReceive('hasHeader')->with('Upload-Offset')->andReturns(true);
        $incoming_request->shouldReceive('getHeaderLine')->with('Upload-Offset')->andReturns(0);
        $request_body = \Mockery::mock(StreamInterface::class);
        $request_body->shouldReceive('detach')->andReturns(null);
        $incoming_request->shouldReceive('getBody')->andReturns($request_body);

        $file_information = \Mockery::mock(TusFileInformation::class);
        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns($file_information);

        $response = $server->handle($incoming_request);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testAnErrorIsGivenWhenTheFileCanNotBeSaved(): void
    {
        $data_writer = \Mockery::mock(TusWriter::class);
        $this->data_store->shouldReceive('getWriter')->andReturns($data_writer);
        $this->data_store->shouldReceive('getLocker')->andReturns(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('application/offset+octet-stream');
        $incoming_request->shouldReceive('hasHeader')->with('Upload-Offset')->andReturns(true);
        $incoming_request->shouldReceive('getHeaderLine')->with('Upload-Offset')->andReturns(0);
        $request_body = \Mockery::mock(StreamInterface::class);
        $request_body_stream = fopen('php://memory', 'rb+');
        $request_body->shouldReceive('detach')->andReturns($request_body_stream);
        $incoming_request->shouldReceive('getBody')->andReturns($request_body);

        $file_information = \Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive('getLength')->andReturns(123456);
        $file_information->shouldReceive('getOffset')->andReturns(0);
        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns($file_information);
        $data_writer->shouldReceive('writeChunk')->andThrows(new CannotWriteFileException());

        $response = $server->handle($incoming_request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Tus-Resumable'));
    }

    public function testANotFoundErrorIsGivenWhenTheFileCanNotBeProvided(): void
    {
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('application/offset+octet-stream');

        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns(null);

        $response = $server->handle($incoming_request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testAnUploadCanBeTerminatedWhenTheTerminationExtensionIsEnabled(): void
    {
        $terminater = \Mockery::mock(TusTerminaterDataStore::class);
        $this->data_store->shouldReceive('getTerminater')->andReturns($terminater);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('DELETE');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');

        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns(\Mockery::mock(TusFileInformation::class));

        $terminater->shouldReceive('terminateUpload')->once();

        $response = $server->handle($incoming_request);

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testAnUploadCanNotBeTerminatedWhenTheTerminationExtensionIsNotEnabled(): void
    {
        $this->data_store->shouldReceive('getTerminater')->andReturns(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('DELETE');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');

        $response = $server->handle($incoming_request);

        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testAnUploadIsNotFinishedWhenAllDataHasNotBeenCopied(): void
    {
        $data_writer = \Mockery::mock(TusWriter::class);
        $this->data_store->shouldReceive('getWriter')->andReturns($data_writer);
        $finisher = \Mockery::mock(TusFinisherDataStore::class);
        $this->data_store->shouldReceive('getFinisher')->andReturns($finisher);
        $this->data_store->shouldReceive('getLocker')->andReturns(null);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incomplete_upload_request = \Mockery::mock(ServerRequestInterface::class);
        $incomplete_upload_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incomplete_upload_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incomplete_upload_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('application/offset+octet-stream');
        $incomplete_upload_request->shouldReceive('hasHeader')->with('Upload-Offset')->andReturns(true);
        $incomplete_upload_request->shouldReceive('getHeaderLine')->with('Upload-Offset')->andReturns(0);
        $request_body = \Mockery::mock(StreamInterface::class);
        $body_size    = 12;
        $request_body->shouldReceive('detach')->andReturns(fopen('php://memory', 'rb+'));
        $incomplete_upload_request->shouldReceive('getBody')->andReturns($request_body);

        $file_information = \Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive('getLength')->andReturns($body_size * 100);
        $file_information->shouldReceive('getOffset')->andReturns(0);
        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns($file_information);

        $data_writer->shouldReceive('writeChunk')->andReturns($body_size);

        $finisher->shouldReceive('finishUpload')->with($file_information)->never();

        $server->handle($incomplete_upload_request);
    }

    public function testALockedUploadIsNotOverwritten(): void
    {
        $locker = \Mockery::mock(TusLocker::class);
        $this->data_store->shouldReceive('getLocker')->andReturns($locker);
        $server = new TusServer($this->response_factory, $this->data_store);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('application/offset+octet-stream');
        $incoming_request->shouldReceive('hasHeader')->with('Upload-Offset')->andReturns(true);
        $incoming_request->shouldReceive('getHeaderLine')->with('Upload-Offset')->andReturns(0);
        $request_body = \Mockery::mock(StreamInterface::class);
        $request_body_stream = fopen('php://memory', 'rb+');
        $request_body->shouldReceive('detach')->andReturns($request_body_stream);
        $incoming_request->shouldReceive('getBody')->andReturns($request_body);

        $file_information = \Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive('getLength')->andReturns(123456);
        $file_information->shouldReceive('getOffset')->andReturns(0);
        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns($file_information);
        $this->file_information_provider->shouldReceive('getFileInformation')->andReturns(\Mockery::mock(TusFileInformation::class));

        $locker->shouldReceive('lock')->andReturns(false);
        $locker->shouldReceive('unlock');
        $this->data_store->shouldReceive('getWriter')->never();

        $response = $server->handle($incoming_request);

        $this->assertEquals(423, $response->getStatusCode());
    }
}
