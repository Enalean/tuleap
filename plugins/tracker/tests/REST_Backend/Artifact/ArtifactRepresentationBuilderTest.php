<?php
/**
 * Copyright (c) Enalean, 2013 - 2016. All Rights Reserved.
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

use Tuleap\User\REST\MinimalUserRepresentation;

require_once __DIR__.'/../../bootstrap.php';

class Tracker_REST_Artifact_ArtifactRepresentationBuilder_BasicTest extends TuleapTestCase {

    /**
     * @var Tracker_REST_Artifact_ArtifactRepresentationBuilder
     */
    protected $builder;

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $project        = mockery_stub(Project::class)->getID()->returns(1478);
        $this->tracker  = aTracker()->withId(888)->withProject($project)->build();
        $this->user     = aUser()->withId(111)->build();
        $formelement_factory = \Mockery::spy(Tracker_FormElementFactory::class);
        stub($formelement_factory)->getUsedFieldsForREST($this->tracker)->returns(array());
        $this->builder  = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(
            $formelement_factory,
            \Mockery::spy(\Tracker_ArtifactFactory::class),
            \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao::class)
        );

        UserHelper::clearInstance();
        UserHelper::setInstance(\Mockery::spy(\UserHelper::class));

        $user_submitter  = \Mockery::spy(PFUser::class);
        $this->changeset = \Mockery::spy(Tracker_Artifact_Changeset::class);

        $this->artifact = \Mockery::spy(Tracker_Artifact::class);
        stub($this->artifact)->getId()->returns(12);
        stub($this->artifact)->getTracker()->returns($this->tracker);
        stub($this->artifact)->getSubmittedBy()->returns(777);
        stub($this->artifact)->getSubmittedOn()->returns(6546546554);
        stub($this->artifact)->getLastChangeset()->returns($this->changeset);
        stub($this->artifact)->getSubmittedByUser()->returns($user_submitter);
        stub($this->artifact)->getUri()->returns('/plugins/tracker/?aid=12');
        stub($this->artifact)->getXRef()->returns('Tracker_Artifact #12');
        stub($this->artifact)->getAssignedTo()->returns(array($this->user));
    }

    public function tearDown()
    {
        UserHelper::clearInstance();
        parent::tearDown();
    }

    public function itBuildsTheBasicInfo() {
        $representation              = $this->builder->getArtifactRepresentationWithFieldValues($this->user, $this->artifact);
        $minimal_user_representation = new MinimalUserRepresentation();
        $minimal_user_representation->build($this->user);

        $this->assertEqual($representation->id, 12);
        $this->assertEqual($representation->uri, Tuleap\Tracker\REST\Artifact\ArtifactRepresentation::ROUTE . '/' . 12);
        $this->assertEqual($representation->tracker->id, 888);
        $this->assertEqual($representation->tracker->uri, Tuleap\Tracker\REST\TrackerRepresentation::ROUTE . '/' . 888);
        $this->assertEqual($representation->project->id, 1478);
        $this->assertEqual($representation->project->uri, 'projects/1478');
        $this->assertEqual($representation->submitted_by, 777);
        $this->assertEqual($representation->submitted_on, '2177-06-14T06:09:14+01:00');
        $this->assertEqual($representation->html_url, '/plugins/tracker/?aid=12');
        $this->assertEqual($representation->changesets_uri, Tuleap\Tracker\REST\Artifact\ArtifactRepresentation::ROUTE . '/' . 12 . '/' . Tuleap\Tracker\REST\ChangesetRepresentation::ROUTE);
        $this->assertEqual($representation->xref, 'Tracker_Artifact #12');
        $this->assertEqual($representation->assignees[0], $minimal_user_representation);
    }
}

class Tracker_REST_Artifact_ArtifactRepresentationBuilder_FieldsTest extends TuleapTestCase {

    /**
     * @var Tracker_REST_Artifact_ArtifactRepresentationBuilder
     */
    protected $builder;

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $project        = mockery_stub(Project::class)->getID()->returns(1478);
        $this->tracker  = aTracker()->withId(888)->withProject($project)->build();
        $this->user     = aUser()->withId(111)->build();
        $this->changeset = \Mockery::spy(Tracker_Artifact_Changeset::class);
        $user_submitter  = \Mockery::spy(PFUser::class);

        UserHelper::clearInstance();
        UserHelper::setInstance(\Mockery::spy(\UserHelper::class));

