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

namespace Tuleap\Artidoc\Document\Field\StepsExecution;

use Codendi_HTMLPurifier;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepsExecutionFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepResultValue;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\TestManagement\Step\Execution\Field\StepsExecution;
use Tuleap\TestManagement\Step\Execution\Field\StepsExecutionChangesetValue;
use Tuleap\TestManagement\Step\Execution\StepResult;
use Tuleap\TestManagement\Step\Step;
use Tuleap\TestManagement\Test\Builders\ChangesetValueStepsExecutionTestBuilder;
use Tuleap\TestManagement\Test\Builders\StepsExecutionFieldBuilder;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class StepsExecutionFieldWithValueBuilderTest extends TestCase
{
    private StepsExecution $field;

    #[\Override]
    protected function setUp(): void
    {
        $this->field = StepsExecutionFieldBuilder::aStepsExecutionField(653)
            ->inTracker(TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->build())->build())
            ->build();
    }

    private function buildStepsExecutionFieldWithValue(?StepsExecutionChangesetValue $value): StepsExecutionFieldWithValue
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $builder  = new StepsExecutionFieldWithValueBuilder(new TextValueInterpreter($purifier, CommonMarkInterpreter::build($purifier)));

        return $builder->buildStepsExecutionFieldWithValue(new ConfiguredField($this->field, DisplayType::BLOCK), $value);
    }

    public function testItReturnsEmptyValueWhenChangesetValueIsNull(): void
    {
        self::assertEquals(
            new StepsExecutionFieldWithValue(
                $this->field->getLabel(),
                DisplayType::BLOCK,
                [],
            ),
            $this->buildStepsExecutionFieldWithValue(null),
        );
    }

    public function testItReturnsEmptyWhenChangesetValueIsEmpty(): void
    {
        self::assertEquals(
            new StepsExecutionFieldWithValue(
                $this->field->getLabel(),
                DisplayType::BLOCK,
                [],
            ),
            $this->buildStepsExecutionFieldWithValue(
                ChangesetValueStepsExecutionTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(85)->build(), $this->field)
                    ->withStepsResults([])
                    ->build(),
            ),
        );
    }

    public function testItReturnsOptionNothingWhenNoExpectedResults(): void
    {
        self::assertEquals(
            new StepsExecutionFieldWithValue(
                $this->field->getLabel(),
                DisplayType::BLOCK,
                [
                    new StepResultValue(
                        'Step 1',
                        Option::nothing(\Psl\Type\string()),
                        'passed',
                    ),
                    new StepResultValue(
                        'Step 2',
                        Option::nothing(\Psl\Type\string()),
                        'notrun',
                    ),
                ],
            ),
            $this->buildStepsExecutionFieldWithValue(
                ChangesetValueStepsExecutionTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(85)->build(), $this->field)
                    ->withStepsResults([
                        new StepResult(new Step(
                            54,
                            'Step 1',
                            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                            null,
                            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                            1,
                        ), 'passed'),
                        new StepResult(new Step(
                            55,
                            'Step 2',
                            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                            null,
                            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                            2,
                        ), 'notrun'),
                    ])
                    ->build(),
            ),
        );
    }

    public function testItReturnsInterpretedTextContent(): void
    {
        self::assertEquals(
            new StepsExecutionFieldWithValue(
                $this->field->getLabel(),
                DisplayType::BLOCK,
                [
                    new StepResultValue(
                        <<<HTML
                        <p><em>Use the feature</em></p>\n
                        HTML,
                        Option::fromValue('<p>It should work</p>'),
                        'blocked'
                    ),
                ],
            ),
            $this->buildStepsExecutionFieldWithValue(
                ChangesetValueStepsExecutionTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(85)->build(), $this->field)
                    ->withStepsResults([
                        new StepResult(new Step(
                            54,
                            '*Use the feature*',
                            Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                            '<p>It should work</p>',
                            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT,
                            1,
                        ), 'blocked'),
                    ])
                    ->build(),
            ),
        );
    }
}
