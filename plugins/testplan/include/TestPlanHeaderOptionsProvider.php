<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestPlan;

use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\Layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\Layout\NewDropdown\DataAttributePresenter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Option\Option;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

class TestPlanHeaderOptionsProvider
{
    private const IDENTIFIER = 'testplan';

    public function __construct(
        private readonly HeaderOptionsProvider $header_options_provider,
        private readonly Config $testmanagement_config,
        private readonly \TrackerFactory $tracker_factory,
        private readonly TrackerNewDropdownLinkPresenterBuilder $presenter_builder,
        private readonly CurrentContextSectionToHeaderOptionsInserter $header_options_inserter,
    ) {
    }

    /**
     * @return Option<NewDropdownLinkSectionPresenter>
     */
    public function getCurrentContextSection(
        \PFUser $user,
        \Planning_Milestone $milestone,
    ): Option {
        $current_context_section = $this->header_options_provider->getCurrentContextSection($user, $milestone, self::IDENTIFIER);

        $campaign_tracker_id = $this->testmanagement_config->getCampaignTrackerId($milestone->getProject());
        if (! $campaign_tracker_id) {
            return $current_context_section;
        }
        $campaign_tracker = $this->tracker_factory->getTrackerById($campaign_tracker_id);
        if (! $campaign_tracker) {
            return $current_context_section;
        }

        if (! $campaign_tracker->userCanSubmitArtifact($user)) {
            return $current_context_section;
        }

        return $this->header_options_inserter->addLinkToCurrentContextSection(
            (string) $milestone->getArtifactTitle(),
            $this->presenter_builder->buildWithAdditionalDataAttributes(
                $campaign_tracker,
                [new DataAttributePresenter('test-plan-create-new-campaign', '1')]
            ),
            $current_context_section,
        );
    }
}
