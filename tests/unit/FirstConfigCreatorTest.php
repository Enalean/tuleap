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

use TuleapTestCase;

require_once dirname(__FILE__) .'/bootstrap.php';

class FirstConfigCreatorTest extends TuleapTestCase
{
    /** @var Config */
    private $config;

    /** @var Project */
    private $template;

    /** @var Project */
    private $project;

    private $template_id                  = 101;
    private $campaign_tracker_id          = 333;
    private $definition_tracker_id        = 444;
    private $execution_tracker_id         = 555;

    private $project_id                   = 102;
    private $new_campaign_tracker_id      = 334;
    private $new_definition_tracker_id    = 445;
    private $new_execution_tracker_id     = 556;
    private $tracker_mapping;

    public function setUp()
    {
        parent::setUp();

        $this->template = stub('Project')->getId()->returns($this->template_id);
        $this->project = stub('Project')->getId()->returns($this->project_id);
        $this->tracker_mapping = array(
            $this->campaign_tracker_id => $this->new_campaign_tracker_id,
            $this->definition_tracker_id => $this->new_definition_tracker_id,
            $this->execution_tracker_id => $this->new_execution_tracker_id
        );

        $this->config = mock('Tuleap\\Trafficlights\\Config');
        stub($this->config)
            ->getCampaignTrackerId($this->template)
            ->returns($this->campaign_tracker_id);
        stub($this->config)
            ->getTestDefinitionTrackerId($this->template)
            ->returns($this->definition_tracker_id);
        stub($this->config)
            ->getTestExecutionTrackerId($this->template)
            ->returns($this->execution_tracker_id);

        $this->config_creator = new FirstConfigCreator($this->config);
    }

    public function itSetsTheProjectTTLTrackerIdsInConfig()
    {
        expect($this->config)->setProjectConfiguration(
            $this->project,
            $this->new_campaign_tracker_id,
            $this->new_definition_tracker_id,
            $this->new_execution_tracker_id
        )->once();

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }

    public function itDoesNotOverwriteAnExistingConfig()
    {
        stub($this->config)
            ->getCampaignTrackerId($this->project)
            ->returns(1);
        stub($this->config)
            ->getTestDefinitionTrackerId($this->project)
            ->returns(2);
        stub($this->config)
            ->getTestExecutionTrackerId($this->project)
            ->returns(3);

        expect($this->config)->setProjectConfiguration(
            $this->project,
            $this->new_campaign_tracker_id,
            $this->new_definition_tracker_id,
            $this->new_execution_tracker_id
        )->never();

        $this->config_creator->createConfigForProjectFromTemplate(
            $this->project,
            $this->template,
            $this->tracker_mapping
        );
    }
}


