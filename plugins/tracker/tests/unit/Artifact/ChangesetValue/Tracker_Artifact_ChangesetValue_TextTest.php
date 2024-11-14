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

final class Tracker_Artifact_ChangesetValue_TextTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private const PROJECT_ID = 101;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;

    protected function setUp(): void
    {
        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
    }

    public function testTexts(): void
    {
        $field = $this->getTextFieldWithProject();

        $text = new Tracker_Artifact_ChangesetValue_Text(111, $this->changeset, $field, false, 'Problems during installation', 'text');
        $this->assertEquals('Problems during installation', $text->getText());
        $this->assertEquals('Problems during installation', $text->getValue());
    }

    public function testItReturnsTheValueWhenFormatIsText(): void
    {
        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
        );
        $this->assertEquals('Problems with my code: <b>example</b>', $text->getContentAsText());
    }

    public function testItStripHTMLWhenFormatIsHTML(): void
    {
        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
        );
        $this->assertEquals('Problems with my code: example', $text->getContentAsText());
    }

    public function testItStripsCommonMarkMarkupWhenFormatIsCommonMark(): void
    {
        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            $this->createMock(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            'Problems with my code: **example**',
            Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT
        );
        self::assertEquals("Problems with my code: example\n", $text->getContentAsText());
    }

    public function testReturnsUnconvertedHTMLWhenFormatIsHTML(): void
    {
        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
        );
        $this->assertEquals('Problems with my code: <b>example</b>', $text->getTextWithReferences(self::PROJECT_ID));
    }

    public function testReturnsUnconvertedTextWhenFormatIsText(): void
    {
        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
        );
        $this->assertEquals('Problems with my code: &lt;b&gt;example&lt;/b&gt;', $text->getTextWithReferences(self::PROJECT_ID));
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Text
     */
    private function getTextFieldWithProject()
    {
        $tracker = Mockery::mock(Tracker::class);
        $field   = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $field->shouldReceive('getTracker')->andReturn($tracker);
        $field->shouldReceive('getId')->andReturn(1);
        $field->shouldReceive('getLabel')->andReturn('my field');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn(self::PROJECT_ID);
        $tracker->shouldReceive('getProject')->andReturn($project);

        return $field;
    }

    public function testItReturnsTheTextValue(): void
    {
        $field = $this->getTextFieldWithProject();
        $text  = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
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
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
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
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(self::PROJECT_ID);
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getTracker')->andReturn($tracker);

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
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(self::PROJECT_ID);
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getTracker')->andReturn($tracker);

        $user                 = Mockery::mock(PFUser::class);
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

        $representation = $changeset_value_text->getRESTValue($user);
        $this->assertEquals(
            "<p>Problems with my code: <strong>example</strong></p>\n",
            $representation->value,
        );
        $this->assertEquals(
            "<p>Problems with my code: <strong>example</strong></p>\n",
            $representation->post_processed_value,
        );
        $this->assertEquals('html', $representation->format);
        $this->assertEquals($text, $representation->commonmark);
    }

    public function testItBuildTheHtmlTextValueRepresentation(): void
    {
        $user  = Mockery::mock(PFUser::class);
        $text  = '<p>Problems with my code: <strong>example</strong> art #1</p>';
        $field = $this->getTextFieldWithProject();

        $purifier = $this->createMock(Codendi_HTMLPurifier::class);
        $purifier->method('purifyHTMLWithReferences')
            ->with('<p>Problems with my code: <strong>example</strong> art #1</p>', self::PROJECT_ID)
            ->willReturn('<p>Problems with my code: <strong>example</strong> <a href>art #1</a></p>');

        $changeset_value_text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            $text,
            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
        );
        $changeset_value_text->setPurifier($purifier);

        $representation = $changeset_value_text->getRESTValue($user);
        $this->assertEquals(
            '<p>Problems with my code: <strong>example</strong> art #1</p>',
            $representation->value,
        );
        $this->assertEquals('html', $representation->format);
    }

    public function testItBuildTheTextTextValueRepresentation(): void
    {
        $user  = Mockery::mock(PFUser::class);
        $text  = 'Ca débite, Ca débite art #1';
        $field = $this->getTextFieldWithProject();

        $purifier = $this->createMock(Codendi_HTMLPurifier::class);
        $purifier->method('purifyTextWithReferences')
            ->with('Ca débite, Ca débite art #1', self::PROJECT_ID)
            ->willReturn('Ca débite, Ca débite <a href>art #1</a>');

        $changeset_value_text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            $text,
            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
        );
        $changeset_value_text->setPurifier($purifier);

        $representation = $changeset_value_text->getRESTValue($user);
        $this->assertEquals(
            'Ca débite, Ca débite art #1',
            $representation->value,
        );
        $this->assertEquals(
            'Ca débite, Ca débite <a href>art #1</a>',
            $representation->post_processed_value,
        );
        $this->assertEquals('text', $representation->format);
    }
}
