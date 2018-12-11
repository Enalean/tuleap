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

require_once __DIR__ . '/../bootstrap.php';

use Http\Message\MessageFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Tuleap\Http\MessageFactoryBuilder;

class TusServerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MessageFactory
     */
    private $message_factory;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->message_factory = MessageFactoryBuilder::build();
    }

    public function testInformationAboutTheServerCanBeGathered()
    {
        $server = new TusServer($this->message_factory);

        $incoming_request = $this->message_factory->createRequest('OPTIONS', '/tus-server');

        $response = $server->serve($incoming_request, \Mockery::mock(TusFile::class));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Tus-Version'));
    }

    public function testInformationAboutTheFileBeingUploadedCanBeGathered()
    {
        $server = new TusServer($this->message_factory);

        $incoming_request = $this->message_factory->createRequest(
            'HEAD',
            '/tus-server',
            ['Tus-Resumable' => '1.0.0']
        );
        $file = \Mockery::mock(TusFile::class);
        $file->shouldReceive('getLength')->andReturns(123456);
        $file->shouldReceive('getOffset')->andReturns(123);

        $response = $server->serve($incoming_request, $file);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Tus-Resumable'));
        $this->assertEquals(123456, $response->getHeaderLine('Upload-Length'));
        $this->assertEquals(123, $response->getHeaderLine('Upload-Offset'));
        $this->assertEquals('no-cache', $response->getHeaderLine('Cache-Control'));
    }

    /**
     * @dataProvider validUploadRequestProvider
     */
    public function testFileCanBeUploaded(RequestInterface $upload_request)
    {
        $server = new TusServer($this->message_factory);

        $initial_offset              = (int) $upload_request->getHeaderLine('Upload-Offset');
        $upload_request_data_to_save = $upload_request->getBody()->getContents();
        $upload_request->getBody()->rewind();

        $file = \Mockery::mock(TusFile::class);
        $file->shouldReceive('getLength')->andReturns(123456);
        $file->shouldReceive('getOffset')->andReturns($initial_offset);
        $destination_resource = fopen('php://memory', 'rb+');
        $file->shouldReceive('getStream')->andReturns($destination_resource);

        $response = $server->serve($upload_request, $file);

        $this->assertEquals(204, $response->getStatusCode());
        rewind($destination_resource);

        $this->assertEquals($upload_request_data_to_save, stream_get_contents($destination_resource));
        $this->assertEquals($initial_offset + strlen($upload_request_data_to_save), $response->getHeaderLine('Upload-Offset'));
    }

    public function validUploadRequestProvider()
    {
        return [
            [
                $this->message_factory->createRequest(
                    'PATCH',
                    '/tus-server',
                    [
                        'Tus-Resumable' => '1.0.0',
                        'Content-Type'  => 'application/offset+octet-stream',
                        'Upload-Offset' => 0
                    ],
                    'Content to upload'
                )
            ],
            [
                $this->message_factory->createRequest(
                    'PATCH',
                    '/tus-server',
                    [
                        'Tus-Resumable' => '1.0.0',
                        'Content-Type'  => 'application/offset+octet-stream',
                        'Upload-Offset' => 1
                    ],
                    'Content to upload'
                )
            ],
            [
                $this->message_factory->createRequest(
                    'PATCH',
                    '/tus-server',
                    [
                        'Tus-Resumable' => '1.0.0',
                        'Content-Type'  => 'application/offset+octet-stream; charset=utf-8',
                        'Upload-Offset' => 0
                    ],
                    'Content to upload'
                )
            ],
        ];
    }

    public function testRequestWithANonSupportedVersionOfTheProtocolIsRejected()
    {
        $server = new TusServer($this->message_factory);

        $incoming_request = $this->message_factory->createRequest(
            'HEAD',
            '/tus-server',
            ['Tus-Resumable' => '0.2.2']
        );

        $response = $server->serve($incoming_request, \Mockery::mock(TusFile::class));

        $this->assertEquals(412, $response->getStatusCode());
    }

    public function testAnUploadRequestWithAnIncorrectOffsetIsRejected()
    {
        $server = new TusServer($this->message_factory);

        $incoming_request = $this->message_factory->createRequest(
            'PATCH',
            '/tus-server',
            [
                'Tus-Resumable' => '1.0.0',
                'Content-Type'  => 'application/offset+octet-stream',
                'Upload-Offset' => 10
            ]
        );
        $file = \Mockery::mock(TusFile::class);
        $file->shouldReceive('getOffset')->andReturns(20);
        $response = $server->serve($incoming_request, $file);

        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testAnUploadRequestWithoutTheOffsetIsRejected()
    {
        $server = new TusServer($this->message_factory);

        $incoming_request = $this->message_factory->createRequest(
            'PATCH',
            '/tus-server',
            [
                'Tus-Resumable' => '1.0.0',
                'Content-Type'  => 'application/offset+octet-stream'
            ]
        );

        $response = $server->serve($incoming_request, \Mockery::mock(TusFile::class));

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAnUploadRequestWithAnIncorrectContentTypeIsRejected()
    {
        $server = new TusServer($this->message_factory);

        $incoming_request = $this->message_factory->createRequest(
            'PATCH',
            '/tus-server',
            [
                'Tus-Resumable' => '1.0.0',
                'Content-Type'  => 'image/png'
            ]
        );

        $response = $server->serve($incoming_request, \Mockery::mock(TusFile::class));

        $this->assertEquals(415, $response->getStatusCode());
    }

    public function testAnErrorIsGivenWhenTheFileCanNotBeSaved()
    {
        $server = new TusServer($this->message_factory);

        $incoming_request = $this->message_factory->createRequest(
            'PATCH',
            '/tus-server',
            [
                'Tus-Resumable' => '1.0.0',
                'Content-Type'  => 'application/offset+octet-stream',
                'Upload-Offset' => 0
            ],
            'Content to upload'
        );

        $file = \Mockery::mock(TusFile::class);
        $file->shouldReceive('getLength')->andReturns(123456);
        $file->shouldReceive('getOffset')->andReturns(0);
        $destination_resource = fopen('php://memory', 'rb');
        $file->shouldReceive('getStream')->andReturns($destination_resource);

        $response = $server->serve($incoming_request, $file);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Tus-Resumable'));
    }
}
