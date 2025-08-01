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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\Title;

use Codendi_HTMLPurifier;
use LogicException;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
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
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class TitleResultBuilderTest extends TestCase
{
    private PFUser $user;
    private TextValueInterpreter $text_value_interpreter;
    private Tracker $tracker;
    private TextField $field;
    private Artifact $artifact;
    private RetrieveSemanticTitleFieldStub $semantic_title_field;
    private Metadata $metadata_title;

    #[\Override]
    protected function setUp(): void
    {
        $this->user                   = UserTestBuilder::buildWithDefaults();
        $purifier                     = Codendi_HTMLPurifier::instance();
        $this->text_value_interpreter = new TextValueInterpreter(
            $purifier,
            CommonMarkInterpreter::build($purifier)
        );
        $project                      = ProjectTestBuilder::aProject()->build();
        $this->tracker                = TrackerTestBuilder::aTracker()->withId(38)->withProject($project)->build();
        $this->artifact               = ArtifactTestBuilder::anArtifact(11)->inTracker($this->tracker)->build();

        $this->field = TextFieldBuilder::aTextField(987)
            ->withReadPermission($this->user, true)
            ->inTracker($this->tracker)
            ->build();

        $this->semantic_title_field = RetrieveSemanticTitleFieldStub::build()->withTitleField($this->field);
        $this->metadata_title       = new Metadata('title');
    }

    public function testItBuildsEmptyValueWhenValueIsNull(): void
    {
        $title_builder = new TitleResultBuilder(
            RetrieveArtifactStub::withArtifacts(
                $this->artifact
            ),
            $this->text_value_interpreter,
            $this->semantic_title_field,
        );

        $result = $title_builder->getResult(
            $this->metadata_title,
            [['id' => $this->artifact->getId(), '@title' => null, '@title_format' => 'text']],
            $this->user
        );
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@title', new TextResultRepresentation('')),
        ], $result->values);
    }

    public function testItThrowsWhenArtifactIsNotFound(): void
    {
        $title_builder = new TitleResultBuilder(
            RetrieveArtifactStub::withNoArtifact(),
            $this->text_value_interpreter,
            $this->semantic_title_field,
        );

        $this->expectException(LogicException::class);

        $title_builder->getResult(
            $this->metadata_title,
            [['id' => $this->artifact->getId(), '@title' => 'My title', '@title_format' => 'text']],
            $this->user
        );
    }

    public function testItBuildsEmptyValueWhenSemanticIsNotDefined(): void
    {
        $title_builder = new TitleResultBuilder(
            RetrieveArtifactStub::withArtifacts(
                $this->artifact
            ),
            $this->text_value_interpreter,
            RetrieveSemanticTitleFieldStub::build()->withoutTitleField($this->field)
        );

        $result = $title_builder->getResult(
            $this->metadata_title,
            [['id' => $this->artifact->getId(), '@title' => 'My title', '@title_format' => 'text']],
            $this->user
        );
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@title', new TextResultRepresentation('')),
        ], $result->values);
    }

    public function testItBuildsEmptyValueWhenUserCanNotReadField(): void
    {
        $field = TextFieldBuilder::aTextField(987)
            ->withReadPermission($this->user, false)
            ->inTracker($this->tracker)
            ->build();

        $title_builder = new TitleResultBuilder(
            RetrieveArtifactStub::withArtifacts(
                $this->artifact
            ),
            $this->text_value_interpreter,
            RetrieveSemanticTitleFieldStub::build()->withTitleField($field),
        );

        $result = $title_builder->getResult(
            $this->metadata_title,
            [['id' => $this->artifact->getId(), '@title' => 'My title', '@title_format' => 'text']],
            $this->user
        );
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@title', new TextResultRepresentation('')),
        ], $result->values);
    }

    public function testItBuildsOnlyOnceTitleValue(): void
    {
        $title_builder = new TitleResultBuilder(
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact($this->artifact->getId())->inTracker($this->tracker)->build(),
            ),
            $this->text_value_interpreter,
            $this->semantic_title_field,
        );

        $result = $title_builder->getResult(
            $this->metadata_title,
            [
                ['id' => $this->artifact->getId(), '@title' => 'My title', '@title_format' => 'text'],
                ['id' => $this->artifact->getId(), '@title' => 'My title', '@title_format' => 'text'],
            ],
            $this->user
        );
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@title', new TextResultRepresentation('My title')),
        ], $result->values);
    }

    public function testItBuildTitleValue(): void
    {
        $title_builder = new TitleResultBuilder(
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact($this->artifact->getId())->inTracker($this->tracker)->build(),
                ArtifactTestBuilder::anArtifact(12)->inTracker($this->tracker)->build(),
                ArtifactTestBuilder::anArtifact(13)->inTracker($this->tracker)->build(),
            ),
            $this->text_value_interpreter,
            $this->semantic_title_field,
        );

        $result = $title_builder->getResult(
            $this->metadata_title,
            [
                ['id' => $this->artifact->getId(), '@title' => 'My title', '@title_format' => 'text'],
                ['id' => 12, '@title' => null, '@title_format' => null],
                ['id' => 13, '@title' => '**Title**', '@title_format' => 'commonmark'],
            ],
            $this->user
        );
        self::assertEqualsCanonicalizing([
            $this->artifact->getId() => new SelectedValue('@title', new TextResultRepresentation('My title')),
            12 => new SelectedValue('@title', new TextResultRepresentation('')),
            13 => new SelectedValue('@title', new TextResultRepresentation(<<<EOL
<p><strong>Title</strong></p>\n
EOL
            )),
        ], $result->values);
    }
}
