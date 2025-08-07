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

use Luracast\Restler\RestException;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StepsDefinitionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private StepsDefinition $field;

    #[\Override]
    protected function setUp(): void
    {
        $this->field = new StepsDefinition(102, 111, 101, 'step_def', 'Steps', '', true, 'S', true, false, 1);
    }

    public function testHasChangesReturnsFalseIfNewValuesIsNull(): void
    {
        self::assertFalse(
            $this->field->hasChanges(
                ArtifactTestBuilder::anArtifact(1)->build(),
                new StepsDefinitionChangesetValue(
                    1,
                    $this->createStub(Tracker_Artifact_Changeset::class),
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
                ArtifactTestBuilder::anArtifact(1)->build(),
                new StepsDefinitionChangesetValue(
                    1,
                    $this->createStub(Tracker_Artifact_Changeset::class),
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
                        ),
                    ]
                ),
                [
                    'no_steps' => true,
                ]
            )
        );
    }

    public function testHasChangesReturnsTrueIfContentChanged(): void
    {
        self::assertTrue(
            $this->field->hasChanges(
                ArtifactTestBuilder::anArtifact(1)->build(),
                new StepsDefinitionChangesetValue(
                    1,
                    $this->createStub(Tracker_Artifact_Changeset::class),
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
                        ),
                    ]
                ),
                [
                    'description' => [
                        'step updated',
                    ],
                    'description_format' => [
                        'html',
                    ],
                    'expected_results' => [
                        '',
                    ],
                    'expected_results_format' => [
                        'text',
                    ],
                    'id' => [
                        1,
                    ],
                ]
            )
        );
    }

    public function testHasChangesReturnsFalseIfContentDidNotChange(): void
    {
        self::assertFalse(
            $this->field->hasChanges(
                ArtifactTestBuilder::anArtifact(1)->build(),
                new StepsDefinitionChangesetValue(
                    1,
                    $this->createStub(Tracker_Artifact_Changeset::class),
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
                        ),
                    ]
                ),
                [
                    'description'             => [
                        'step',
                    ],
                    'description_format'      => [
                        'html',
                    ],
                    'expected_results'        => [
                        '',
                    ],
                    'expected_results_format' => [
                        'text',
                    ],
                    'id'                      => [
                        1,
                    ],
                ]
            )
        );
    }

    public function testItReturnsTheConvertedRESTFormatInDBCompatibleFormatBeforeInsertion(): void
    {
        $steps = [
            'value' => [
                [
                    'description'             => 'some description',
                    'description_format'      => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                    'expected_results'        => 'some results',
                    'expected_results_format' => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                ],
            ],
        ];

        $expected_converted_step = [
            'description'             => ['some description'],
            'description_format'      => [
                Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            ],
            'expected_results'        => ['some results'],
            'expected_results_format' => [
                Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            ],
        ];


        self::assertEquals($expected_converted_step, $this->field->getFieldDataFromRESTValue($steps));
        self::assertEquals($expected_converted_step, $this->field->getFieldDataFromRESTValueByField($steps));
    }

    public function testReturnsConvertedRESTFormatInDBCompatibleFormatBeforeInsertionOnUpdate(): void
    {
        $steps = [
            'value' => [
                [
                    'description' => 'some description',
                    'description_format' => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                    'expected_results' => 'some results',
                    'expected_results_format' => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                    'rank' => 1,
                ],
            ],
        ];

        $artifact = ArtifactTestBuilder::anArtifact(1)->build();

        $expected_format = [
            'description' => ['some description'],
            'description_format' => ['commonmark'],
            'expected_results' => ['some results'],
            'expected_results_format' => ['commonmark'],
        ];

        self::assertEquals($expected_format, $this->field->getFieldDataFromRESTValue($steps, $artifact));
        self::assertEquals($expected_format, $this->field->getFieldDataFromRESTValueByField($steps, $artifact));
    }

    public function testItReturnsNullIfTheRESTStepDefinitionValueIsNotSet(): void
    {
        $steps = ['value' => null];


        self::assertNull($this->field->getFieldDataFromRESTValue($steps));
        self::assertNull($this->field->getFieldDataFromRESTValueByField($steps));
    }

    public function testReturnsNoStepsIfTheRESTStepDefinitionValueIsEmpty(): void
    {
        $steps = ['value' => []];

        $expected_value = ['no_steps' => true];

        self::assertEquals($expected_value, $this->field->getFieldDataFromRESTValue($steps));
        self::assertEquals($expected_value, $this->field->getFieldDataFromRESTValueByField($steps));
    }

    public function testVerifiesThatRESTDefinitionValueIsAnArray(): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->field->getFieldDataFromRESTValue(['value' => 'foo']);
    }
}
