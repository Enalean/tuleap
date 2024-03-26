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
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Project\ProjectByStatus;
use Tuleap\Project\ProjectRename;
use Tuleap\Project\UpdateProjectStatus;
use Tuleap\Upload\UploadPathAllocator;

final readonly class ProjectArchiveUploadCleaner
{
    public function __construct(
        private UploadPathAllocator $path_allocator,
        private SearchFileUploadIdsAndProjectIds $dao,
        private DeleteUnusedFiles $delete_dao,
        private EventDispatcherInterface $event_manager,
        private ProjectByStatus $project_manager_get_status,
        private UpdateProjectStatus $project_manager_update_status,
        private ProjectRename $project_manager_rename,
    ) {
    }

    /**
     * @throws \Tuleap\Project\DeletedProjectStatusChangeException
     * @throws \Tuleap\Project\Status\CannotDeletedDefaultAdminProjectException
     * @throws \Tuleap\Project\Status\SwitchingBackToPendingException
     */
    public function deleteUploadedDanglingProjectArchive(DateTimeImmutable $current_time): void
    {
        $this->delete_dao->deleteUnusableFile($current_time);
        $project_archive_uploaded_item_ids         = [];
        $project_archive_uploaded_item_project_ids = [];
        foreach ($this->dao->searchFileOngoingUploadIdsAndProjectIds() as $project_archive_upload_row) {
            $project_archive_uploaded_item_ids[]         = $project_archive_upload_row['id'];
            $project_archive_uploaded_item_project_ids[] = $project_archive_upload_row['project_id'];
        }

        $this->deleteProjectArchiveFile($project_archive_uploaded_item_ids);
        $this->deleteProjectCreatedFromArchive($project_archive_uploaded_item_project_ids);
    }

    /**
     * @param int[] $project_archive_uploaded_item_ids
     */
    private function deleteProjectArchiveFile(array $project_archive_uploaded_item_ids): void
    {
        $project_archive_uploaded_filesystem = $this->path_allocator->getCurrentlyUsedAllocatedPathsPerExpectedItemIDs();
        foreach ($project_archive_uploaded_filesystem as $expected_item_id => $path) {
            if (! in_array((int) $expected_item_id, $project_archive_uploaded_item_ids)) {
                unlink($path);
            }
        }
    }

    /**
     * @param int[] $project_archive_uploaded_item_project_ids
     *
     * @throws \Tuleap\Project\DeletedProjectStatusChangeException
     * @throws \Tuleap\Project\Status\CannotDeletedDefaultAdminProjectException
     * @throws \Tuleap\Project\Status\SwitchingBackToPendingException
     */
    private function deleteProjectCreatedFromArchive(array $project_archive_uploaded_item_project_ids): void
    {
        $from_archive_status_projects = $this->project_manager_get_status->getProjectsByStatus(Project::STATUS_CREATING_FROM_ARCHIVE);
        foreach ($from_archive_status_projects as $project) {
            if (! in_array((int) $project->getID(), $project_archive_uploaded_item_project_ids)) {
                $this->project_manager_update_status->updateStatus($project, Project::STATUS_DELETED);
                $this->project_manager_rename->renameProject($project, sprintf('import_failure_%d', (int) $project->getID()));
                $this->event_manager->dispatch(new ProjectStatusUpdate($project, Project::STATUS_DELETED));
            }
        }
    }
}
