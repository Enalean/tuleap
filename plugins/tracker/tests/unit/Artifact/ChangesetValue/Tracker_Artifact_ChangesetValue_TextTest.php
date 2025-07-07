<?php
/**
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\Artifact\ChangesetValue;

use Codendi_HTMLPurifier;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_ChangesetValue_TextTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private const PROJECT_ID = 101;

    public function testTexts(): void
    {
        $field = $this->getTextFieldWithProject();

        $text = new Tracker_Artifact_ChangesetValue_Text(111, ChangesetTestBuilder::aChangeset(15)->build(), $field, false, 'Problems during installation', 'text');
        self::assertEquals('Problems during installation', $text->getText());
        self::assertEquals('Problems during installation', $text->getValue());
    }

    public function testItReturnsTheValueWhenFormatIsText(): void
    {
        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            ChangesetTestBuilder::aChangeset(15)->build(),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
        );
        self::assertEquals('Problems with my code: <b>example</b>', $text->getContentAsText());
    }

    public function testItStripHTMLWhenFormatIsHTML(): void
    {
        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            ChangesetTestBuilder::aChangeset(15)->build(),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
        );
        self::assertEquals('Problems with my code: example', $text->getContentAsText());
    }

    public function testItStripsCommonMarkMarkupWhenFormatIsCommonMark(): void
    {
        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            ChangesetTestBuilder::aChangeset(15)->build(),
            $field,
            false,
            'Problems with my code: **example**',
            Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT
        );
        self::assertEquals("Problems with my code: example\n", $text->getContentAsText());
    }

    private function getTextFieldWithProject(): TextField
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();
        return TextFieldBuilder::aTextField(1)->inTracker($tracker)->withLabel('my field')->build();
    }

    public function testItReturnsTheTextValue(): void
    {
        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            ChangesetTestBuilder::aChangeset(15)->build(),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
        );
        self::assertEquals('Problems with my code: &lt;b&gt;example&lt;/b&gt;', $text->getValue());
        self::assertEquals(Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT, $text->getFormat());
    }

    public function testItReturnsTheHTMLValue(): void
    {
        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            ChangesetTestBuilder::aChangeset(15)->build(),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
        );
        self::assertEquals('Problems with my code: <b>example</b>', $text->getValue());
        self::assertEquals(Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $text->getFormat());
    }

    public function testItReturnsTheMarkdownValue(): void
    {
        $tracker   = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build())
            ->build();
        $changeset = ChangesetTestBuilder::aChangeset(15)
            ->ofArtifact(ArtifactTestBuilder::anArtifact(785)->inTracker($tracker)->build())
            ->build();

        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            $changeset,
            $field,
            false,
            'Problems with my code: **example**',
            Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT
        );
        self::assertEquals("<p>Problems with my code: <strong>example</strong></p>\n", $text->getValue());
        self::assertEquals(Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT, $text->getFormat());
    }

    public function testItBuildTheMarkdownTextValueRepresentation(): void
    {
        $tracker   = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build())
            ->build();
        $changeset = ChangesetTestBuilder::aChangeset(15)
            ->ofArtifact(ArtifactTestBuilder::anArtifact(785)->inTracker($tracker)->build())
            ->build();

        $text                 = 'Problems with my code: **example**';
        $field                = $this->getTextFieldWithProject();
        $changeset_value_text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            $changeset,
            $field,
            false,
            $text,
            Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT
        );

        $representation = $changeset_value_text->getRESTValue(UserTestBuilder::buildWithDefaults());
        self::assertEquals(
            "<p>Problems with my code: <strong>example</strong></p>\n",
            $representation->value,
        );
        self::assertEquals(
            "<p>Problems with my code: <strong>example</strong></p>\n",
            $representation->post_processed_value,
        );
        self::assertEquals('html', $representation->format);
        self::assertEquals($text, $representation->commonmark);
    }

    public function testItBuildTheHtmlTextValueRepresentation(): void
    {
        $text  = '<p>Problems with my code: <strong>example</strong> art #1</p>';
        $field = $this->getTextFieldWithProject();

        $purifier = $this->createMock(Codendi_HTMLPurifier::class);
        $purifier->method('purifyHTMLWithReferences')
            ->with('<p>Problems with my code: <strong>example</strong> art #1</p>', self::PROJECT_ID)
            ->willReturn('<p>Problems with my code: <strong>example</strong> <a href>art #1</a></p>');

        $changeset_value_text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            ChangesetTestBuilder::aChangeset(15)->build(),
            $field,
            false,
            $text,
            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
        );
        $changeset_value_text->setPurifier($purifier);

        $representation = $changeset_value_text->getRESTValue(UserTestBuilder::buildWithDefaults());
        self::assertEquals(
            '<p>Problems with my code: <strong>example</strong> art #1</p>',
            $representation->value,
        );
        self::assertEquals('html', $representation->format);
    }

    public function testItBuildTheTextTextValueRepresentation(): void
    {
        $text  = 'Ca débite, Ca débite art #1';
        $field = $this->getTextFieldWithProject();

        $purifier = $this->createMock(Codendi_HTMLPurifier::class);
        $purifier->method('purifyTextWithReferences')
            ->with('Ca débite, Ca débite art #1', self::PROJECT_ID)
            ->willReturn('Ca débite, Ca débite <a href>art #1</a>');

        $changeset_value_text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            ChangesetTestBuilder::aChangeset(15)->build(),
            $field,
            false,
            $text,
            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
        );
        $changeset_value_text->setPurifier($purifier);

        $representation = $changeset_value_text->getRESTValue(UserTestBuilder::buildWithDefaults());
        self::assertEquals(
            'Ca débite, Ca débite art #1',
            $representation->value,
        );
        self::assertEquals(
            'Ca débite, Ca débite <a href>art #1</a>',
            $representation->post_processed_value,
        );
        self::assertEquals('text', $representation->format);
    }
}
