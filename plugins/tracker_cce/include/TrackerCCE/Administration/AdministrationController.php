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

namespace Tuleap\TrackerCCE\Administration;

use HTTPRequest;
use Project;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Artifact\RetrieveTracker;

final class AdministrationController implements DispatchableWithRequest, DispatchableWithBurningParrot, DispatchableWithProject
{
    public function __construct(
        private readonly RetrieveTracker $retrieve_tracker,
        private readonly \Tracker_IDisplayTrackerLayout $tracker_layout,
        private readonly \TemplateRendererFactory $renderer_factory,
        private readonly TrackerCSRFTokenProvider $token_provider,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker = $this->getTracker((int) $variables['id']);

        $current_user = $request->getCurrentUser();
        if (! $tracker->userIsAdmin($current_user)) {
            throw new NotFoundException();
        }

        $tracker->displayAdminItemHeaderBurningParrot(
            $this->tracker_layout,
            'editworkflow',
            dgettext('tuleap-tracker_cce', 'Custom code execution'),
        );

        $renderer = $this->renderer_factory->getRenderer(__DIR__);
        $renderer->renderToPage(
            'administration',
            new AdministrationPresenter(
                UpdateModuleController::getUrl($tracker),
                CSRFSynchronizerTokenPresenter::fromToken($this->token_provider->getToken($tracker)),
            )
        );

        $tracker->displayFooter($this->tracker_layout);
    }

    public static function getUrl(\Tracker $tracker): string
    {
        return '/tracker_cce/' . urlencode((string) $tracker->getId()) . '/admin';
    }

    public function getProject(array $variables): Project
    {
        return $this
            ->getTracker((int) $variables['id'])
            ->getProject();
    }

    private function getTracker(int $id): \Tracker
    {
        $tracker = $this->retrieve_tracker->getTrackerById($id);
        if (! $tracker) {
            throw new NotFoundException();
        }

        if ($tracker->isDeleted()) {
            throw new NotFoundException();
        }

        return $tracker;
    }
}
