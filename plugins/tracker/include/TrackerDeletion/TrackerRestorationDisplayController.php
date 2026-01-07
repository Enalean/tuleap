<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\TrackerDeletion;

use Override;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Tracker\Config\SiteAdminChecker;

final readonly class TrackerRestorationDisplayController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const string URL                             = '/site-admin/restore-tracker';
    public const string FULL_URL                        = \trackerPlugin::TRACKER_BASE_URL . self::URL;
    private const string DELETED_TRACKERS_TEMPLATE_NAME = 'deleted_trackers';

    public function __construct(private AdminPageRenderer $renderer, private DeleteTrackerPresenterBuilder $deleted_tracker_presenter_builder)
    {
    }

    #[Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $user = $request->getCurrentUser();
        SiteAdminChecker::checkUserIsSiteadmin($user, $layout);

        $presenter = $this->deleted_tracker_presenter_builder->displayDeletedTrackers();

        $this->renderer->renderANoFramedPresenter(
            dgettext('tuleap-tracker', 'Trackers'),
            $presenter->getTemplateDir(),
            self::DELETED_TRACKERS_TEMPLATE_NAME,
            $presenter
        );
    }
}
