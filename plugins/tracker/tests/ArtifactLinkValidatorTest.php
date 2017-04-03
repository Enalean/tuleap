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

namespace Tuleap\Tracker\FormElement;

require_once('bootstrap.php');

class ArtifactLinkValidatorTest extends \TuleapTestCase
{
    /**
     * @var Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory
     */
    private $nature_presenter_factory;

    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var ArtifactLinkValidator
     */
    private $artifact_link_validator;

    /**
     * @var \Tracker_Artifact
     */
    private $artifact;

    /**
     * @var \Tracker_FormElement_Field_ArtifactLink
     */
    private $field;

    /**
     * @var \Tracker_Artifact
     */
    private $linked_artifact;

    /**
     * @var \Tracker
     */
    private $tracker;

    /**
     * @var Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildPresenter
     */
    private $nature_is_child;

    /**
     * @var Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter
     */
    private $nature_fixed_in;

    /**
     * @var Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter
     */
    private $nature_no_nature;

    public function setUp()
    {
        parent::setUp();

        $this->artifact_factory         = mock('\Tracker_ArtifactFactory');
        $this->nature_presenter_factory = mock(
            'Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory'
        );

        $this->tracker         = mock('Tracker');
        $this->artifact        = anArtifact()->withId(101)->withTracker($this->tracker)->build();
        $this->linked_artifact = anArtifact()->withId(105)->withTracker($this->tracker)->build();
        $this->field           = mock('\Tracker_FormElement_Field_ArtifactLink');

        $this->artifact_link_validator = new ArtifactLinkValidator(
            $this->artifact_factory,
            $this->nature_presenter_factory
        );

        $this->nature_is_child  = mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildPresenter');
        $this->nature_fixed_in  = mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter');
        $this->nature_no_nature = mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter');
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function itReturnsTrueWhenNoNewValuesAreSent()
    {
        $this->assertTrue($this->artifact_link_validator->isValid(array(), $this->artifact, $this->field));
        $this->assertTrue($this->artifact_link_validator->isValid(null, $this->artifact, $this->field));
    }

    public function itReturnsFalseWhenArtifactIdIsIncorrect()
    {
        stub($this->tracker)->isProjectAllowedToUseNature()->returns(false);

        $this->assertFalse(
            $this->artifact_link_validator->isValid(
                array('new_values' => '666'),
                $this->artifact,
                $this->field
            )
        );

        $this->assertFalse(
            $this->artifact_link_validator->isValid(
                array('new_values' => '123, 666'),
                $this->artifact,
                $this->field
            )
        );
        $this->assertFalse(
            $this->artifact_link_validator->isValid(
                array('new_values' => '123,666'),
                $this->artifact,
                $this->field
            )
        );
        $this->assertFalse(
            $this->artifact_link_validator->isValid(
                array('new_values' => ',,,,'),
                $this->artifact,
                $this->field
            )
        );
    }

    public function itReturnsFalseWhenArtifactIdDoesNotExist()
    {
        $value = array('new_values' => '1000');
        stub($this->artifact_factory)->getArtifactById()->returns(null);
        stub($this->tracker)->isProjectAllowedToUseNature()->returns(false);

        $this->assertFalse($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }

    public function itReturnsFalseWhenTrackerIsDeleted()
    {
        $value = array('new_values' => '1000');
        stub($this->artifact_factory)->getArtifactById()->returns($this->linked_artifact);
        stub($this->tracker)->isDeleted()->returns(true);
        stub($this->tracker)->isProjectAllowedToUseNature()->returns(false);

        $this->assertFalse($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }

    public function itReturnsTrueWhenProjectCanNotUseNature()
    {
        $value = array('new_values' => '1000');
        stub($this->artifact_factory)->getArtifactById()->returns($this->linked_artifact);
        stub($this->tracker)->isProjectAllowedToUseNature()->returns(false);

        $this->assertTrue($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }

    public function itReturnsFalseWhenProjectCanUseNatureAndNatureDoesNotExist()
    {
        $value = array('new_values' => '1000', 'natures' => array('_is_child', 'fixed_in'));
        stub($this->artifact_factory)->getArtifactById()->returns($this->linked_artifact);
        stub($this->tracker)->isProjectAllowedToUseNature()->returns(true);
        stub($this->nature_presenter_factory)->getFromShortname()->returns(null);

        $this->assertFalse($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }

    public function itReturnsTrueWhenProjectCanUseNatureAndNatureExist()
    {
        stub($this->artifact_factory)->getArtifactById()->returns($this->linked_artifact);
        stub($this->tracker)->isProjectAllowedToUseNature()->returns(true);
        stub($this->nature_presenter_factory)->getFromShortname('_is_child')->returns($this->nature_is_child);
        stub($this->nature_presenter_factory)->getFromShortname('fixed_in')->returns($this->nature_fixed_in);

        $value = array('new_values' => '1000', 'natures' => array('_is_child', 'fixed_in'));
        $this->assertTrue($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));

        $value = array('new_values' => '123          ,   321, 999', 'natures' => array('_is_child', 'fixed_in'));
        $this->assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field
            )
        );

        $value = array('new_values' => '', 'natures' => array('_is_child', 'fixed_in'));
        $this->assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field
            )
        ); // existing values

        $value = array('new_values' => '123', 'natures' => array('_is_child', 'fixed_in'));
        $this->assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field
            )
        );
    }

    public function itReturnsTrueWhenProjectCanUseNatureAndNatureEmpty()
    {
        $value = array('new_values' => '1000', 'natures' => array(''));
        stub($this->artifact_factory)->getArtifactById()->returns($this->linked_artifact);
        stub($this->tracker)->isProjectAllowedToUseNature()->returns(true);
        stub($this->nature_presenter_factory)->getFromShortname('')->returns($this->nature_no_nature);

        $this->assertTrue($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }
}
