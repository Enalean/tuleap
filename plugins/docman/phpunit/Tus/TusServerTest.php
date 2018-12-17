<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Tus;

use Http\Message\MessageFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tuleap\Http\MessageFactoryBuilder;

class TusServerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MessageFactory
     */
    private $message_factory;
    private $file_provider;
    private $event_dispatcher;

    protected function setUp()
    {
        $this->message_factory  = MessageFactoryBuilder::build();
        $this->file_provider    = \Mockery::mock(TusFileProvider::class);
        $this->event_dispatcher = \Mockery::mock(TusEventDispatcher::class);
    }

    public function testInformationAboutTheServerCanBeGathered()
    {
        $server = new TusServer($this->message_factory, $this->file_provider, $this->event_dispatcher);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('OPTIONS');

        $this->file_provider->shouldReceive('getFile')->andReturns(\Mockery::mock(TusFile::class));

        $response = $server->handle($incoming_request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Tus-Version'));
    }

    public function testInformationAboutTheFileBeingUploadedCanBeGathered()
    {
        $server = new TusServer($this->message_factory, $this->file_provider, $this->event_dispatcher);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('HEAD');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');

        $file = \Mockery::mock(TusFile::class);
        $file->shouldReceive('getLength')->andReturns(123456);
        $file->shouldReceive('getOffset')->andReturns(123);
        $this->file_provider->shouldReceive('getFile')->andReturns($file);

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
    public function testFileCanBeUploaded($upload_offset, $body_content, $content_type)
    {
        $server = new TusServer($this->message_factory, $this->file_provider, $this->event_dispatcher);

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

        $file = \Mockery::mock(TusFile::class);
        $file->shouldReceive('getLength')->andReturns(123456);
        $file->shouldReceive('getOffset')->andReturns($upload_offset);
        $destination_resource = fopen('php://memory', 'rb+');
        $file->shouldReceive('getStream')->andReturns($destination_resource);
        $this->file_provider->shouldReceive('getFile')->andReturns($file);

        $this->event_dispatcher->shouldReceive('dispatch')->with(TusEvent::UPLOAD_COMPLETED, \Mockery::any())->once();

        $response = $server->handle($upload_request);

        $this->assertEquals(204, $response->getStatusCode());
        rewind($destination_resource);

        $this->assertEquals($body_content, stream_get_contents($destination_resource));
        $this->assertEquals($upload_offset + strlen($body_content), $response->getHeaderLine('Upload-Offset'));
    }

    public function validUploadRequestProvider()
    {
        return [
            [0, 'Content to upload', 'application/offset+octet-stream'],
            [0, 'Content', 'application/offset+octet-stream'],
            [1, 'Content to upload', 'application/offset+octet-stream'],
            [1, 'Content', 'application/offset+octet-stream'],
            [0, 'Content to upload', 'application/offset+octet-stream; charset=utf-8'],
        ];
    }

    public function testRequestWithANonSupportedVersionOfTheProtocolIsRejected()
    {
        $server = new TusServer($this->message_factory, $this->file_provider, $this->event_dispatcher);

        $this->file_provider->shouldReceive('getFile')->andReturns(\Mockery::mock(TusFile::class));

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('HEAD');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('0.2.2');

        $response = $server->handle($incoming_request);

        $this->assertEquals(412, $response->getStatusCode());
    }

    public function testAnUploadRequestWithAnIncorrectOffsetIsRejected()
    {
        $server = new TusServer($this->message_factory, $this->file_provider, $this->event_dispatcher);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('application/offset+octet-stream');
        $incoming_request->shouldReceive('hasHeader')->with('Upload-Offset')->andReturns(true);
        $incoming_request->shouldReceive('getHeaderLine')->with('Upload-Offset')->andReturns(10);

        $file = \Mockery::mock(TusFile::class);
        $file->shouldReceive('getOffset')->andReturns(20);
        $this->file_provider->shouldReceive('getFile')->andReturns($file);

        $response = $server->handle($incoming_request);

        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testAnUploadRequestWithoutTheOffsetIsRejected()
    {
        $server = new TusServer($this->message_factory, $this->file_provider, $this->event_dispatcher);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('application/offset+octet-stream');
        $incoming_request->shouldReceive('hasHeader')->with('Upload-Offset')->andReturns(false);

        $this->file_provider->shouldReceive('getFile')->andReturns(\Mockery::mock(TusFile::class));

        $response = $server->handle($incoming_request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAnUploadRequestWithAnIncorrectContentTypeIsRejected()
    {
        $file_provider = \Mockery::mock(TusFileProvider::class);
        $server = new TusServer($this->message_factory, $file_provider, $this->event_dispatcher);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('image/png');

        $file_provider->shouldReceive('getFile')->andReturns(\Mockery::mock(TusFile::class));

        $response = $server->handle($incoming_request);

        $this->assertEquals(415, $response->getStatusCode());
    }

    public function testAnErrorIsGivenWhenTheFileCanNotBeSaved()
    {
        $server = new TusServer($this->message_factory, $this->file_provider, $this->event_dispatcher);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('application/offset+octet-stream');
        $incoming_request->shouldReceive('hasHeader')->with('Upload-Offset')->andReturns(true);
        $incoming_request->shouldReceive('getHeaderLine')->with('Upload-Offset')->andReturns(0);
        $request_body = \Mockery::mock(StreamInterface::class);
        $request_body_stream = fopen('php://memory', 'rb+');
        fwrite($request_body_stream, 'Content to Upload');
        rewind($request_body_stream);
        $request_body->shouldReceive('detach')->andReturns($request_body_stream);
        $incoming_request->shouldReceive('getBody')->andReturns($request_body);

        $file = \Mockery::mock(TusFile::class);
        $file->shouldReceive('getLength')->andReturns(123456);
        $file->shouldReceive('getOffset')->andReturns(0);
        $destination_resource = fopen('php://memory', 'rb');
        $file->shouldReceive('getStream')->andReturns($destination_resource);
        $this->file_provider->shouldReceive('getFile')->andReturns($file);

        $response = $server->handle($incoming_request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Tus-Resumable'));
    }

    public function testANotFoundErrorIsGivenWhenTheFileCanNotBeProvided()
    {
        $server = new TusServer($this->message_factory, $this->file_provider, $this->event_dispatcher);

        $incoming_request = \Mockery::mock(ServerRequestInterface::class);
        $incoming_request->shouldReceive('getMethod')->andReturns('PATCH');
        $incoming_request->shouldReceive('getHeaderLine')->with('Tus-Resumable')->andReturns('1.0.0');
        $incoming_request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturns('application/offset+octet-stream');

        $this->file_provider->shouldReceive('getFile')->andReturns(null);

        $response = $server->handle($incoming_request);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
