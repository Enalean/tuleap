<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once('bootstrap.php');

class Tracker_FormElement_Field_Burndown_RequestProcessingTest extends TuleapTestCase
{
    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->tracker_manager = \Mockery::spy(\TrackerManager::class);
        $this->current_user    = \Mockery::spy(\PFUser::class);

        $this->field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $tracker = mockery_stub(\Tracker::class)->isProjectAllowedToUseNature()->returns(false);
        $this->field->setTracker($tracker);
    }

    public function tearDown() {
        parent::tearDown();
        Tracker_ArtifactFactory::clearInstance();
    }

    public function itShouldRenderGraphWhenShowBurndownFuncIsCalled() {
        $artifact_id = 999;

        $request = new Codendi_Request(array('formElement' => 1234,
                                             'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                                             'src_aid'     => $artifact_id));

        $artifact        = Mockery::spy(\Tracker_Artifact::class);
        $artifactFactory = mockery_stub(\Tracker_ArtifactFactory::class)->getArtifactById($artifact_id)->returns($artifact);
        Tracker_ArtifactFactory::setInstance($artifactFactory);

        $this->field->shouldReceive('fetchBurndownImage')->with($artifact, $this->current_user)->once();

        $this->field->process($this->tracker_manager, $request, $this->current_user);
    }

    public function itMustNotBuildBurndownWhensrc_aidIsNotValid() {
        $request = new Codendi_Request(array('formElement' => 1234,
                                             'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                                             'src_aid'     => '; DROP DATABASE mouuahahahaha!'));

        $artifactFactory = mockery_stub(\Tracker_ArtifactFactory::class)->getArtifactById()->returns(null);
        Tracker_ArtifactFactory::setInstance($artifactFactory);

        $this->field->shouldReceive('fetchBurndownImage')->never();

        $this->field->process($this->tracker_manager, $request, $this->current_user);
    }

    public function itMustNotBuildBurndownWhenArtifactDoesNotExist() {
        $request = new Codendi_Request(array('formElement' => 1234,
                                             'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                                             'src_aid'     => 999));

        $artifactFactory = mockery_stub(\Tracker_ArtifactFactory::class)->getArtifactById()->returns(null);
        Tracker_ArtifactFactory::setInstance($artifactFactory);

        $this->field->shouldReceive('fetchBurndownImage')->never();

        $this->field->process($this->tracker_manager, $request, $this->current_user);
    }
}
