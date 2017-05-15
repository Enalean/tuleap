<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Trafficlights;

use Project;
use Feedback;

class FirstConfigCreator
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config  = $config;
    }

    public function createConfigForProjectFromTemplate(
        Project $project,
        Project $template,
        array $tracker_mapping
    ) {
        if (! $this->isConfigNeeded($project)) {
            return;
        }

        $template_campaign_tracker_id        = $this->config->getCampaignTrackerId($template);
        $template_test_definition_tracker_id = $this->config->getTestDefinitionTrackerId($template);
        $template_test_execution_tracker_id  = $this->config->getTestExecutionTrackerId($template);

        if (! isset($tracker_mapping[$template_campaign_tracker_id]) ||
            ! isset($tracker_mapping[$template_test_definition_tracker_id]) ||
            ! isset($tracker_mapping[$template_test_execution_tracker_id])
        ) {
            return;
        }

        $this->config->setProjectConfiguration(
            $project,
            $tracker_mapping[$template_campaign_tracker_id],
            $tracker_mapping[$template_test_definition_tracker_id],
            $tracker_mapping[$template_test_execution_tracker_id]
        );
    }

    private function isConfigNeeded(Project $project)
    {
        return (! $this->config->getCampaignTrackerId($project)) ||
               (! $this->config->getTestDefinitionTrackerId($project)) ||
               (! $this->config->getTestExecutionTrackerId($project));
    }
}

