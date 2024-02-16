<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Project\Registration\Template\Upload\Tus;

use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Project\Registration\CheckProjectRegistrationUserPermission;
use Tuleap\Project\Registration\CheckProjectRegistrationUserPermissionStub;
use Tuleap\Project\Registration\RestrictedUsersNotAllowedException;
use Tuleap\Project\Registration\Template\Upload\SearchFileUpload;
use Tuleap\Project\Registration\Template\Upload\SearchFileUploadStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;
use Tuleap\User\ProvideCurrentRequestUser;

final class ProjectFileBeingUploadedInformationProviderTest extends TestCase
{
    private const FILE_ONGOING_UPLOAD_ID = 10;
    private ServerRequestInterface|NullServerRequest $request;
    private CheckProjectRegistrationUserPermissionStub $permission_checker;
    private SearchFileUploadStub $file_upload_dao;
    private CurrentRequestUserProviderStub $current_user_provider;

    protected function setUp(): void
    {
        $this->request = (new NullServerRequest())->withAttribute('id', self::FILE_ONGOING_UPLOAD_ID);

        $this->file_upload_dao       = SearchFileUploadStub::withEmptyRow();
        $this->current_user_provider =   new CurrentRequestUserProviderStub(UserTestBuilder::buildWithId(101));
        $this->permission_checker    =  CheckProjectRegistrationUserPermissionStub::withoutException();
    }

    private function buildProjectFileBeingUploadedInformationProvider(
        SearchFileUpload $dao,
        ProvideCurrentRequestUser $current_user_provider,
        CheckProjectRegistrationUserPermission $permission_checker,
    ): ProjectFileBeingUploadedInformationProvider {
        return new ProjectFileBeingUploadedInformationProvider(
            new UploadPathAllocator('/tmp/upload/'),
            $dao,
            $current_user_provider,
            $permission_checker
        );
    }

    public function testItReturnsNullWhenTheRequestDoesNotProvideTheFileId(): void
    {
        $request = new NullServerRequest();

        $result = $this->buildProjectFileBeingUploadedInformationProvider(
            $this->file_upload_dao,
            $this->current_user_provider,
            $this->permission_checker
        )->getFileInformation($request);


        self::assertNull($result);
        self::assertSame(0, $this->permission_checker->getCheckUserCreateAProjectMethodCallCount());
    }

    public function testItThrowsAnExceptionIfThereIsNoCurrentUser(): void
    {
        self::expectException(NotFoundException::class);

        $this->buildProjectFileBeingUploadedInformationProvider(
            $this->file_upload_dao,
            new CurrentRequestUserProviderStub(null),
            $this->permission_checker
        )->getFileInformation($this->request);

        self::assertSame(0, $this->permission_checker->getCheckUserCreateAProjectMethodCallCount());
    }

    public function testItThrowsAnExceptionIfTheUserCannotCreateTheProject(): void
    {
        self::expectException(ForbiddenException::class);

        $this->buildProjectFileBeingUploadedInformationProvider(
            $this->file_upload_dao,
            $this->current_user_provider,
            CheckProjectRegistrationUserPermissionStub::withException(new RestrictedUsersNotAllowedException())
        )->getFileInformation($this->request);
    }

    public function testItReturnsNullWhenTheWantedFileDoesNotExist(): void
    {
        $result = $this->buildProjectFileBeingUploadedInformationProvider(
            $this->file_upload_dao,
            $this->current_user_provider,
            $this->permission_checker
        )->getFileInformation($this->request);


        self::assertNull($result);
        self::assertSame(1, $this->permission_checker->getCheckUserCreateAProjectMethodCallCount());
    }

    public function testItReturnsFileInformationOfTheFileBeingUploaded(): void
    {
        $file_size = 1275;
        $file_name = 'GR86';

        $result = $this->buildProjectFileBeingUploadedInformationProvider(
            SearchFileUploadStub::withExistingRow(
                ['id' => self::FILE_ONGOING_UPLOAD_ID, 'file_size' => $file_size, 'file_name' => $file_name]
            ),
            $this->current_user_provider,
            $this->permission_checker
        )->getFileInformation($this->request);

        $expected_result = new FileBeingUploadedInformation(
            self::FILE_ONGOING_UPLOAD_ID,
            $file_name,
            $file_size,
            0
        );

        self::assertEqualsCanonicalizing($expected_result, $result);
    }
}
