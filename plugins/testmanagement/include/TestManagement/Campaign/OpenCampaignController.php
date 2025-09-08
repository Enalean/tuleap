<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Campaign;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

class OpenCampaignController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var CampaignRetriever
     */
    private $campaign_retriever;
    /**
     * @var StatusUpdater
     */
    private $status_updater;

    public function __construct(CampaignRetriever $campaign_retriever, StatusUpdater $status_updater)
    {
        $this->campaign_retriever = $campaign_retriever;
        $this->status_updater     = $status_updater;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        try {
            $campaign_id = (int) $variables['campaign_id'];
            $campaign    = $this->campaign_retriever->getById($campaign_id);
        } catch (ArtifactNotFoundException $exception) {
            throw new NotFoundException($exception->getMessage());
        }

        $user    = $request->getCurrentUser();
        $project = $campaign->getArtifact()->getTracker()->getProject();

        $csrf_token = new CSRFSynchronizerToken(
            '/plugins/testmanagement/?group_id=' . (int) $project->getID()
        );

        try {
            $this->status_updater->openCampaign(
                $campaign,
                $user,
                $csrf_token
            );

            $layout->addFeedback(
                Feedback::INFO,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The campaign %s is now open.'),
                    $campaign->getLabel()
                )
            );
        } catch (NoPossibleValueException $exception) {
            $layout->addFeedback(
                Feedback::ERROR,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The campaign cannot be open : %s'),
                    $exception->getMessage()
                )
            );
        }

        $layout->redirect(
            StatusChangedRedirectURLBuilder::buildRedirectURL(
                $request,
                $project,
                $campaign_id
            )
        );
    }
}
