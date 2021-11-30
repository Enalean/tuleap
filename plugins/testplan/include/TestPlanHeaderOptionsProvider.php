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
use Tuleap\layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\layout\NewDropdown\DataAttributePresenter;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

class TestPlanHeaderOptionsProvider
{
    /**
     * @var HeaderOptionsProvider
     */
    private $header_options_provider;
    /**
     * @var Config
     */
    private $testmanagement_config;
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var TrackerNewDropdownLinkPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var CurrentContextSectionToHeaderOptionsInserter
     */
    private $header_options_inserter;

    public function __construct(
        HeaderOptionsProvider $header_options_provider,
        Config $testmanagement_config,
        \TrackerFactory $tracker_factory,
        TrackerNewDropdownLinkPresenterBuilder $presenter_builder,
        CurrentContextSectionToHeaderOptionsInserter $header_options_inserter,
    ) {
        $this->header_options_provider = $header_options_provider;
        $this->testmanagement_config   = $testmanagement_config;
        $this->tracker_factory         = $tracker_factory;
        $this->presenter_builder       = $presenter_builder;
        $this->header_options_inserter = $header_options_inserter;
    }

    public function getHeaderOptions(\PFUser $user, \Planning_Milestone $milestone): array
    {
        $header_options = $this->header_options_provider->getHeaderOptions($user, $milestone, 'testplan');
        if (! isset($header_options['main_classes'])) {
            $header_options['main_classes'] = [];
        }
        if (! in_array('fluid-main', $header_options['main_classes'], true)) {
            $header_options['main_classes'][] = 'fluid-main';
        }

        $this->addCampaignInCurrentContextSection($user, $milestone, $header_options);

        return $header_options;
    }

    private function addCampaignInCurrentContextSection(
        \PFUser $user,
        \Planning_Milestone $milestone,
        array &$header_options,
    ): void {
        $campaign_tracker_id = $this->testmanagement_config->getCampaignTrackerId($milestone->getProject());
        if (! $campaign_tracker_id) {
            return;
        }
        $campaign_tracker = $this->tracker_factory->getTrackerById($campaign_tracker_id);
        if (! $campaign_tracker) {
            return;
        }

        if (! $campaign_tracker->userCanSubmitArtifact($user)) {
            return;
        }

        $this->header_options_inserter->addLinkToCurrentContextSection(
            (string) $milestone->getArtifactTitle(),
            $this->presenter_builder->buildWithAdditionalDataAttributes(
                $campaign_tracker,
                [new DataAttributePresenter('test-plan-create-new-campaign', '1')]
            ),
            $header_options
        );
    }
}
