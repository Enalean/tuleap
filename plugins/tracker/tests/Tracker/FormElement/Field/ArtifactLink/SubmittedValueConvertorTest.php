<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use TuleapTestCase;
use Tracker_ArtifactLinkInfo;
use Tracker_ArtifactFactory;

require_once __DIR__.'/../../../../bootstrap.php';

class SubmittedValueConvertorTest extends TuleapTestCase
{

    /** @var SubmittedValueConvertor */
    private $convertor;

    /** @var SourceOfAssociationDetector */
    private $source_of_association_detector;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_Artifact_ChangesetValue_ArtifactLink */
    private $previous_changesetvalue;

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var Tracker_Artifact */
    private $art_123;

    /** @var Tracker_Artifact */
    private $art_124;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $tracker = aTracker()->withId(101)->build();

        $changesets_123 = array(mockery_stub(\Tracker_Artifact_Changeset::class)->getId()->returns(1231));
        $changesets_124 = array(mockery_stub(\Tracker_Artifact_Changeset::class)->getId()->returns(1241));
        $changesets_201 = array(mockery_stub(\Tracker_Artifact_Changeset::class)->getId()->returns(2011));

        $this->artifact = anArtifact()->withId(120)->build();
        $this->art_123  = anArtifact()->withId(123)->withTracker($tracker)->withChangesets($changesets_123)->build();
        $this->art_124  = anArtifact()->withId(124)->withTracker($tracker)->withChangesets($changesets_124)->build();
        $this->art_201  = anArtifact()->withId(201)->withTracker($tracker)->withChangesets($changesets_201)->build();

        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $this->source_of_association_detector = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationDetector::class);

        $this->previous_changesetvalue = \Mockery::spy(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        stub($this->previous_changesetvalue)->getValue()->returns(array(
            201 => Tracker_ArtifactLinkInfo::buildFromArtifact($this->art_201, '_is_child')
        ));

        stub($this->artifact_factory)->getArtifactById(123)->returns($this->art_123);
        stub($this->artifact_factory)->getArtifactById(124)->returns($this->art_124);
        stub($this->artifact_factory)->getArtifactById(201)->returns($this->art_201);

        $this->source_of_association_collection = new SourceOfAssociationCollection();
        $this->convertor = new SubmittedValueConvertor(
            $this->artifact_factory,
            $this->source_of_association_detector
        );

        Tracker_ArtifactFactory::setInstance($this->artifact_factory);
    }

    public function tearDown()
    {
        Tracker_ArtifactFactory::clearInstance();
        parent::tearDown();
    }

    public function itRemovesFromSubmittedValuesArtifactsThatWereUpdatedByDirectionChecking()
    {
        $submitted_value = array('new_values' => '123, 124');

        stub($this->source_of_association_detector)->isChild($this->art_123, $this->artifact)->returns(false);
        stub($this->source_of_association_detector)->isChild($this->art_124, $this->artifact)->returns(true);

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->source_of_association_collection,
            $this->artifact,
            $this->previous_changesetvalue
        );

        $this->assertEqual(
            $updated_submitted_value['list_of_artifactlinkinfo'],
            array(
                201 => Tracker_ArtifactLinkInfo::buildFromArtifact($this->art_201, '_is_child'),
                123 => Tracker_ArtifactLinkInfo::buildFromArtifact($this->art_123, '')
            )
        );
    }

    public function itChangesTheNatureOfAnExistingLink()
    {
        $submitted_value = array(
            'new_values' => '',
            'natures' => array(
                '201' => 'fixed_in'
            )
        );

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->source_of_association_collection,
            $this->artifact,
            $this->previous_changesetvalue
        );

        $this->assertEqual($updated_submitted_value['list_of_artifactlinkinfo'][201]->getNature(), 'fixed_in');
    }

    public function itChangesTheNatureToNullOfAnExistingLink()
    {
        $submitted_value = array(
            'new_values' => '',
            'natures' => array(
                '201' => ''
            )
        );

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->source_of_association_collection,
            $this->artifact,
            $this->previous_changesetvalue
        );

        $this->assertEqual($updated_submitted_value['list_of_artifactlinkinfo'][201]->getNature(), null);
    }

    public function itDoesNotMutateTheExistingArtifactLinkInfo()
    {
        $submitted_value = array(
            'new_values' => '',
            'natures' => array(
                '201' => '_is_child'
            )
        );

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->source_of_association_collection,
            $this->artifact,
            $this->previous_changesetvalue
        );

        $existing_list_of_artifactlinkinfo = $this->previous_changesetvalue->getValue();

        $this->assertEqual(
            $updated_submitted_value['list_of_artifactlinkinfo'][201],
            $existing_list_of_artifactlinkinfo[201]
        );
    }

    public function itConvertsWhenThereIsNoNature()
    {
        $submitted_value = array('new_values' => '123, 124');

        stub($this->source_of_association_detector)->isChild($this->art_123, $this->artifact)->returns(false);
        stub($this->source_of_association_detector)->isChild($this->art_124, $this->artifact)->returns(false);

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->source_of_association_collection,
            $this->artifact,
            $this->previous_changesetvalue
        );
        $this->assertEqual($updated_submitted_value['list_of_artifactlinkinfo']['123']->getNature(), null);
        $this->assertEqual($updated_submitted_value['list_of_artifactlinkinfo']['124']->getNature(), null);
    }

    public function itConvertsWhenThereIsOnlyOneNature()
    {
        $submitted_value = array('new_values' => '123, 124', 'natures' => array('123' => '_is_child', '124' => '_is_child'));

        stub($this->source_of_association_detector)->isChild($this->art_123, $this->artifact)->returns(false);
        stub($this->source_of_association_detector)->isChild($this->art_124, $this->artifact)->returns(false);

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->source_of_association_collection,
            $this->artifact,
            $this->previous_changesetvalue
        );
        $this->assertEqual($updated_submitted_value['list_of_artifactlinkinfo']['123']->getNature(), '_is_child');
        $this->assertEqual($updated_submitted_value['list_of_artifactlinkinfo']['124']->getNature(), '_is_child');
    }

    public function itConvertsWhenEachArtifactLinkHasItsOwnNature()
    {
        $submitted_value = array('new_values' => '123, 124', 'natures' => array('123' => '_is_child', '124' => '_is_foo'));

        stub($this->source_of_association_detector)->isChild($this->art_123, $this->artifact)->returns(false);
        stub($this->source_of_association_detector)->isChild($this->art_124, $this->artifact)->returns(false);

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->source_of_association_collection,
            $this->artifact,
            $this->previous_changesetvalue
        );
        $this->assertEqual($updated_submitted_value['list_of_artifactlinkinfo']['123']->getNature(), '_is_child');
        $this->assertEqual($updated_submitted_value['list_of_artifactlinkinfo']['124']->getNature(), '_is_foo');
    }
}
