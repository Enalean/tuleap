<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\ArtifactsDeletion;

use HTTPRequest;
use TrackerManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\RetrieveTracker;

final readonly class ArtifactsDeletionInTrackerAdminController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private RetrieveTracker $retrieve_tracker,
        private TrackerManager $tracker_manager,
        private \TemplateRenderer $template_renderer,
        private ConfigurationArtifactsDeletion $config,
        private RetrieveUserDeletionForLastDay $user_deletion_retriever,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker      = $this->retrieve_tracker->getTrackerById($variables['tracker_id']);
        $current_user = $request->getCurrentUser();
        if (! $tracker || ! $tracker->userIsAdmin($current_user)) {
            throw new NotFoundException(dgettext('tuleap-tracker', 'Tracker does not exist'));
        }

        $tracker->displayAdminItemHeaderBurningParrot(
            $this->tracker_manager,
            'adminartifactsdeletion',
            dgettext('tuleap-tracker', 'Artifacts deletion'),
        );

        $url_to_deletion_confirmation = ArtifactsConfirmDeletionInTrackerAdminUrlBuilder::fromTracker($tracker);

        $this->template_renderer->renderToPage(
            'admin-artifacts-deletion',
            new ArtifactsDeletionInTrackerAdminPresenter(
                $url_to_deletion_confirmation->getCSRFSynchronizerToken(),
                $url_to_deletion_confirmation->getUrl(),
                $this->config->getArtifactsDeletionLimit(),
                $this->user_deletion_retriever->getNumberOfArtifactsDeletionsForUserInTimePeriod($current_user),
            )
        );

        $tracker->displayFooter($this->tracker_manager);
    }
}