        $this->artifact = \Mockery::spy(Tracker_Artifact::class);
        stub($this->artifact)->getTracker()->returns($this->tracker);
        stub($this->artifact)->getLastChangeset()->returns($this->changeset);
        stub($this->artifact)->getSubmittedByUser()->returns($user_submitter);
        stub($this->artifact)->getXRef()->returns('Tracker_Artifact #12');
        stub($this->artifact)->getAssignedTo()->returns(array($this->user));

        $this->formelement_factory = \Mockery::spy(Tracker_FormElementFactory::class);
        $this->builder = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(
            $this->formelement_factory,
            \Mockery::spy(\Tracker_ArtifactFactory::class),
            \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao::class)
        );
    }

    public function tearDown()
    {
        UserHelper::clearInstance();
        parent::tearDown();
    }

    public function itGetsTheFieldsFromTheFactory() {
        stub($this->formelement_factory)->getUsedFieldsForREST()->returns(array())->once();
        $this->builder->getArtifactRepresentationWithFieldValues($this->user, $this->artifact);
    }

    public function itHasNoValuesWhenThereAreNoFields() {
        stub($this->formelement_factory)->getUsedFieldsForREST()->returns(array());
        $representation = $this->builder->getArtifactRepresentationWithFieldValues($this->user, $this->artifact);

        $this->assertEqual($representation->values, array());
    }

    public function itDoesntIncludeFieldsTheUserCannotView() {
        $field1 = aMockField(true)->withId(1)->build();

        $field2 = aMockField(true)->withId(2)->build();
        $field3 = aMockField(true)->withId(3)->build();
        stub($field1)->userCanRead($this->user)->returns(false);
        stub($field2)->userCanRead($this->user)->returns(true);
        stub($field3)->userCanRead($this->user)->returns(false);

        expect($field1)->getRESTValue($this->user, $this->changeset)->never();
        expect($field2)->getRESTValue($this->user, $this->changeset)->once();
        expect($field3)->getRESTValue($this->user, $this->changeset)->never();

        stub($this->formelement_factory)->getUsedFieldsForREST($this->tracker)->returns(array($field1, $field2, $field3));

        $this->builder->getArtifactRepresentationWithFieldValues($this->user, $this->artifact);
    }

    public function itReturnsValuesOnlyForFieldsWithValues() {
        $field1 = aMockField(true)->withId(1)->build();
        $field2 = aMockField(true)->withId(2)->build();
        $field3 = aMockField(true)->withId(3)->build();
        stub($field2)->userCanRead($this->user)->returns(true);
        stub($field2)->getRESTValue()->returns('whatever');

        stub($this->formelement_factory)->getUsedFieldsForREST($this->tracker)->returns(array($field1, $field2, $field3));

        $representation = $this->builder->getArtifactRepresentationWithFieldValues($this->user, $this->artifact);

        $this->assertEqual($representation->values, array('whatever'));
        $this->assertEqual($representation->values_by_field, null);
    }

    public function itReturnsSimpleValuesOnlyForFieldsWithValues() {
        $field1 = mockery_stub(Tracker_FormElement_Field_Integer::class)->getId()->returns(1);
        $field2 = mockery_stub(Tracker_FormElement_Field_String::class)->getId()->returns(2);
        $field3 = mockery_stub(Tracker_FormElement_Field_Float::class)->getId()->returns(3);
        stub($field1)->userCanRead($this->user)->returns(true);
        stub($field2)->userCanRead($this->user)->returns(true);
        stub($field1)->getName()->returns('field01');
        stub($field2)->getName()->returns('field02');
        stub($field1)->getRESTValue()->returns('01');
        stub($field2)->getRESTValue()->returns('whatever');

        stub($this->formelement_factory)->getUsedFieldsForREST($this->tracker)->returns(array($field1, $field2, $field3));

        $representation = $this->builder->getArtifactRepresentationWithFieldValuesByFieldValues(
            $this->user,
            $this->artifact
        );

        $this->assertEqual($representation->values, null);
        $this->assertEqual($representation->values_by_field, array(
            'field01' => '01',
            'field02' => 'whatever',
        ));
    }

    public function itReturnsBothFormatForFieldsWithValues() {
        $field1 = mockery_stub(Tracker_FormElement_Field_Integer::class)->getId()->returns(1);
        $field2 = mockery_stub(Tracker_FormElement_Field_String::class)->getId()->returns(2);
        $field3 = mockery_stub(Tracker_FormElement_Field_Float::class)->getId()->returns(3);
        stub($field1)->userCanRead($this->user)->returns(true);
        stub($field2)->userCanRead($this->user)->returns(true);
        stub($field1)->getName()->returns('field01');
        stub($field2)->getName()->returns('field02');
        stub($field1)->getRESTValue()->returns('01');
        stub($field2)->getRESTValue()->returns('whatever');

        stub($this->formelement_factory)->getUsedFieldsForREST($this->tracker)->returns(array($field1, $field2, $field3));

        $representation = $this->builder->getArtifactRepresentationWithFieldValuesInBothFormat(
            $this->user,
            $this->artifact
        );

        $this->assertEqual($representation->values, array('01', 'whatever'));
        $this->assertEqual($representation->values_by_field, array(
            'field01' => '01',
            'field02' => 'whatever',
        ));
    }
}

