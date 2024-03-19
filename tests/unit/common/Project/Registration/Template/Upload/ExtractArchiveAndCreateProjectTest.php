<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;

use ColinODell\PsrTestLogger\TestLogger;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Queue\WorkerEvent;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\Project\ImportFromArchiveStub;
use Tuleap\Test\Stubs\Project\Registration\Template\Upload\ActivateProjectAfterArchiveImportStub;
use Tuleap\Test\Stubs\Project\Registration\Template\Upload\ArchiveUploadedArchiveStub;
use Tuleap\Test\Stubs\Project\Registration\Template\Upload\SaveUploadedArchiveForProjectStub;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\ForceLoginStub;
use function Psl\Filesystem\create_directory;

final class ExtractArchiveAndCreateProjectTest extends TestCase
{
    use TemporaryTestDirectory;
    use ForgeConfigSandbox;

    private const PROJECT_ID = 1001;
    private const USER_ID    = 102;

    private string $upload;
    private \Project $project;
    private \PFUser $user;

    protected function setUp(): void
    {
        $tmp = $this->getTmpDir() . '/tmp';
        create_directory($tmp);
        \ForgeConfig::set('tmp_dir', $tmp);

        $this->upload = $this->getTmpDir() . '/upload';
        create_directory($this->upload);
        \Psl\Filesystem\copy(__DIR__ . "/Tus/_fixtures/test.zip", $this->upload . '/test.zip');

        $this->project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->build();

        $this->user = UserTestBuilder::buildWithId(self::USER_ID);
    }

    public function testProjectIdIsNotInPayload(): void
    {
        $this->expectException(\Exception::class);

        ExtractArchiveAndCreateProject::fromEvent(
            new WorkerEvent(
                new NullLogger(),
                [
                    'event_name' => ExtractArchiveAndCreateProject::TOPIC,
                    'payload' => [],
                ]
            ),
            ImportFromArchiveStub::buildWithSuccessfulImport(),
            ActivateProjectAfterArchiveImportStub::build(),
            ProjectByIDFactoryStub::buildWith($this->project),
            RetrieveUserByIdStub::withUser($this->user),
            ForceLoginStub::build(),
            ArchiveUploadedArchiveStub::notExpectedToBeCalled(),
            SaveUploadedArchiveForProjectStub::build(),
        );
    }

    public function testProjectIdIsNotAnInt(): void
    {
        $this->expectException(\Exception::class);

        ExtractArchiveAndCreateProject::fromEvent(
            new WorkerEvent(
                new NullLogger(),
                [
                    'event_name' => ExtractArchiveAndCreateProject::TOPIC,
                    'payload' => ['project_id' => 'a string'],
                ]
            ),
            ImportFromArchiveStub::buildWithSuccessfulImport(),
            ActivateProjectAfterArchiveImportStub::build(),
            ProjectByIDFactoryStub::buildWith($this->project),
            RetrieveUserByIdStub::withUser($this->user),
            ForceLoginStub::build(),
            ArchiveUploadedArchiveStub::notExpectedToBeCalled(),
            SaveUploadedArchiveForProjectStub::build(),
        );
    }

    public function testFilenameIsNotInPayload(): void
    {
        $this->expectException(\Exception::class);

        ExtractArchiveAndCreateProject::fromEvent(
            new WorkerEvent(
                new NullLogger(),
                [
                    'event_name' => ExtractArchiveAndCreateProject::TOPIC,
                    'payload' => ['project_id' => self::PROJECT_ID],
                ]
            ),
            ImportFromArchiveStub::buildWithSuccessfulImport(),
            ActivateProjectAfterArchiveImportStub::build(),
            ProjectByIDFactoryStub::buildWith($this->project),
            RetrieveUserByIdStub::withUser($this->user),
            ForceLoginStub::build(),
            ArchiveUploadedArchiveStub::notExpectedToBeCalled(),
            SaveUploadedArchiveForProjectStub::build(),
        );
    }

    public function testFilenameIsNotIAString(): void
    {
        $this->expectException(\Exception::class);

        ExtractArchiveAndCreateProject::fromEvent(
            new WorkerEvent(
                new NullLogger(),
                [
                    'event_name' => ExtractArchiveAndCreateProject::TOPIC,
                    'payload' => ['project_id' => self::PROJECT_ID, 'filename' => []],
                ]
            ),
            ImportFromArchiveStub::buildWithSuccessfulImport(),
            ActivateProjectAfterArchiveImportStub::build(),
            ProjectByIDFactoryStub::buildWith($this->project),
            RetrieveUserByIdStub::withUser($this->user),
            ForceLoginStub::build(),
            ArchiveUploadedArchiveStub::notExpectedToBeCalled(),
            SaveUploadedArchiveForProjectStub::build(),
        );
    }

    public function testFilenameIsAnEmptyString(): void
    {
        $this->expectException(\Exception::class);

        ExtractArchiveAndCreateProject::fromEvent(
            new WorkerEvent(
                new NullLogger(),
                [
                    'event_name' => ExtractArchiveAndCreateProject::TOPIC,
                    'payload' => ['project_id' => self::PROJECT_ID, 'filename' => '', 'user_id' => 123],
                ]
            ),
            ImportFromArchiveStub::buildWithSuccessfulImport(),
            ActivateProjectAfterArchiveImportStub::build(),
            ProjectByIDFactoryStub::buildWith($this->project),
            RetrieveUserByIdStub::withUser($this->user),
            ForceLoginStub::build(),
            ArchiveUploadedArchiveStub::notExpectedToBeCalled(),
            SaveUploadedArchiveForProjectStub::build(),
        );
    }

