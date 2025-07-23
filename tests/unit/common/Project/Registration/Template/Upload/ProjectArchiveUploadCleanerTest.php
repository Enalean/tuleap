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

namespace Tuleap\Project\Registration\Template\Upload;

use DateTimeImmutable;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\Project\ProjectByStatusStub;
use Tuleap\Test\Stubs\Project\ProjectRenameStub;
use Tuleap\Test\Stubs\Project\UpdateProjectStatusStub;
use Tuleap\Upload\UploadPathAllocator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectArchiveUploadCleanerTest extends TestCase
{
    use TemporaryTestDirectory;

    #[\Override]
    protected function setUp(): void
    {
    }

    public function testDeleteExpiredAndDanglingFilesAndProjects(): void
    {
        $base_dir =  $this->getTmpDir() . '/project/ongoing-upload';

        $first_file_uploaded_path  = $base_dir . '/1';
        $second_file_uploaded_path = $base_dir . '/2';
        $third_file_uploaded_path  = $base_dir . '/3';

        mkdir($base_dir, 0777, true);
        touch($first_file_uploaded_path);
        touch($second_file_uploaded_path);
        touch($third_file_uploaded_path);

        $project_101 = ProjectTestBuilder::aProject()->withId(101)->build();
        $project_102 = ProjectTestBuilder::aProject()->withId(102)->build();
        $project_103 = ProjectTestBuilder::aProject()->withId(103)->build();

        $delete_unused_file = DeleteUnusedFilesStub::build();
        $event_manager      = EventDispatcherStub::withIdentityCallback();

        $project_manager_update_status  = UpdateProjectStatusStub::build();
        $project_manager_rename_project = ProjectRenameStub::successfullyRenamedProject();
        $project_archive_cleaner        = new ProjectArchiveUploadCleaner(
            new UploadPathAllocator($base_dir),
            SearchFileUploadIdsAndProjectIdsStub::withFileAndProjectIds(
                [['id' => 1, 'project_id' => 101], ['id' => 3, 'project_id' => 103]]
            ),
            $delete_unused_file,
            $event_manager,
            ProjectByStatusStub::withProjects($project_101, $project_102, $project_103),
            $project_manager_update_status,
            $project_manager_rename_project,
        );

        self::assertFileExists($first_file_uploaded_path);
        self::assertFileExists($second_file_uploaded_path);
        self::assertFileExists($third_file_uploaded_path);

        $project_archive_cleaner->deleteUploadedDanglingProjectArchive(new DateTimeImmutable());

        self::assertFileExists($first_file_uploaded_path);
        self::assertFileDoesNotExist($second_file_uploaded_path);
        self::assertFileExists($third_file_uploaded_path);
        self::assertSame(1, $delete_unused_file->getCallCount());

        self::assertSame(1, $project_manager_update_status->getCallCount());
        self::assertSame(1, $project_manager_rename_project->getCallCount());
        self::assertSame(1, $event_manager->getCallCount());
    }
}
