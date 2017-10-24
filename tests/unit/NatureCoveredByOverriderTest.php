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

namespace Tuleap\TestManagement\Nature;

use TuleapTestCase;

require_once dirname(__FILE__) .'/bootstrap.php';

class NatureCoveredByOverriderTest extends TuleapTestCase
{
    /** @var TrackerArtifact */
    private $artifact;

    /** @var Config */
    private $config;

    /** @var Project */
    private $project;

    /** @var NatureCoveredByOverrider */
    private $overrider;

    private $artifact_id = 123;

    private $test_definition_tracker_id = 444;

    public function setUp()
    {
        parent::setUp();

        $this->artifact  = stub('Tracker_Artifact')->getId()->returns($this->artifact_id);
        $this->config    = mock('Tuleap\\TestManagement\\Config');
        $this->project   = aMockProject()->withId(101)->build();
        $this->dao       = mock('Tuleap\Tracker\Admin\ArtifactLinksUsageDao');

        $this->overrider = new NatureCoveredByOverrider($this->config, $this->dao);

        stub($this->config)->getTestDefinitionTrackerId($this->project)
            ->returns($this->test_definition_tracker_id);
    }

    public function itGivesTheCoveredByNatureToNewLinkToTestDefinition()
    {
        $new_linked_artifact_ids = array($this->artifact_id);

        stub($this->artifact)->getTrackerId()->returns($this->test_definition_tracker_id);
        stub($this->dao)->isTypeDisabledInProject(101, '_covered_by')->returns(false);

        $overridingNature = $this->overrider->getOverridingNature(
            $this->project,
            $this->artifact,
            $new_linked_artifact_ids
        );

        $this->assertEqual(
            $overridingNature,
            NatureCoveredByPresenter::NATURE_COVERED_BY
        );
    }

    public function itReturnsNothingWhenNotLinkingToTestDefinition()
    {
        $new_linked_artifact_ids = array($this->artifact_id);

        stub($this->dao)->isTypeDisabledInProject(101, '_covered_by')->returns(false);

        $overridingNature = $this->overrider->getOverridingNature(
            $this->project,
            $this->artifact,
            $new_linked_artifact_ids
        );

        $this->assertNull($overridingNature);
    }

    public function itReturnsNothingWhenUpdatingLinkToTestDefinition()
    {
        $new_linked_artifact_ids = array();

        stub($this->artifact)->getTrackerId()->returns($this->test_definition_tracker_id);
        stub($this->dao)->isTypeDisabledInProject(101, '_covered_by')->returns(false);

        $overridingNature = $this->overrider->getOverridingNature(
            $this->project,
            $this->artifact,
            $new_linked_artifact_ids
        );

        $this->assertNull($overridingNature);
    }

    public function itReturnsNothingIfCoveredByTypeIsDisabled()
    {
        $new_linked_artifact_ids = array($this->artifact_id);

        stub($this->dao)->isTypeDisabledInProject(101, '_covered_by')->returns(true);

        $overridingNature = $this->overrider->getOverridingNature(
            $this->project,
            $this->artifact,
            $new_linked_artifact_ids
        );

        $this->assertNull($overridingNature);
    }
}