class Tracker_REST_Artifact_ArtifactRepresentationBuilder_ChangesetsTest extends TuleapTestCase {
    /** @var Tracker_Artifact */
    private $artifact;

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->user     = aUser()->withId(111)->build();
        $this->artifact = anArtifact()
            ->withTracker(aMockeryTracker()->build())
            ->build();
        $this->builder = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(
            \Mockery::spy(\Tracker_FormElementFactory::class),
            \Mockery::spy(\Tracker_ArtifactFactory::class),
            \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao::class)
        );
    }

    public function itReturnsEmptyArrayWhenNoChanges() {
        $this->artifact->setChangesets(array());

        $this->assertIdentical(
            $this->builder->getArtifactChangesetsRepresentation($this->user, $this->artifact, Tracker_Artifact_Changeset::FIELDS_ALL, 0, 10, false)->toArray(),
            array()
        );
    }

    public function itBuildsHistoryOutOfChangeset() {
        $changeset1 = \Mockery::spy(Tracker_Artifact_Changeset::class);
        expect($changeset1)->getRESTValue($this->user, Tracker_Artifact_Changeset::FIELDS_ALL)->once();

        $this->artifact->setChangesets(array($changeset1));

        $this->builder->getArtifactChangesetsRepresentation($this->user, $this->artifact, Tracker_Artifact_Changeset::FIELDS_ALL, 0, 10, false)->toArray();
    }

    public function itDoesntExportEmptyChanges() {
        $changeset1 = \Mockery::spy(Tracker_Artifact_Changeset::class);
        $changeset2 = \Mockery::spy(Tracker_Artifact_Changeset::class);

        stub($changeset1)->getRESTValue()->returns(null);
        stub($changeset2)->getRESTValue()->returns('whatever');

        $this->artifact->setChangesets(array($changeset1, $changeset2));

        $this->assertIdentical(
            $this->builder->getArtifactChangesetsRepresentation($this->user, $this->artifact, Tracker_Artifact_Changeset::FIELDS_ALL, 0, 10, false)->toArray(),
            array('whatever')
        );
    }

    public function itPaginatesResults() {
        $changeset1 = \Mockery::spy(Tracker_Artifact_Changeset::class);
        $changeset2 = \Mockery::spy(Tracker_Artifact_Changeset::class);

        stub($changeset1)->getRESTValue()->returns('result 1');
        stub($changeset2)->getRESTValue()->returns('result 2');

        $this->artifact->setChangesets(array($changeset1, $changeset2));

        $this->assertIdentical(
            $this->builder->getArtifactChangesetsRepresentation($this->user, $this->artifact, Tracker_Artifact_Changeset::FIELDS_ALL, 1, 10, false)->toArray(),
            array('result 2')
        );
    }

    public function itReturnsTheTotalCountOfResults() {
        $changeset1 = \Mockery::spy(Tracker_Artifact_Changeset::class);
        $changeset2 = \Mockery::spy(Tracker_Artifact_Changeset::class);

        stub($changeset1)->getRESTValue()->returns('result 1');
        stub($changeset2)->getRESTValue()->returns('result 2');

        $this->artifact->setChangesets(array($changeset1, $changeset2));

        $this->assertIdentical(
            $this->builder->getArtifactChangesetsRepresentation($this->user, $this->artifact, Tracker_Artifact_Changeset::FIELDS_ALL, 1, 10, false)->totalCount(),
            2
        );
    }

    public function itReturnsTheChangesetsInReverseOrde() {
        $changeset1 = \Mockery::spy(Tracker_Artifact_Changeset::class);
        $changeset2 = \Mockery::spy(Tracker_Artifact_Changeset::class);

        stub($changeset1)->getRESTValue()->returns('result 1');
        stub($changeset2)->getRESTValue()->returns('result 2');

        $this->artifact->setChangesets(array($changeset1, $changeset2));

        $this->assertIdentical(
            $this->builder->getArtifactChangesetsRepresentation($this->user, $this->artifact, Tracker_Artifact_Changeset::FIELDS_ALL, 0, 10, true)->toArray(),
            array('result 2', 'result 1')
        );
    }
}
