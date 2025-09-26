<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Container\Fieldset;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FieldsetContainerBuilder;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;

#[DisableReturnValueGenerationForTestDoubles]
final class HiddenFieldsetCheckerTest extends TestCase
{
    use ForgeConfigSandbox;

    private HiddenFieldsetChecker $checker;
    private HiddenFieldsetsDetector&MockObject $detector;
    private FieldsExtractor&MockObject $fields_extractor;
    private FieldsetContainer $fieldset;
    private Artifact $artifact;

    #[\Override]
    protected function setUp(): void
    {
        $this->detector         = $this->createMock(HiddenFieldsetsDetector::class);
        $this->fields_extractor = $this->createMock(FieldsExtractor::class);

        $this->checker = new HiddenFieldsetChecker(
            $this->detector,
            $this->fields_extractor
        );

        $this->fieldset = FieldsetContainerBuilder::aFieldset(69753)->build();
        $this->artifact = ArtifactTestBuilder::anArtifact(14865)->build();
    }

    public function testFieldsetIsHiddenIfConfiguredInState(): void
    {
        $this->detector->expects($this->once())->method('isFieldsetHidden')
            ->with($this->artifact, $this->fieldset)->willReturn(true);

        $field = $this->createMock(TrackerField::class);
        $field->method('isRequired')->willReturn(false);
        $field->method('isUsedInFieldDependency')->willReturn(false);

        $this->fields_extractor->expects($this->once())->method('extractFieldsInsideContainer')
            ->with($this->fieldset)->willReturn([$field]);

        self::assertTrue($this->checker->mustFieldsetBeHidden($this->fieldset, $this->artifact));
    }

    public function testFieldsetIsNotHiddenIfItContainsAMandatoryField(): void
    {
        $this->detector->expects($this->once())->method('isFieldsetHidden')
            ->with($this->artifact, $this->fieldset)->willReturn(true);

        $field = $this->createMock(TrackerField::class);
        $field->method('isRequired')->willReturn(true);
        $field->method('isUsedInFieldDependency')->willReturn(false);

        $this->fields_extractor->expects($this->once())->method('extractFieldsInsideContainer')
            ->with($this->fieldset)->willReturn([$field]);

        self::assertFalse($this->checker->mustFieldsetBeHidden($this->fieldset, $this->artifact));
    }

    public function testFieldsetIsNotHiddenIfNotConfiguredInState(): void
    {
        $this->detector->expects($this->once())->method('isFieldsetHidden')
            ->with($this->artifact, $this->fieldset)->willReturn(false);

        self::assertFalse($this->checker->mustFieldsetBeHidden($this->fieldset, $this->artifact));
    }

    public function testFieldsetIsNotHiddenIfContainsFieldUsedInFieldDependency(): void
    {
        $this->detector->expects($this->once())->method('isFieldsetHidden')
            ->with($this->artifact, $this->fieldset)->willReturn(true);

        $field = $this->createMock(TrackerField::class);
        $field->method('isUsedInFieldDependency')->willReturn(true);
        $field->method('isRequired')->willReturn(false);

        $this->fields_extractor->expects($this->once())->method('extractFieldsInsideContainer')
            ->with($this->fieldset)->willReturn([$field]);

        self::assertFalse($this->checker->mustFieldsetBeHidden($this->fieldset, $this->artifact));
    }
}
