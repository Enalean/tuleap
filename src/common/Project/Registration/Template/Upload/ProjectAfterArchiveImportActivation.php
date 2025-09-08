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

use Project;
use Tuleap\Project\ActivateProject;
use Tuleap\Project\NotifySiteAdmin;

final readonly class ProjectAfterArchiveImportActivation implements ActivateProjectAfterArchiveImport
{
    public function __construct(
        private \ProjectDao $project_dao,
        private NotifySiteAdmin $site_admin_notifier,
        private NotifyProjectImportStatus $notify_project_import_status,
        private ActivateProject $project_manager,
    ) {
    }

    #[\Override]
    public function activateProject(Project $project, \PFUser $project_admin): void
    {
        if ($this->shouldProjectBeApprovedByAdmin()) {
            $presenter = [
                'project_name' => $project->getPublicName(),
                'instance_name' => \ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME),
            ];

            $message = ProjectImportMessage::build(
                sprintf(
                    _('Project "%s" imported'),
                    $project->getPublicName(),
                ),
                'notification-project-created-but-pending',
                'notification-project-created-but-pending-text',
                $presenter
            );
            $this->project_dao->updateStatus($project->getID(), Project::STATUS_PENDING);
            $this->site_admin_notifier->notifySiteAdmin($project);
            $this->notify_project_import_status->notify($project, $project_admin, $message);
            return;
        }

        $this->project_manager->activateWithNotifications($project);
        $this->site_admin_notifier->notifySiteAdmin($project);
    }

    private function shouldProjectBeApprovedByAdmin(): bool
    {
        return (bool) \ForgeConfig::get(\ProjectManager::CONFIG_PROJECT_APPROVAL, true);
    }
}
