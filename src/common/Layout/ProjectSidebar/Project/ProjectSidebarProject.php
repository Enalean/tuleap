<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Layout\ProjectSidebar\Project;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Project\Banner\BannerRetriever;
use Tuleap\Project\Flags\ProjectFlagPresenter;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\ProjectPrivacyPresenter;
use Tuleap\Project\Sidebar\CollectLinkedProjects;
use Tuleap\Project\Sidebar\LinkedProjectsCollectionPresenter;
use Tuleap\ServerHostname;

/**
 * @psalm-immutable
 */
final class ProjectSidebarProject
{
    /**
     * @param ProjectFlagPresenter[] $flags
     */
    private function __construct(
        public string $icon,
        public string $name,
        public string $href,
        public string $administration_href,
        public ProjectPrivacyPresenter $privacy,
        public bool $has_project_announcement,
        public array $flags,
        public ?LinkedProjectsCollectionPresenter $linked_projects,
    ) {
    }

    public static function build(
        \Project $project,
        \PFUser $user,
        BannerRetriever $banner_retriever,
        ProjectFlagsBuilder $project_flags_builder,
        EventDispatcherInterface $event_dispatcher,
    ): self {
        $base_url = ServerHostname::HTTPSUrl();

        return new self(
            EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($project->getIconUnicodeCodepoint()),
            $project->getPublicName(),
            $base_url . $project->getUrl(),
            $base_url . '/project/admin/?group_id=' . urlencode((string) $project->getID()),
            ProjectPrivacyPresenter::fromProject($project),
            $banner_retriever->getBannerForProject($project) !== null,
            $project_flags_builder->buildProjectFlags($project),
            LinkedProjectsCollectionPresenter::fromEvent(
                $event_dispatcher->dispatch(
                    new CollectLinkedProjects($project, $user)
                )
            )
        );
    }
}
