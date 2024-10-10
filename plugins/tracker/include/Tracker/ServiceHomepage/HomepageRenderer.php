<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\ServiceHomepage;

use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\Creation\OngoingCreationFeedbackNotifier;

final readonly class HomepageRenderer
{
    public function __construct(
        private HomepagePresenterBuilder $presenter_builder,
        private GlobalAdminPermissionsChecker $permissions_checker,
        private OngoingCreationFeedbackNotifier $creation_notifier,
        private \TemplateRendererFactory $renderer_factory,
    ) {
    }

    public function renderToString(\Project $project, \PFUser $user): string
    {
        $is_tracker_admin = $this->permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user);
        if ($is_tracker_admin) {
            $this->creation_notifier->informUserOfOngoingMigrations($project, $GLOBALS['Response']);
        }

        $presenter = $this->presenter_builder->build($project, $user, $is_tracker_admin);
        $renderer  = $this->renderer_factory->getRenderer(__DIR__ . '/../../../templates/service-homepage');
        return $renderer->renderToString('service-homepage', $presenter);
    }
}
