<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
use Tracker_ArtifactFactory;
use ArtifactDao;

require_once dirname(__FILE__) .'/bootstrap.php';

class ArtifactFactoryTest extends TuleapTestCase
{
    /** @var ArtifactFactory */
    private $factory;

    /** @var PFUser */
    private $user;

    /** @var Tracker */
    private $campaign_tracker;

    /** @var Tracker_Artifact */
    private $campaign;

    /** @var Tracker_Artifact */
    private $execution;

    private $project_id                   = 101;
    private $campaign_tracker_id          = 444;
    private $execution_tracker_id         = 555;
    private $campaign_id                  = 404;

    public function setUp()
    {
        parent::setUp();

        $this->user = aUser()->build();
        $project = stub('Project')->getId()->returns($this->project_id);
        $campaign_tracker = aTracker()
            ->withId($this->campaign_tracker_id)
            ->withProject($project)
            ->build();
        $execution_tracker = aTracker()
            ->withId($this->execution_tracker_id)
            ->withProject($project)
            ->build();

        # Prepare positive result
        $this->campaign = mock('Tracker_Artifact');
        $this->execution = mock('Tracker_Artifact');
        stub($this->campaign)
            ->getId()
            ->returns($this->campaign_id);
        stub($this->campaign)
            ->getTracker()
            ->returns($campaign_tracker);
        stub($this->campaign)
            ->getLinkedArtifacts($this->user)
            ->returns(array($this->execution));
        stub($this->execution)
            ->getTracker()
            ->returns($execution_tracker);
        stub($this->execution)
            ->getLinkedArtifacts($this->user)
            ->returns(array());

        # Prepare other resources
        $another_campaign_id = 403;
        $another_campaign = mock('Tracker_Artifact');
        $another_execution = mock('Tracker_Artifact');
        stub($another_campaign)
            ->getId()
            ->returns($another_campaign_id);
        stub($another_campaign)
            ->getTracker()
            ->returns($campaign_tracker);
        stub($another_campaign)
            ->getLinkedArtifacts($this->user)
            ->returns(array($another_execution));
        stub($another_execution)
            ->getTracker()
            ->returns($execution_tracker);
        stub($another_execution)
            ->getLinkedArtifacts($this->user)
            ->returns(array());

        $config = mock('Tuleap\\Trafficlights\\Config');
        stub($config)
            ->getCampaignTrackerId($project)
            ->returns($campaign_tracker->getId());
        stub($config)
            ->getTestExecutionTrackerId($project)
            ->returns($execution_tracker->getId());
        $conformance_validator = new ConfigConformanceValidator($config);
        $tracker_artifact_factory = mock('Tracker_ArtifactFactory');
        stub($tracker_artifact_factory)
            ->getArtifactsByTrackerId($this->campaign_tracker_id)
            ->returns(array($another_campaign, $this->campaign));
        Tracker_ArtifactFactory::setInstance($tracker_artifact_factory);
        $artifact_dao = mock('Tuleap\\Trafficlights\\ArtifactDao');

        $this->factory = new ArtifactFactory(
            $config,
            $conformance_validator,
            Tracker_ArtifactFactory::instance(),
            $artifact_dao
        );
    }

    public function tearDown()
    {
        Tracker_ArtifactFactory::clearInstance();

        parent::tearDown();
    }

    public function itReturnsACampaign()
    {
        $retrieved_campaign = $this->factory->getCampaignForExecution(
            $this->user,
            $this->execution
        );

        $this->assertEqual(
            $retrieved_campaign->getTracker()->getId(),
            $this->campaign_tracker_id
        );
    }

    public function itReturnsTheAssociatedCampaign()
    {
        $retrieved_campaign = $this->factory->getCampaignForExecution(
            $this->user,
            $this->execution
        );

        $this->assertEqual(
            $retrieved_campaign->getId(),
            $this->campaign_id
        );
    }
}

