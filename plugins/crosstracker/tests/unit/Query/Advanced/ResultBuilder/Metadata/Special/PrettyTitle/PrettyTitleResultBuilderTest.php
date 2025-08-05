<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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


namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\PrettyTitle;

use LogicException;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Color\ColorName;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\PrettyTitleRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class PrettyTitleResultBuilderTest extends TestCase
{
    private PFUser $user;
    private Tracker $tracker;
    private TextField $field;
    private Artifact $artifact;
    private RetrieveSemanticTitleFieldStub $semantic_pretty_title_field;

    #[\Override]
    protected function setUp(): void
    {
        $this->user     = UserTestBuilder::buildWithDefaults();
        $project        = ProjectTestBuilder::aProject()->build();
        $this->tracker  = TrackerTestBuilder::aTracker()->withId(38)->withProject($project)->withColor(ColorName::FIESTA_RED)->withName('my-tracker')->build();
        $this->artifact = ArtifactTestBuilder::anArtifact(223)->withTitle('My artifact')->inTracker($this->tracker)->build();

        $this->field = TextFieldBuilder::aTextField(987)
            ->withReadPermission($this->user, true)
            ->inTracker($this->tracker)
            ->build();

        $this->semantic_pretty_title_field = RetrieveSemanticTitleFieldStub::build()->withTitleField($this->field);
    }

    public function testItThrowsWhenArtifactIsNotFound(): void
    {
        $pretty_title_builder = new PrettyTitleResultBuilder(
            RetrieveArtifactStub::withNoArtifact(),
            $this->semantic_pretty_title_field,
        );

        $this->expectException(LogicException::class);

        $pretty_title_builder->getResult(
            [
                ['id' => $this->artifact->getId(), '@pretty_title.tracker' => 'tracker_38', '@pretty_title.color' => 'inca-silver', '@pretty_title' => 'title 121', '@pretty_title.format' => 'text'],
            ],
            $this->user
        );
    }

    public function testItReturnsAnEmptyValueWhenArtifactDoesNotHaveTitleSemanticSet(): void
    {
        $pretty_title_builder = new PrettyTitleResultBuilder(
            RetrieveArtifactStub::withArtifacts($this->artifact),
            RetrieveSemanticTitleFieldStub::build()->withoutTitleField($this->field),
        );

        $select_result = ['id' => $this->artifact->getId(), '@pretty_title.tracker' => 'tracker_38', '@pretty_title.color' => 'inca-silver', '@pretty_title' => '', '@pretty_title.format' => 'text'];

        $result = $pretty_title_builder->getResult(
            [$select_result],
            $this->user
        );

        $expected = new SelectedValue('@pretty_title', new PrettyTitleRepresentation($select_result['@pretty_title.tracker'], $select_result['@pretty_title.color'], $this->artifact->getId(), ''));
        self::assertEqualsCanonicalizing($expected, $result->values[$this->artifact->getId()]);
    }

    public function testItBuildsAnEmptyTitleWhenUserCanNotReadTitleField(): void
    {
        $field = TextFieldBuilder::aTextField(987)
            ->withReadPermission($this->user, false)
            ->inTracker($this->tracker)
            ->build();

        $pretty_title_builder = new PrettyTitleResultBuilder(
            RetrieveArtifactStub::withArtifacts($this->artifact),
            RetrieveSemanticTitleFieldStub::build()->withTitleField($field),
        );

        $select_result = ['id' => $this->artifact->getId(), '@pretty_title.tracker' => 'tracker_38', '@pretty_title.color' => 'inca-silver', '@pretty_title' => 'title 121', '@pretty_title.format' => 'text'];
        $result        = $pretty_title_builder->getResult(
            [$select_result],
            $this->user
        );

        $expected = new SelectedValue('@pretty_title', new PrettyTitleRepresentation($select_result['@pretty_title.tracker'], $select_result['@pretty_title.color'], $this->artifact->getId(), ''));
        self::assertEqualsCanonicalizing($expected, $result->values[$this->artifact->getId()]);
    }

    public function testItBuildsPrettyTitle(): void
    {
        $second_artifact = ArtifactTestBuilder::anArtifact(123)->inTracker($this->tracker)->build();
        $this->semantic_pretty_title_field->withTitleField($this->field);
        $pretty_title_builder = new PrettyTitleResultBuilder(
            RetrieveArtifactStub::withArtifacts($this->artifact, $second_artifact),
            $this->semantic_pretty_title_field,
        );

        $first_result  = ['id' => $this->artifact->getId(), '@pretty_title.tracker' => 'tracker_38', '@pretty_title.color' => 'inca-silver', '@pretty_title' => 'title 121', '@pretty_title.format' => 'text'];
        $second_result = ['id' => $second_artifact->getId(), '@pretty_title.tracker' => 'tracker_38', '@pretty_title.color' => 'inca-silver', '@pretty_title' => 'title 123', '@pretty_title.format' => 'text'];

        $result = $pretty_title_builder->getResult(
            [$first_result, $second_result],
            $this->user
        );

        $first_expected = new SelectedValue('@pretty_title', new PrettyTitleRepresentation($first_result['@pretty_title.tracker'], $first_result['@pretty_title.color'], $this->artifact->getId(), $first_result['@pretty_title']));
        self::assertEqualsCanonicalizing($first_expected, $result->values[$this->artifact->getId()]);

        $second_expected = new SelectedValue('@pretty_title', new PrettyTitleRepresentation($second_result['@pretty_title.tracker'], $second_result['@pretty_title.color'], $second_artifact->getId(), $second_result['@pretty_title']));
        self::assertEqualsCanonicalizing($second_expected, $result->values[$second_artifact->getId()]);
    }
}
