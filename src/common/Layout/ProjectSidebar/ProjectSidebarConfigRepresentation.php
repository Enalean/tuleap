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

namespace Tuleap\Layout\ProjectSidebar;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\BuildVersion\FlavorFinder;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Layout\Logo\IDetectIfLogoIsCustomized;
use Tuleap\Layout\ProjectSidebar\InstanceInformation\ProjectSidebarInstanceInformation;
use Tuleap\Layout\ProjectSidebar\Internationalization\ProjectSidebarInternationalization;
use Tuleap\Layout\ProjectSidebar\Project\ProjectSidebarProject;
use Tuleap\Layout\ProjectSidebar\User\ProjectSidebarUser;
use Tuleap\Layout\ProjectSidebarToolsBuilder;
use Tuleap\Layout\SidebarServicePresenter;
use Tuleap\Project\Admin\Access\VerifyUserCanAccessProjectAdministration;
use Tuleap\Project\Banner\BannerRetriever;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\User\CurrentUserWithLoggedInInformation;

/**
 * @psalm-immutable
 */
final class ProjectSidebarConfigRepresentation
{
    /**
     * @param SidebarServicePresenter[] $tools
     */
    private function __construct(
        public ProjectSidebarInternationalization $internationalization,
        public ProjectSidebarProject $project,
        public ProjectSidebarUser $user,
        public ProjectSidebarInstanceInformation $instance_information,
        public array $tools,
    ) {
    }

    public static function build(
        \Project $project,
        CurrentUserWithLoggedInInformation $current_user,
        BannerRetriever $banner_retriever,
        ProjectFlagsBuilder $project_flags_builder,
        EventDispatcherInterface $event_dispatcher,
        VerifyUserCanAccessProjectAdministration $project_admin_access_verifier,
        FlavorFinder $flavor_finder,
        IDetectIfLogoIsCustomized $customized_logo_detector,
        GlyphFinder $glyph_finder,
        ProjectSidebarToolsBuilder $project_sidebar_tools_builder,
        mixed $currently_active_service,
        ?string $active_promoted_item_id,
    ): self {
        return new self(
            ProjectSidebarInternationalization::build(),
            ProjectSidebarProject::build(
                $project,
                $current_user->user,
                $banner_retriever,
                $project_flags_builder,
                $event_dispatcher,
            ),
            ProjectSidebarUser::fromProjectAndUser(
                $project,
                $current_user,
                $project_admin_access_verifier,
            ),
            ProjectSidebarInstanceInformation::build(
                $current_user->user->getLanguage(),
                $flavor_finder,
                $customized_logo_detector,
                $glyph_finder,
            ),
            [...$project_sidebar_tools_builder->getSidebarTools($current_user->user, $currently_active_service, $active_promoted_item_id, $project)],
        );
    }
}
