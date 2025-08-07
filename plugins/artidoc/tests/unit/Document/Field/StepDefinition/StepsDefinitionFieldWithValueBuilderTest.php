<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field\StepDefinition;

use Codendi_HTMLPurifier;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepsDefinitionFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepValue;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\TestManagement\Step\Definition\Field\StepsDefinition;
use Tuleap\TestManagement\Step\Definition\Field\StepsDefinitionChangesetValue;
use Tuleap\TestManagement\Step\Step;
use Tuleap\TestManagement\Test\Builders\ChangesetValueStepsDefinitionTestBuilder;
use Tuleap\TestManagement\Test\Builders\StepsDefinitionFieldBuilder;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class StepsDefinitionFieldWithValueBuilderTest extends TestCase
{
    private StepsDefinition $field;

    #[\Override]
    protected function setUp(): void
    {
        $this->field = StepsDefinitionFieldBuilder::aStepsDefinitionField(653)
            ->inTracker(TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->build())->build())
            ->build();
    }

    private function buildStepDefinitionFieldWithValue(?StepsDefinitionChangesetValue $value): StepsDefinitionFieldWithValue
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $builder  = new StepsDefinitionFieldWithValueBuilder(new TextValueInterpreter($purifier, CommonMarkInterpreter::build($purifier)));

        return $builder->buildStepsDefinitionFieldWithValue(new ConfiguredField($this->field, DisplayType::BLOCK), $value);
    }

    public function testItReturnsEmptyValueWhenChangesetValueIsNull(): void
    {
        self::assertEquals(
            new StepsDefinitionFieldWithValue(
                $this->field->getLabel(),
                DisplayType::BLOCK,
                [],
            ),
            $this->buildStepDefinitionFieldWithValue(null),
        );
    }

    public function testItReturnsEmptyWhenChangesetValueIsEmpty(): void
    {
        self::assertEquals(
            new StepsDefinitionFieldWithValue(
                $this->field->getLabel(),
                DisplayType::BLOCK,
                [],
            ),
            $this->buildStepDefinitionFieldWithValue(
                ChangesetValueStepsDefinitionTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(85)->build(), $this->field)
                    ->withSteps([])
                    ->build(),
            ),
        );
    }

    public function testItReturnsOptionNothingWhenNoExpectedResults(): void
    {
        self::assertEquals(
            new StepsDefinitionFieldWithValue(
                $this->field->getLabel(),
                DisplayType::BLOCK,
                [
                    new StepValue(
                        'Step 1',
                        Option::nothing(\Psl\Type\string()),
                    ),
                    new StepValue(
                        'Step 2',
                        Option::nothing(\Psl\Type\string()),
                    ),
                ],
            ),
            $this->buildStepDefinitionFieldWithValue(
                ChangesetValueStepsDefinitionTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(85)->build(), $this->field)
                    ->withSteps([
                        new Step(
                            54,
                            'Step 1',
                            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                            null,
                            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                            1,
                        ),
                        new Step(
                            55,
                            'Step 2',
                            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                            null,
                            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                            2,
                        ),
                    ])
                    ->build(),
            ),
        );
    }

    public function testItReturnsInterpretedTextContent(): void
    {
        self::assertEquals(
            new StepsDefinitionFieldWithValue(
                $this->field->getLabel(),
                DisplayType::BLOCK,
                [
                    new StepValue(
                        <<<HTML
                        <p><em>Use the feature</em></p>\n
                        HTML,
                        Option::fromValue('<p>It should work</p>'),
                    ),
                ],
            ),
            $this->buildStepDefinitionFieldWithValue(
                ChangesetValueStepsDefinitionTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(85)->build(), $this->field)
                    ->withSteps([
                        new Step(
                            54,
                            '*Use the feature*',
                            Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                            '<p>It should work</p>',
                            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT,
                            1,
                        ),
                    ])
                    ->build(),
            ),
        );
    }
}
