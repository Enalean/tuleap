<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\File;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PFUser;
use Project;
use Project_AccessException;
use Psr\Http\Message\ServerRequestInterface;
use Tracker;
use Tracker_FileInfo;
use Tracker_FileInfo_InvalidFileInfoException;
use Tracker_FileInfo_UnauthorisedException;
use Tracker_FormElement_Field_File;
use Tracker_FormElementFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\FormElement\Field\File\Upload\Tus\FileBeingUploadedInformationProvider;
use Tuleap\Upload\PathAllocator;
use URLVerification;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AttachmentControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|URLVerification
     */
    private $url_verification;
    /**
     * @var Mockery\MockInterface|FileOngoingUploadDao
     */
    private $ongoing_upload_dao;
    /**
     * @var Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var Mockery\MockInterface|PathAllocator
     */
    private $path_allocator;
    /**
     * @var Mockery\MockInterface|Tracker_FormElement_Field_File
     */
    private $field;
    /**
     * @var Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\MockInterface|\Tracker_FileInfoFactory
     */
    private $file_info_factory;

    protected function setUp(): void
    {
        $this->field                = Mockery::mock(Tracker_FormElement_Field_File::class);
        $this->tracker              = Mockery::mock(Tracker::class);
        $this->project              = Mockery::mock(Project::class);
        $this->url_verification     = Mockery::mock(URLVerification::class);
        $this->ongoing_upload_dao   = Mockery::mock(FileOngoingUploadDao::class);
        $this->form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $this->path_allocator       = Mockery::mock(PathAllocator::class);
        $this->file_info_factory    = Mockery::mock(\Tracker_FileInfoFactory::class);
    }

    public function testFileCanBeDownloaded(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('preview')
            ->andReturn(null);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');
        $server_request
            ->shouldReceive('getHeaderLine')
            ->with('Range')
            ->andReturn('');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => $row,
                'searchFileOngoingUploadById'                        => $row,
            ]
        );

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path . '/42']);
        $this->field->shouldReceive(['getRootPath' => $path]);
        mkdir($path);
        file_put_contents($path . '/42', $file_data);

        $this->form_element_factory->shouldReceive(
            [
                'getUsedFormElementFieldById' => $this->field,
                'isFieldAFileField'           => true,
            ]
        );

        $this->field->shouldReceive(
            [
                'getTracker'  => $this->tracker,
                'userCanRead' => true,
            ]
        );

        $this->tracker->shouldReceive(['getProject' => $this->project]);

        $this->project->shouldReceive(['isError' => false]);

        $this->url_verification->shouldReceive('userCanAccessProject');

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $response = $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($file_data, $response->getBody()->getContents());
    }

    public function testPreviewCanBeDownloaded(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('preview')
            ->andReturn(true);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('toto.png');
        $server_request
            ->shouldReceive('getHeaderLine')
            ->with('Range')
            ->andReturn('');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'image/png',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'toto.png',
        ];
        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => $row,
                'searchFileOngoingUploadById'                        => $row,
            ]
        );

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path . '/42']);
        $this->field->shouldReceive(['getRootPath' => $path]);
        mkdir($path . '/thumbnails', 0777, true);
        file_put_contents($path . '/42', $file_data);
        file_put_contents($path . '/thumbnails/42', $file_data);

        $this->form_element_factory->shouldReceive(
            [
                'getUsedFormElementFieldById' => $this->field,
                'isFieldAFileField'           => true,
            ]
        );

        $this->field->shouldReceive(
            [
                'getTracker'  => $this->tracker,
                'userCanRead' => true,
            ]
        );

        $this->tracker->shouldReceive(['getProject' => $this->project]);

        $this->project->shouldReceive(['isError' => false]);

        $this->url_verification->shouldReceive('userCanAccessProject');

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $response = $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($file_data, $response->getBody()->getContents());
    }

    public function testRequestIsRejectedWhenFileBeingUploadedCannotBeFound(): void
    {
        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => null,
            ]
        );

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFileIsBeingUploadedButIsExpired(): void
    {
        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => null,
            ]
        );

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFileIsBeingUploadedButBelongsToSomeoneElse(): void
    {
        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => null,
            ]
        );

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFileIsNotAlreadyUploaded(): void
    {
        $file_data = 'ABC';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => $row,
                'searchFileOngoingUploadById'                        => $row,
            ]
        );

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path]);
        file_put_contents($path, $file_data);

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFilenameInURLDoesNotMatchTheOneInDB(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'TaylorSwift.jpg',
        ];
        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => $row,
                'searchFileOngoingUploadById'                        => $row,
            ]
        );

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path]);
        file_put_contents($path, $file_data);

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFieldCannotBeFound(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => $row,
                'searchFileOngoingUploadById'                        => $row,
            ]
        );

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path]);
        file_put_contents($path, $file_data);

        $this->form_element_factory->shouldReceive(
            [
                'getUsedFormElementFieldById' => null,
            ]
        );

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFieldIsNotAFileField(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => $row,
                'searchFileOngoingUploadById'                        => $row,
            ]
        );

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path]);
        file_put_contents($path, $file_data);

        $this->form_element_factory->shouldReceive(
            [
                'getUsedFormElementFieldById' => $this->field,
                'isFieldAFileField'           => false,
            ]
        );

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenTrackerCannotBeFound(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => $row,
                'searchFileOngoingUploadById'                        => $row,
            ]
        );

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path]);
        file_put_contents($path, $file_data);

        $this->form_element_factory->shouldReceive(
            [
                'getUsedFormElementFieldById' => $this->field,
                'isFieldAFileField'           => true,
            ]
        );

        $this->field->shouldReceive(
            [
                'getTracker' => null,
            ]
        );

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheUserCanNotAccessTheProject(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => $row,
                'searchFileOngoingUploadById'                        => $row,
            ]
        );

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path]);
        file_put_contents($path, $file_data);

        $this->form_element_factory->shouldReceive(
            [
                'getUsedFormElementFieldById' => $this->field,
                'isFieldAFileField'           => true,
            ]
        );

        $this->field->shouldReceive(
            [
                'getTracker' => $this->tracker,
            ]
        );

        $this->tracker->shouldReceive(['getProject' => $this->project]);

        $this->project->shouldReceive(['isError' => false]);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->andThrow(Mockery::mock(Project_AccessException::class));

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheUserCanReadTheField(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => $row,
                'searchFileOngoingUploadById'                        => $row,
            ]
        );

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path]);
        file_put_contents($path, $file_data);

        $this->form_element_factory->shouldReceive(
            [
                'getUsedFormElementFieldById' => $this->field,
                'isFieldAFileField'           => true,
            ]
        );

        $this->field->shouldReceive(
            [
                'getTracker'  => $this->tracker,
                'userCanRead' => false,
            ]
        );

        $this->tracker->shouldReceive(['getProject' => $this->project]);

        $this->project->shouldReceive(['isError' => false]);

        $this->url_verification->shouldReceive('userCanAccessProject');

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedIfFileDoesNotHavePreview(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('preview')
            ->andReturn(true);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('readme.mkd');
        $server_request
            ->shouldReceive('getHeaderLine')
            ->with('Range')
            ->andReturn('');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'readme.mkd',
        ];
        $this->ongoing_upload_dao->shouldReceive(
            [
                'searchFileOngoingUploadByIDUserIDAndExpirationDate' => $row,
                'searchFileOngoingUploadById'                        => $row,
            ]
        );

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path . '/42']);
        $this->field->shouldReceive(['getRootPath' => $path]);
        mkdir($path . '/thumbnails', 0777, true);
        file_put_contents($path . '/42', $file_data);

        $this->form_element_factory->shouldReceive(
            [
                'getUsedFormElementFieldById' => $this->field,
                'isFieldAFileField'           => true,
            ]
        );

        $this->field->shouldReceive(
            [
                'getTracker'  => $this->tracker,
                'userCanRead' => true,
            ]
        );

        $this->tracker->shouldReceive(['getProject' => $this->project]);

        $this->project->shouldReceive(['isError' => false]);

        $this->url_verification->shouldReceive('userCanAccessProject');

        $this->file_info_factory->shouldReceive('getById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFilenameInURLDoesNotMatchTheOneInDBForAlreadyLinkedAttachment(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path]);
        file_put_contents($path, $file_data);

        $fileinfo = Mockery::mock(Tracker_FileInfo::class);
        $fileinfo->shouldReceive('getFilename')->andReturn('TaylorSwift.jpg');

        $this->file_info_factory->shouldReceive('getById')->with(42)->andReturn($fileinfo);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenArtifactIsNotReachableByCurrentUser(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path]);
        file_put_contents($path, $file_data);

        $fileinfo = Mockery::mock(Tracker_FileInfo::class);
        $fileinfo->shouldReceive(
            [
                'getFilename' => 'Readme.mkd',
                'getId'       => 42,
            ]
        );

        $this->file_info_factory
            ->shouldReceive('getById')
            ->with(42)
            ->andReturn($fileinfo);
        $current_user = UserTestBuilder::buildWithId(102);
        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdAndUser')
            ->with($current_user, 42)
            ->andThrow(Mockery::mock(Tracker_FileInfo_UnauthorisedException::class));

        $this->expectException(NotFoundException::class);
        $this->buildController($current_user)->handle($server_request);
    }

    public function testRequestIsRejectedWhenAttachmentIsNotLinkedInLatestChangeset(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');

        $path = vfsStream::setup()->url() . '/file';
        $this->path_allocator->shouldReceive(['getPathForItemBeingUploaded' => $path]);
        file_put_contents($path, $file_data);

        $fileinfo = Mockery::mock(Tracker_FileInfo::class);
        $fileinfo->shouldReceive(
            [
                'getFilename' => 'Readme.mkd',
                'getId'       => 42,
            ]
        );

        $this->file_info_factory
            ->shouldReceive('getById')
            ->with(42)
            ->andReturn($fileinfo);
        $current_user = UserTestBuilder::buildWithId(102);
        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdAndUser')
            ->with($current_user, 42)
            ->andThrow(Mockery::mock(Tracker_FileInfo_InvalidFileInfoException::class));

        $this->expectException(NotFoundException::class);
        $this->buildController($current_user)->handle($server_request);
    }

    public function testFileCanBeDownloadedForAlreadyLinkedAttachment(): void
    {
        $file_data = 'ABCDE';

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('preview')
            ->andReturn(null);
        $server_request
            ->shouldReceive('getAttribute')
            ->with('filename')
            ->andReturn('Readme.mkd');
        $server_request
            ->shouldReceive('getHeaderLine')
            ->with('Range')
            ->andReturn('');

        $path = vfsStream::setup()->url() . '/file';
        mkdir($path);
        file_put_contents($path . '/42', $file_data);

        $this->field->shouldReceive(
            [
                'getTracker'  => $this->tracker,
                'userCanRead' => true,
            ]
        );

        $this->tracker->shouldReceive(['getProject' => $this->project]);

        $this->project->shouldReceive(['isError' => false]);

        $this->url_verification->shouldReceive('userCanAccessProject');

        $fileinfo = Mockery::mock(Tracker_FileInfo::class);
        $fileinfo->shouldReceive(
            [
                'getFilename' => 'Readme.mkd',
                'getId'       => 42,
                'getField'    => $this->field,
                'getPath'     => $path . '/42',
                'getFiletype' => 'text/plain',
            ]
        );

        $this->file_info_factory
            ->shouldReceive('getById')
            ->with(42)
            ->andReturn($fileinfo);
        $current_user = UserTestBuilder::buildWithId(102);
        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdAndUser')
            ->with($current_user, 42);

        $response = $this->buildController($current_user)->handle($server_request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($file_data, $response->getBody()->getContents());
    }

    public function testFileCannotBeDownloadedWhenNoCurrentUserIsFoundWithTheRequest(): void
    {
        $this->expectException(NotFoundException::class);
        $this->buildController(null)->handle(new NullServerRequest());
    }

    private function buildController(?PFUser $current_user): AttachmentController
    {
        $current_request_user_provider = new CurrentRequestUserProviderStub($current_user);
        return new AttachmentController(
            $this->url_verification,
            $this->ongoing_upload_dao,
            $this->form_element_factory,
            new FileBeingUploadedInformationProvider(
                $this->path_allocator,
                $this->ongoing_upload_dao,
                $current_request_user_provider
            ),
            $this->file_info_factory,
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            Mockery::mock(EmitterInterface::class),
            $current_request_user_provider,
        );
    }
}
