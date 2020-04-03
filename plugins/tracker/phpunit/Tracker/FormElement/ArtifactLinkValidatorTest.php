<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

use BaseLanguage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;

require_once __DIR__ . '/../../bootstrap.php';

class ArtifactLinkValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $backup_globals;

    /**
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory
     */
    private $nature_presenter_factory;

    /**
     * @var Tracker_ArtifactFactory
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
     * @var Tracker_FormElement_Field_ArtifactLink
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
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildPresenter
     */
    private $nature_is_child;

    /**
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter
     */
    private $nature_fixed_in;

    /**
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter
     */
    private $nature_no_nature;
    private $project;
    private $dao;

    public function setUp(): void
    {
        parent::setUp();

        $this->backup_globals = array_merge([], $GLOBALS);
        $GLOBALS['Response']  = Mockery::spy(\Response::class);
        $GLOBALS['Language']  = Mockery::spy(BaseLanguage::class);

        $this->artifact_factory         = \Mockery::spy(Tracker_ArtifactFactory::class);
        $this->nature_presenter_factory = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory::class);

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn(101);

        $this->tracker = \Mockery::spy(\Tracker::class);

        $this->artifact = Mockery::mock(\Tracker_Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->linked_artifact = Mockery::mock(\Tracker_Artifact::class);
        $this->linked_artifact->shouldReceive('getId')->andReturn(105);
        $this->linked_artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->field   = \Mockery::spy(Tracker_FormElement_Field_ArtifactLink::class);
        $this->dao     = \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);

        $this->tracker->shouldReceive('getProject')->andReturn($this->project);

        $this->artifact_link_validator = new ArtifactLinkValidator(
            $this->artifact_factory,
            $this->nature_presenter_factory,
            $this->dao
        );

        $this->nature_is_child  = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildPresenter::class);
        $this->nature_fixed_in  = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter::class);
        $this->nature_no_nature = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter::class);
    }

    protected function tearDown(): void
    {
        $GLOBALS = $this->backup_globals;

        parent::tearDown();
    }

    public function testItReturnsTrueWhenNoNewValuesAreSent()
    {
        $this->assertTrue($this->artifact_link_validator->isValid(array(), $this->artifact, $this->field));
        $this->assertTrue($this->artifact_link_validator->isValid(null, $this->artifact, $this->field));
    }

    public function testItReturnsFalseWhenArtifactIdIsIncorrect()
    {
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);

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

    public function testItReturnsFalseWhenArtifactIdDoesNotExist()
    {
        $value = array('new_values' => '1000');
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn(null);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);

        $this->assertFalse($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }

    public function testItReturnsFalseWhenTrackerIsDeleted()
    {
        $value = array('new_values' => '1000');
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isDeleted')->andReturn(true);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);

        $this->assertFalse($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }

    public function testItReturnsFalseWhenProjectIsNotActive()
    {
        $value = array('new_values' => '1000');
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);
        $this->project->shouldReceive('isActive')->andReturn(false);

        $this->assertFalse($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }

    public function testItReturnsTrueWhenProjectCanNotUseNature()
    {
        $value = array('new_values' => '1000');
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);
        $this->project->shouldReceive('isActive')->andReturn(true);

        $this->assertTrue($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }

    public function testItReturnsFalseWhenProjectCanUseNatureAndNatureDoesNotExist()
    {
        $value = array('new_values' => '1000', 'natures' => array('_is_child', 'fixed_in'));
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->andReturn(null);
        $this->project->shouldReceive('isActive')->andReturn(true);

        $this->assertFalse($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }

    public function testItReturnsTrueWhenProjectCanUseNatureAndNatureExist()
    {
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('_is_child')->andReturn($this->nature_is_child);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in')->andReturn($this->nature_fixed_in);
        $this->project->shouldReceive('isActive')->andReturn(true);

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

    public function testItReturnsTrueWhenProjectCanUseNatureAndNatureEmpty()
    {
        $value = array('new_values' => '1000', 'natures' => array(''));
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('')->andReturn($this->nature_no_nature);
        $this->project->shouldReceive('isActive')->andReturn(true);

        $this->assertTrue($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }

    public function testItReturnsFalseWhenProjectCanUseTypesAndAtLeastOneTypeIsDisabled()
    {
        $value = array(
            'new_values' => '1000',
            'natures' => array('_is_child', 'fixed_in')
        );

        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('_is_child')->andReturn($this->nature_is_child);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in')->andReturn($this->nature_fixed_in);
        $this->dao->shouldReceive('isTypeDisabledInProject')->with(101, 'fixed_in')->andReturn(true);
        $this->project->shouldReceive('isActive')->andReturn(true);

        $this->assertFalse($this->artifact_link_validator->isValid($value, $this->artifact, $this->field));
    }
}
