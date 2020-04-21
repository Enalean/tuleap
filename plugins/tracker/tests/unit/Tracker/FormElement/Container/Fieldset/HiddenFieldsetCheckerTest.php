<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Container\Fieldset;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_FormElement_Field;
use Tuleap\ForgeConfigSandbox;
use Tracker_FormElement_Container_Fieldset;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;

require_once __DIR__ . '/../../../../bootstrap.php';

class HiddenFieldsetCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var HiddenFieldsetChecker
     */
    private $checker;

    private $detector;
    private $fields_extractor;
    private $fieldset;
    private $artifact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector         = Mockery::mock(HiddenFieldsetsDetector::class);
        $this->fields_extractor = Mockery::mock(FieldsExtractor::class);

        $this->checker = new HiddenFieldsetChecker(
            $this->detector,
            $this->fields_extractor
        );

        $this->fieldset = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);
        $this->artifact = Mockery::mock(Tracker_Artifact::class);
    }

    public function testFieldsetIsHiddenIfConfiguredInState()
    {
        $this->detector->shouldReceive('isFieldsetHidden')
            ->with($this->artifact, $this->fieldset)
            ->once()
            ->andReturnTrue();

        $field = Mockery::mock(Tracker_FormElement_Field::class);
        $field->shouldReceive('isRequired')->andReturnFalse();
        $field->shouldReceive('isUsedInFieldDependency')->andReturnFalse();

        $this->fields_extractor->shouldReceive('extractFieldsInsideContainer')
            ->with($this->fieldset)
            ->once()
            ->andReturn([$field]);

        $this->assertTrue($this->checker->mustFieldsetBeHidden($this->fieldset, $this->artifact));
    }

    public function testFieldsetIsNotHiddenIfItContainsAMandatoryField()
    {
        $this->detector->shouldReceive('isFieldsetHidden')
            ->with($this->artifact, $this->fieldset)
            ->once()
            ->andReturnTrue();

        $field = Mockery::mock(Tracker_FormElement_Field::class);
        $field->shouldReceive('isRequired')->andReturnTrue();
        $field->shouldReceive('isUsedInFieldDependency')->andReturnFalse();

        $this->fields_extractor->shouldReceive('extractFieldsInsideContainer')
            ->with($this->fieldset)
            ->once()
            ->andReturn([$field]);

        $this->assertFalse($this->checker->mustFieldsetBeHidden($this->fieldset, $this->artifact));
    }

    public function testFieldsetIsNotHiddenIfNotConfiguredInState()
    {
        $this->detector->shouldReceive('isFieldsetHidden')
            ->with($this->artifact, $this->fieldset)
            ->once()
            ->andReturnFalse();

        $this->assertFalse($this->checker->mustFieldsetBeHidden($this->fieldset, $this->artifact));
    }

    public function testFieldsetIsNotHiddenIfContainsFieldUsedInFieldDependency()
    {
        $this->detector->shouldReceive('isFieldsetHidden')
            ->with($this->artifact, $this->fieldset)
            ->once()
            ->andReturnTrue();

        $field = Mockery::mock(Tracker_FormElement_Field::class);
        $field->shouldReceive('isUsedInFieldDependency')->andReturnTrue();
        $field->shouldReceive('isRequired')->andReturnFalse();

        $this->fields_extractor->shouldReceive('extractFieldsInsideContainer')
            ->with($this->fieldset)
            ->once()
            ->andReturn([$field]);

        $this->assertFalse($this->checker->mustFieldsetBeHidden($this->fieldset, $this->artifact));
    }
}