    public function testUserIdIsNotInPayload(): void
    {
        $this->expectException(\Exception::class);

        ExtractArchiveAndCreateProject::fromEvent(
            new WorkerEvent(
                new NullLogger(),
                [
                    'event_name' => ExtractArchiveAndCreateProject::TOPIC,
                    'payload' => [
                        'project_id' => self::PROJECT_ID,
                        'filename' => $this->upload . '/test.zip',
                    ],
                ]
            ),
            ImportFromArchiveStub::buildWithSuccessfulImport(),
            ActivateProjectAfterArchiveImportStub::build(),
            ProjectByIDFactoryStub::buildWith($this->project),
            RetrieveUserByIdStub::withUser($this->user),
            ForceLoginStub::build(),
            ArchiveUploadedArchiveStub::notExpectedToBeCalled(),
            SaveUploadedArchiveForProjectStub::build(),
        );
    }

    public function testUserIdIsNotAnInt(): void
    {
        $this->expectException(\Exception::class);

        ExtractArchiveAndCreateProject::fromEvent(
            new WorkerEvent(
                new NullLogger(),
                [
                    'event_name' => ExtractArchiveAndCreateProject::TOPIC,
                    'payload' => [
                        'project_id' => self::PROJECT_ID,
                        'filename'   => $this->upload . '/test.zip',
                        'user_id'    => 'a string',
                    ],
                ]
            ),
            ImportFromArchiveStub::buildWithSuccessfulImport(),
            ActivateProjectAfterArchiveImportStub::build(),
            ProjectByIDFactoryStub::buildWith($this->project),
            RetrieveUserByIdStub::withUser($this->user),
            ForceLoginStub::build(),
            ArchiveUploadedArchiveStub::notExpectedToBeCalled(),
            SaveUploadedArchiveForProjectStub::build(),
        );
    }

    public function testProjectDoesNotExists(): void
    {
        $this->expectException(\Exception::class);

        $action = ExtractArchiveAndCreateProject::fromEvent(
            new WorkerEvent(
                new NullLogger(),
                [
                    'event_name' => ExtractArchiveAndCreateProject::TOPIC,
                    'payload' => [
                        'project_id' => self::PROJECT_ID,
                        'filename'   => 'test.zip',
                        'user_id'    => self::USER_ID,
                    ],
                ]
            ),
            ImportFromArchiveStub::buildWithSuccessfulImport(),
            ActivateProjectAfterArchiveImportStub::build(),
            ProjectByIDFactoryStub::buildWithoutProject(),
            RetrieveUserByIdStub::withUser($this->user),
            ForceLoginStub::build(),
            ArchiveUploadedArchiveStub::notExpectedToBeCalled(),
            SaveUploadedArchiveForProjectStub::build(),
        );

        $action->process();
    }

    public function testProcessHappyPath(): void
    {
        $logger = new TestLogger();

        $activator = ActivateProjectAfterArchiveImportStub::build();

        $force_login = ForceLoginStub::build();

        $archive_for_project_dao = SaveUploadedArchiveForProjectStub::build();

        $action = ExtractArchiveAndCreateProject::fromEvent(
            new WorkerEvent(
                $logger,
                [
                    'event_name' => ExtractArchiveAndCreateProject::TOPIC,
                    'payload' => [
                        'project_id' => self::PROJECT_ID,
                        'filename'   => $this->upload . '/test.zip',
                        'user_id'    => self::USER_ID,
                    ],
                ]
            ),
            ImportFromArchiveStub::buildWithSuccessfulImport(),
            $activator,
            ProjectByIDFactoryStub::buildWith($this->project),
            RetrieveUserByIdStub::withUser($this->user),
            $force_login,
            ArchiveUploadedArchiveStub::withDestination('/final/destination'),
            $archive_for_project_dao,
        );

        $action->process();

        self::assertTrue($logger->hasInfoRecords());
        self::assertFalse(\Psl\Filesystem\is_file($this->upload . "/test.zip"));
        self::assertTrue($activator->isCalled());
        self::assertTrue($force_login->isForced());
        self::assertTrue($archive_for_project_dao->isSaved());
        self::assertEquals('/final/destination', $archive_for_project_dao->getSavedDestination());
    }

    public function testProcessFailure(): void
    {
        $logger = new TestLogger();

        $archive_for_project_dao = SaveUploadedArchiveForProjectStub::build();

        $activator = ActivateProjectAfterArchiveImportStub::build();
        $action    = ExtractArchiveAndCreateProject::fromEvent(
            new WorkerEvent(
                $logger,
                [
                    'event_name' => ExtractArchiveAndCreateProject::TOPIC,
                    'payload' => [
                        'project_id' => self::PROJECT_ID,
                        'filename'   => $this->upload . '/test.zip',
                        'user_id'    => self::USER_ID,
                    ],
                ]
            ),
            ImportFromArchiveStub::buildWithErrorDuringImport("Task failed successfully"),
            $activator,
            ProjectByIDFactoryStub::buildWith($this->project),
            RetrieveUserByIdStub::withUser($this->user),
            ForceLoginStub::build(),
            ArchiveUploadedArchiveStub::notExpectedToBeCalled(),
            $archive_for_project_dao,
        );

        $action->process();

        self::assertTrue($logger->hasError("Task failed successfully"));
        self::assertFalse(\Psl\Filesystem\is_file($this->upload . "/test.zip"));
        self::assertFalse($activator->isCalled());
        self::assertFalse($archive_for_project_dao->isSaved());
    }
}
