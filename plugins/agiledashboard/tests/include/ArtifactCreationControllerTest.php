<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../bootstrap.php';
require_once dirname(__FILE__).'/../../../tracker/include/constants.php';
require_once dirname(__FILE__).'/../../../../tests/simpletest/common/include/builders/aRequest.php';

class Planning_ArtifactCreationControllerTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR .'/../../..');

        $planning_id         = "99876387";
        $aid                 = -1;
        $this->planning_tracker_id = 66;
        $this->request = aRequest()->withUri("/plugins/agiledashboard/?group_id=104&action=show&planning_id=$planning_id&aid=$aid")->build();

        $this->planning = aPlanning()
            ->withId($planning_id)
            ->withPlanningTrackerId($this->planning_tracker_id)
            ->build();

        $planning_factory = mock('PlanningFactory');

        stub($planning_factory)->getPlanning($planning_id)->returns($this->planning);

        $this->controller = new Planning_ArtifactCreationController($planning_factory, $this->request);
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itRedirectsToArtifactCreationForm()
    {
        $new_artifact_url = preg_quote(TRACKER_BASE_URL."/?tracker=$this->planning_tracker_id&func=new-artifact&planning[{$this->planning->getId()}]=-1");

        $this->expectRedirectTo(new PatternExpectation("@$new_artifact_url@"));
        $this->controller->createArtifact();
    }
}
