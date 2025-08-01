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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\Description;

use Codendi_HTMLPurifier;
use LogicException;
use PFUser;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\TextResultRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DescriptionResultBuilderTest extends TestCase
{
    private PFUser $user;
    private TextValueInterpreter $text_value_interpreter;
    private Tracker $tracker;
    private TextField $field;
    private Artifact $artifact;
    private RetrieveSemanticDescriptionFieldStub $semantic_description_field;
    private Metadata $metadata_description;

    #[\Override]
    protected function setUp(): void
    {
        $this->user                   = UserTestBuilder::buildWithDefaults();
        $purifier                     = Codendi_HTMLPurifier::instance();
        $this->text_value_interpreter = new TextValueInterpreter(
            $purifier,
            CommonMarkInterpreter::build($purifier)
        );

        $project        = ProjectTestBuilder::aProject()->build();
        $this->tracker  = TrackerTestBuilder::aTracker()->withId(38)->withProject($project)->build();
        $this->artifact = ArtifactTestBuilder::anArtifact(12)->inTracker($this->tracker)->build();

        $this->field = TextFieldBuilder::aTextField(987)
            ->withReadPermission($this->user, true)
            ->inTracker($this->tracker)
            ->build();

        $this->semantic_description_field = RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField($this->field);
        $this->metadata_description       = new Metadata('description');
    }

    public function testItDoesNothingWhenSelectResultIsNull(): void
    {
        $select_results = [
            ['id' => $this->artifact->getId(), '@description' => null, '@description_format' => 'text'],
        ];

        $description_builder = new DescriptionResultBuilder(
            RetrieveArtifactStub::withArtifacts($this->artifact),
            $this->text_value_interpreter,
            $this->semantic_description_field
        );

        $result = $description_builder->getResult(
            $this->metadata_description,
            $select_results,
            $this->user,
        );

        $expected = [
            $this->artifact->getId() => new SelectedValue('@description', new TextResultRepresentation('')),
        ];

        self::assertEqualsCanonicalizing($expected, $result->values);
    }

    public function testItThrowsAnExceptionWhenArtifactIsNotFound(): void
    {
        $select_results = [['id' => $this->artifact->getId(), '@description' => 'blablabla', '@description_format' => 'text']];

        $description_builder = new DescriptionResultBuilder(
            RetrieveArtifactStub::withNoArtifact(),
            $this->text_value_interpreter,
            $this->semantic_description_field
        );

        $this->expectException(LogicException::class);

        $description_builder->getResult(
            $this->metadata_description,
            $select_results,
            $this->user,
        );
    }

    public function testItBuildsAnEmptyValueWhenSemanticIsNotSet(): void
    {
        $select_results = [['id' => $this->artifact->getId(), '@description' => 'blablabla', '@description_format' => 'text']];

        $description_builder = new DescriptionResultBuilder(
            RetrieveArtifactStub::withArtifacts($this->artifact),
            $this->text_value_interpreter,
            RetrieveSemanticDescriptionFieldStub::build()->withoutDescriptionField($this->field)
        );

        $result = $description_builder->getResult(
            $this->metadata_description,
            $select_results,
            $this->user,
        );

        $expected = [
            $this->artifact->getId() => new SelectedValue('@description', new TextResultRepresentation('')),
        ];

        self::assertEqualsCanonicalizing($expected, $result->values);
    }

    public function testItBuildsAnEmptyValueWhenUserCanNotReadField(): void
    {
        $field = TextFieldBuilder::aTextField(987)->withReadPermission($this->user, false)->inTracker($this->tracker)->build();

        $select_results = [['id' => $this->artifact->getId(), '@description' => 'blablabla', '@description_format' => 'text']];

        $description_builder = new DescriptionResultBuilder(
            RetrieveArtifactStub::withArtifacts($this->artifact),
            $this->text_value_interpreter,
            RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField($field)
        );

        $result = $description_builder->getResult(
            $this->metadata_description,
            $select_results,
            $this->user,
        );

        $expected = [$this->artifact->getId() => new SelectedValue('@description', new TextResultRepresentation('')),];

        self::assertEqualsCanonicalizing($expected, $result->values);
    }

    public function testItBuildsOnlyOnceDescriptionValue(): void
    {
        $select_results = [
            ['id' => $this->artifact->getId(), '@description' => 'blablabla', '@description_format' => 'text'],
            ['id' => $this->artifact->getId(), '@description' => 'blablabla', '@description_format' => 'text'],
        ];

        $description_builder = new DescriptionResultBuilder(
            RetrieveArtifactStub::withArtifacts($this->artifact),
            $this->text_value_interpreter,
            $this->semantic_description_field
        );

        $result = $description_builder->getResult(
            $this->metadata_description,
            $select_results,
            $this->user,
        );

        $expected = [
            $this->artifact->getId() => new SelectedValue('@description', new TextResultRepresentation('blablabla')),
        ];

        self::assertEqualsCanonicalizing($expected, $result->values);
    }

    public function testItBuildsDescriptionValue(): void
    {
        $select_results = [
            ['id' => $this->artifact->getId(), '@description' => 'blablabla', '@description_format' => 'text'],
            ['id' => 22, '@description' => null, '@description_format' => null],
            ['id' => 23, '@description' => "# Hello\n\nWorld!", '@description_format' => 'commonmark'],
        ];

        $description_builder = new DescriptionResultBuilder(
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact($this->artifact->getId())->inTracker($this->tracker)->build(),
                ArtifactTestBuilder::anArtifact(22)->inTracker($this->tracker)->build(),
                ArtifactTestBuilder::anArtifact(23)->inTracker($this->tracker)->build(),
            ),
            $this->text_value_interpreter,
            $this->semantic_description_field
        );

        $result = $description_builder->getResult(
            $this->metadata_description,
            $select_results,
            $this->user,
        );

        $expected = [
            $this->artifact->getId() => new SelectedValue('@description', new TextResultRepresentation('blablabla')),
            22 => new SelectedValue('@description', new TextResultRepresentation('')),
            23 => new SelectedValue('@description', new TextResultRepresentation(<<<EOL
<h1>Hello</h1>
<p>World!</p>\n
EOL
            )),
        ];

        self::assertEqualsCanonicalizing($expected, $result->values);
    }
}
