<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Step\Definition\Field;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\Artifact\Artifact;

final class StepDefinitionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var StepDefinition
     */
    private $field;

    protected function setUp(): void
    {
        $this->field = new StepDefinition(102, 111, 101, 'step_def', 'Steps', '', true, 'S', true, false, 1);
    }

    public function testHasChangesReturnsFalseIfNewValuesIsNull(): void
    {
        self::assertFalse(
            $this->field->hasChanges(
                Mockery::mock(Artifact::class),
                new StepDefinitionChangesetValue(
                    1,
                    Mockery::mock(Tracker_Artifact_Changeset::class),
                    $this->field,
                    false,
                    []
                ),
                null
            )
        );
    }

    public function testHasChangesReturnsTrueIfNewValuesClearTheContent(): void
    {
        self::assertTrue(
            $this->field->hasChanges(
                Mockery::mock(Artifact::class),
                new StepDefinitionChangesetValue(
                    1,
                    Mockery::mock(Tracker_Artifact_Changeset::class),
                    $this->field,
                    true,
                    [
                        new Step(
                            1,
                            'step',
                            'html',
                            '',
                            'text',
                            1
                        )
                    ]
                ),
                [
                'no_steps' => true
                ]
            )
        );
    }

    public function testHasChangesReturnsTrueIfContentChanged(): void
    {
        self::assertTrue(
            $this->field->hasChanges(
                Mockery::mock(Artifact::class),
                new StepDefinitionChangesetValue(
                    1,
                    Mockery::mock(Tracker_Artifact_Changeset::class),
                    $this->field,
                    true,
                    [
                        new Step(
                            1,
                            'step',
                            'html',
                            '',
                            'text',
                            1
                        )
                    ]
                ),
                [
                'description' => [
                    'step updated'
                ],
                'description_format' => [
                    'html'
                ],
                'expected_results' => [
                    ''
                ],
                'expected_results_format' => [
                    'text'
                ],
                'id' => [
                    1
                ]
                ]
            )
        );
    }

    public function testHasChangesReturnsFalseIfContentDidNotChange(): void
    {
        self::assertFalse(
            $this->field->hasChanges(
                Mockery::mock(Artifact::class),
                new StepDefinitionChangesetValue(
                    1,
                    Mockery::mock(Tracker_Artifact_Changeset::class),
                    $this->field,
                    true,
                    [
                        new Step(
                            1,
                            'step',
                            'html',
                            '',
                            'text',
                            1
                        )
                    ]
                ),
                [
                    'description'             => [
                        'step'
                    ],
                    'description_format'      => [
                        'html'
                    ],
                    'expected_results'        => [
                        ''
                    ],
                    'expected_results_format' => [
                        'text'
                    ],
                    'id'                      => [
                        1
                    ]
                ]
            )
        );
    }

    public function testItReturnsTheConvertedRESTFormatInDBCompatibleFormatBeforeInsertion(): void
    {
        $steps = [
            "value" => [
                [
                    "description"             => "some description",
                    "description_format"      => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                    "expected_results"        => "somme results",
                    "expected_results_format" => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT
                ],
            ],
        ];

        $expected_converted_step = [
            "description"             => ["some description"],
            "description_format"      => [
                Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            ],
            "expected_results"        => ["somme results"],
            "expected_results_format" => [
                Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            ]
        ];


        self::assertEquals($expected_converted_step, $this->field->getFieldDataFromRESTValue($steps));
        self::assertEquals($expected_converted_step, $this->field->getFieldDataFromRESTValueByField($steps));
    }

    public function testItReturnsNullIfTheRESTStepDefinitionValueIsNotSet(): void
    {
        $steps = ["value" => null];


        self::assertNull($this->field->getFieldDataFromRESTValue($steps));
        self::assertNull($this->field->getFieldDataFromRESTValueByField($steps));
    }

    public function testItReturnsNullIfTheRESTStepDefinitionValueIsEmpty(): void
    {
        $steps = ["value" => []];


        self::assertNull($this->field->getFieldDataFromRESTValue($steps));
        self::assertNull($this->field->getFieldDataFromRESTValueByField($steps));
    }

    public function testItDoesNotAllowUpdatingAnExistingStepDefinition(): void
    {
        $steps = [
            "value" => [
                "description"             => "some description",
                "description_format"      => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                "expected_results"        => "somme results",
                "expected_results_format" => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT
            ],
        ];

        $artifact = Mockery::mock(Artifact::class);

        self::assertNull($this->field->getFieldDataFromRESTValue($steps, $artifact));
        self::assertNull($this->field->getFieldDataFromRESTValueByField($steps, $artifact));
    }
}
