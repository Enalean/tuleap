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

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template\Upload\Tus;

use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Project\Registration\Template\Upload\DeleteFileUploadStub;
use Tuleap\Project\Registration\Template\Upload\FinishFileUploadPostActionStub;
use Tuleap\Project\Registration\Template\Upload\SearchFileUpload;
use Tuleap\Project\Registration\Template\Upload\SearchFileUploadStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;
use Tuleap\User\ProvideCurrentRequestUser;

final class ProjectFileUploadFinisherTest extends TestCase
{
    use \Tuleap\TemporaryTestDirectory;

    private const PROJECT_ID = 1001;
    private const USER_ID    = 102;

    private string $base_path;
    private UploadPathAllocator $path_allocator;
    private DeleteFileUploadStub $file_upload_dao;
    private FinishFileUploadPostActionStub $finish_file_upload_post_action;
    private \PFUser $user;
    private NullServerRequest $request;


    protected function setUp(): void
    {
        $this->base_path       = $this->getTmpDir();
        $this->path_allocator  = new UploadPathAllocator($this->base_path);
        $this->file_upload_dao = DeleteFileUploadStub::build();

        $this->finish_file_upload_post_action = FinishFileUploadPostActionStub::build();

        $this->user = UserTestBuilder::buildWithId(self::USER_ID);

        $this->request = new NullServerRequest();
    }

    private function getFinisher(
        SearchFileUpload $search_file_upload,
        ProvideCurrentRequestUser $current_request_user,
    ): ProjectFileUploadFinisher {
        return new ProjectFileUploadFinisher(
            $this->file_upload_dao,
            $search_file_upload,
            $this->path_allocator,
            $this->finish_file_upload_post_action,
            $current_request_user,
        );
    }

    public function testItDeletesTheSavedFileInDBWhenTheUploadIsFinished(): void
    {
        $file_information = new FileBeingUploadedInformation(15, "test.zip", 996, 0);
        $item_path        = $this->path_allocator->getPathForItemBeingUploaded($file_information);

        copy(__DIR__ . "/_fixtures/test.zip", $item_path);

        $this
            ->getFinisher(
                SearchFileUploadStub::withExistingRow(['project_id' => self::PROJECT_ID]),
                new CurrentRequestUserProviderStub($this->user),
            )
            ->finishUpload($this->request, $file_information);
        self::assertSame(1, $this->file_upload_dao->getDeleteByIdMethodCallCount());
    }

    public function testItPostProcessTheUploadedFile(): void
    {
        $file_information = new FileBeingUploadedInformation(15, "test.zip", 996, 0);
        $item_path        = $this->path_allocator->getPathForItemBeingUploaded($file_information);

        copy(__DIR__ . "/_fixtures/test.zip", $item_path);

        $this
            ->getFinisher(
                SearchFileUploadStub::withExistingRow(['project_id' => self::PROJECT_ID]),
                new CurrentRequestUserProviderStub($this->user),
            )
            ->finishUpload($this->request, $file_information);

        self::assertEquals($item_path, $this->finish_file_upload_post_action->getProcessedFilename());
        self::assertEquals(self::PROJECT_ID, $this->finish_file_upload_post_action->getProcessedProjectId());
        self::assertEquals(self::USER_ID, $this->finish_file_upload_post_action->getProcessedUserId());
        self::assertTrue(file_exists($item_path));
    }

    public function testItThrowsWhenThereIsNoCurrentUser(): void
    {
        $file_information = new FileBeingUploadedInformation(15, "test.zip", 996, 0);
        $item_path        = $this->path_allocator->getPathForItemBeingUploaded($file_information);

        copy(__DIR__ . "/_fixtures/test.zip", $item_path);

        $this->expectException(ForbiddenException::class);

        $this
            ->getFinisher(
                SearchFileUploadStub::withExistingRow(['project_id' => self::PROJECT_ID]),
                new CurrentRequestUserProviderStub(null),
            )
            ->finishUpload($this->request, $file_information);

        self::assertFalse(file_exists($item_path));
        self::assertSame(1, $this->file_upload_dao->getDeleteByIdMethodCallCount());
    }

    public function testItThrowsWhenProvidedFileIsNotAnArchive(): void
    {
        $file_information = new FileBeingUploadedInformation(15, "filename.md", 996, 0);
        $item_path        = $this->path_allocator->getPathForItemBeingUploaded($file_information);
        file_put_contents($item_path, "#test");

        $this->expectException(FileIsNotAnArchiveException::class);

        $this
            ->getFinisher(
                SearchFileUploadStub::withExistingRow(['project_id' => self::PROJECT_ID]),
                new CurrentRequestUserProviderStub($this->user),
            )
            ->finishUpload($this->request, $file_information);

        self::assertFalse(file_exists($item_path));
        self::assertSame(1, $this->file_upload_dao->getDeleteByIdMethodCallCount());
    }

    public function testItThrowsWhenProjectCannotBeFound(): void
    {
        $file_information = new FileBeingUploadedInformation(15, "test.zip", 996, 0);
        $item_path        = $this->path_allocator->getPathForItemBeingUploaded($file_information);

        copy(__DIR__ . "/_fixtures/test.zip", $item_path);

        $this->expectException(ProjectNotFoundException::class);

        $this
            ->getFinisher(
                SearchFileUploadStub::withEmptyRow(),
                new CurrentRequestUserProviderStub($this->user),
            )
            ->finishUpload($this->request, $file_information);

        self::assertFalse(file_exists($item_path));
        self::assertSame(1, $this->file_upload_dao->getDeleteByIdMethodCallCount());
    }
}
