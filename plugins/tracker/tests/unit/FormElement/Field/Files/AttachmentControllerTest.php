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

namespace Tuleap\Tracker\FormElement\Field\Files;

use ForgeConfig;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Project_AccessPrivateException;
use Tracker_FileInfo;
use Tracker_FileInfo_InvalidFileInfoException;
use Tracker_FileInfo_UnauthorisedException;
use Tracker_FileInfoFactory;
use Tracker_FormElementFactory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;
use Tuleap\Tracker\FormElement\Field\Files\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\FormElement\Field\Files\Upload\Tus\FileBeingUploadedInformationProvider;
use Tuleap\Tracker\Test\Builders\Fields\FilesFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Upload\PathAllocator;
use URLVerification;

#[DisableReturnValueGenerationForTestDoubles]
final class AttachmentControllerTest extends TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private URLVerification&MockObject $url_verification;
    private FileOngoingUploadDao&MockObject $ongoing_upload_dao;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private PathAllocator&MockObject $path_allocator;
    private FilesField $field;
    private Tracker_FileInfoFactory&MockObject $file_info_factory;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_data_dir', $this->getTmpDir());

        $project                    = ProjectTestBuilder::aProject()->build();
        $tracker                    = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $this->field                = FilesFieldBuilder::aFileField(6875)->inTracker($tracker)->build();
        $this->url_verification     = $this->createMock(URLVerification::class);
        $this->ongoing_upload_dao   = $this->createMock(FileOngoingUploadDao::class);
        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->path_allocator       = $this->createMock(PathAllocator::class);
        $this->file_info_factory    = $this->createMock(Tracker_FileInfoFactory::class);

        mkdir($this->field->getRootPath(), 0700, true);
    }

    public function testFileCanBeDownloaded(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('preview', null)
            ->withAttribute('filename', 'Readme.mkd')
            ->withAttribute('Range', '');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn($row);
        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn($row);

        $path = $this->field->getRootPath();
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path . '/42');
        file_put_contents($path . '/42', $file_data);

        $this->form_element_factory->method('getUsedFormElementFieldById')->willReturn($this->field);
        $this->form_element_factory->method('isFieldAFileField')->willReturn(true);

        $user = UserTestBuilder::buildWithId(102);
        $this->field->setUserCanRead($user, true);

        $this->url_verification->method('userCanAccessProject');

        $this->file_info_factory->method('getById')->willReturn(null);

        $response = $this->buildController($user)->handle($server_request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($file_data, $response->getBody()->getContents());
    }

    public function testPreviewCanBeDownloaded(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('preview', true)
            ->withAttribute('filename', 'toto.png')
            ->withAttribute('Range', '');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'image/png',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'toto.png',
        ];
        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn($row);
        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn($row);

        $path = $this->field->getRootPath();
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path . '/42');
        mkdir($path . '/thumbnails', 0777, true);
        file_put_contents($path . '/42', $file_data);
        file_put_contents($path . '/thumbnails/42', $file_data);

        $this->form_element_factory->method('getUsedFormElementFieldById')->willReturn($this->field);
        $this->form_element_factory->method('isFieldAFileField')->willReturn(true);

        $user = UserTestBuilder::buildWithId(102);
        $this->field->setUserCanRead($user, true);

        $this->url_verification->method('userCanAccessProject');

        $this->file_info_factory->method('getById')->willReturn(null);

        $response = $this->buildController($user)->handle($server_request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($file_data, $response->getBody()->getContents());
    }

    public function testRequestIsRejectedWhenFileBeingUploadedCannotBeFound(): void
    {
        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn(null);

        $this->file_info_factory->method('getById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFileIsBeingUploadedButIsExpired(): void
    {
        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn(null);

        $this->file_info_factory->method('getById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFileIsBeingUploadedButBelongsToSomeoneElse(): void
    {
        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn(null);

        $this->file_info_factory->method('getById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFileIsNotAlreadyUploaded(): void
    {
        $file_data = 'ABC';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn($row);
        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn($row);

        $path = $this->field->getRootPath() . '/file';
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path);
        file_put_contents($path, $file_data);

        $this->file_info_factory->method('getById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFilenameInURLDoesNotMatchTheOneInDB(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'TaylorSwift.jpg',
        ];
        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn($row);
        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn($row);

        $path = $this->field->getRootPath() . '/file';
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path);
        file_put_contents($path, $file_data);

        $this->file_info_factory->method('getById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFieldCannotBeFound(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn($row);
        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn($row);

        $path = $this->field->getRootPath() . '/file';
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path);
        file_put_contents($path, $file_data);

        $this->form_element_factory->method('getUsedFormElementFieldById')->willReturn(null);

        $this->file_info_factory->method('getById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenFieldIsNotAFileField(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn($row);
        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn($row);

        $path = $this->field->getRootPath() . '/file';
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path);
        file_put_contents($path, $file_data);

        $this->form_element_factory->method('getUsedFormElementFieldById')->willReturn($this->field);
        $this->form_element_factory->method('isFieldAFileField')->willReturn(false);

        $this->file_info_factory->method('getById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheUserCanNotAccessTheProject(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn($row);
        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn($row);

        $path = $this->field->getRootPath() . '/file';
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path);
        file_put_contents($path, $file_data);

        $this->form_element_factory->method('getUsedFormElementFieldById')->willReturn($this->field);
        $this->form_element_factory->method('isFieldAFileField')->willReturn(true);

        $this->url_verification->method('userCanAccessProject')->willThrowException(new Project_AccessPrivateException());

        $this->file_info_factory->method('getById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheUserCanReadTheField(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'Readme.mkd',
        ];
        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn($row);
        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn($row);

        $path = $this->field->getRootPath() . '/file';
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path);
        file_put_contents($path, $file_data);

        $this->form_element_factory->method('getUsedFormElementFieldById')->willReturn($this->field);
        $this->form_element_factory->method('isFieldAFileField')->willReturn(true);

        $user = UserTestBuilder::buildWithId(102);
        $this->field->setUserCanRead($user, false);

        $this->url_verification->method('userCanAccessProject');

        $this->file_info_factory->method('getById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController($user)->handle($server_request);
    }

    public function testRequestIsRejectedIfFileDoesNotHavePreview(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('preview', true)
            ->withAttribute('filename', 'Readme.mkd')
            ->withAttribute('Range', '');

        $row = [
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filetype'     => 'text/plain',
            'field_id'     => 1001,
            'filesize'     => 5,
            'filename'     => 'readme.mkd',
        ];
        $this->ongoing_upload_dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn($row);
        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn($row);

        $path = $this->field->getRootPath();
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path . '/42');
        mkdir($path . '/thumbnails', 0777, true);
        file_put_contents($path . '/42', $file_data);

        $this->form_element_factory->method('getUsedFormElementFieldById')->willReturn($this->field);
        $this->form_element_factory->method('isFieldAFileField')->willReturn(true);

        $user = UserTestBuilder::buildWithId(102);
        $this->field->setUserCanRead($user, true);

        $this->url_verification->method('userCanAccessProject');

        $this->file_info_factory->method('getById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->buildController($user)->handle($server_request);
    }

    public function testRequestIsRejectedWhenFilenameInURLDoesNotMatchTheOneInDBForAlreadyLinkedAttachment(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $path = $this->field->getRootPath() . '/file';
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path);
        file_put_contents($path, $file_data);

        $fileinfo = new Tracker_FileInfo(2, $this->field, 101, '', 'TaylorSwift.jpg', 0, 'image/jpg');

        $this->file_info_factory->method('getById')->with(42)->willReturn($fileinfo);

        $this->expectException(NotFoundException::class);
        $this->buildController(UserTestBuilder::buildWithId(102))->handle($server_request);
    }

    public function testRequestIsRejectedWhenArtifactIsNotReachableByCurrentUser(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $path = $this->field->getRootPath() . '/file';
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path);
        file_put_contents($path, $file_data);

        $fileinfo = new Tracker_FileInfo(42, $this->field, 101, '', 'Readme.mkd', 0, 'text/plain');

        $this->file_info_factory->method('getById')->with(42)->willReturn($fileinfo);
        $current_user = UserTestBuilder::buildWithId(102);
        $this->file_info_factory->method('getArtifactByFileInfoIdAndUser')->with($current_user, 42)
            ->willThrowException(new Tracker_FileInfo_UnauthorisedException());

        $this->expectException(NotFoundException::class);
        $this->buildController($current_user)->handle($server_request);
    }

    public function testRequestIsRejectedWhenAttachmentIsNotLinkedInLatestChangeset(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('filename', 'Readme.mkd');

        $path = $this->field->getRootPath() . '/file';
        $this->path_allocator->method('getPathForItemBeingUploaded')->willReturn($path);
        file_put_contents($path, $file_data);

        $fileinfo = new Tracker_FileInfo(42, $this->field, 101, '', 'Readme.mkd', 0, 'text/plain');

        $this->file_info_factory->method('getById')->with(42)->willReturn($fileinfo);
        $current_user = UserTestBuilder::buildWithId(102);
        $this->file_info_factory->method('getArtifactByFileInfoIdAndUser')->with($current_user, 42)
            ->willThrowException(new Tracker_FileInfo_InvalidFileInfoException());

        $this->expectException(NotFoundException::class);
        $this->buildController($current_user)->handle($server_request);
    }

    public function testFileCanBeDownloadedForAlreadyLinkedAttachment(): void
    {
        $file_data = 'ABCDE';

        $server_request = (new NullServerRequest())
            ->withAttribute('id', 42)
            ->withAttribute('preview', null)
            ->withAttribute('filename', 'Readme.mkd')
            ->withAttribute('Range', '');

        $path = $this->field->getRootPath();
        file_put_contents($path . '/42', $file_data);

        $current_user = UserTestBuilder::buildWithId(102);
        $this->field->setUserCanRead($current_user, true);

        $this->url_verification->method('userCanAccessProject');

        $fileinfo = new Tracker_FileInfo(42, $this->field, 101, '', 'Readme.mkd', 0, 'text/plain');

        $this->file_info_factory->method('getById')->with(42)->willReturn($fileinfo);
        $this->file_info_factory->method('getArtifactByFileInfoIdAndUser')->with($current_user, 42);

        $response = $this->buildController($current_user)->handle($server_request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($file_data, $response->getBody()->getContents());
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
            new NoopSapiEmitter(),
            $current_request_user_provider,
        );
    }
}
