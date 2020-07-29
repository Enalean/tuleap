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

final class Tracker_Artifact_ChangesetValue_TextTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $field;

    protected function setUp(): void
    {
        $this->field = \Mockery::spy(\Tracker_FormElement_Field_Text::class)->shouldReceive('getName')->andReturns('field_text')->getMock();

        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
    }

    public function testTexts(): void
    {
        $field = $this->getTextFieldWithProject();

        $text  = new Tracker_Artifact_ChangesetValue_Text(111, $this->changeset, $field, false, 'Problems during installation', 'text');
        $this->assertEquals('Problems during installation', $text->getText());
        $this->assertEquals('Problems during installation', $text->getValue());
    }

    public function testNoDiff(): void
    {
        $text_1 = new Tracker_Artifact_ChangesetValue_Text(111, $this->changeset, $this->field, false, 'Problems during installation', 'text');
        $text_2 = new Tracker_Artifact_ChangesetValue_Text(111, $this->changeset, $this->field, false, 'Problems during installation', 'text');

        $this->assertEquals('', $text_1->diff($text_2));
        $this->assertEquals('', $text_2->diff($text_1));
    }

    public function testDiff(): void
    {
        $text_1 = new Tracker_Artifact_ChangesetValue_Text(
            111,
            $this->changeset,
            $this->field,
            false,
            'Problems during <ins> installation',
            'text'
        );

        $previous = ['Problems during <ins> installation'];
        $next     = ['FullTextSearch does not work on Wiki pages'];

        $this->assertStringContainsString(
            '- FullTextSearch does not work on Wiki pages',
            $text_1->fetchDiff($next, $previous, 'text')
        );
        $this->assertStringContainsString(
            '+ Problems during <ins> installation',
            $text_1->fetchDiff($next, $previous, 'text')
        );

        $this->assertStringContainsString(
            '+ FullTextSearch does not work on Wiki pages',
            $text_1->fetchDiff($previous, $next, 'text')
        );
        $this->assertStringContainsString(
            '- Problems during <ins> installation',
            $text_1->fetchDiff($previous, $next, 'text')
        );
    }

    public function testItReturnsTheValueWhenFormatIsText(): void
    {
        $field = $this->getTextFieldWithProject();
        $text = new Tracker_Artifact_ChangesetValue_Text(
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
        $text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
        );
        $this->assertEquals('Problems with my code: example', $text->getContentAsText());
    }

    public function testItReturnsTheRESTValue(): void
    {
        $field = $this->getTextFieldWithProject();
        $user  = Mockery::mock(PFUser::class);

        $changeset = new Tracker_Artifact_ChangesetValue_Text(111, \Mockery::spy(\Tracker_Artifact_Changeset::class), $field, true, 'myxedemic enthymematic', 'html');
        $representation = $changeset->getRESTValue($user);

        $this->assertEquals('myxedemic enthymematic', $representation->value);
        $this->assertEquals('html', $representation->format);
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
        $field->shouldReceive('getLabel')->andReturn("my field");

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getProject')->andReturn($project);

        return $field;
    }

    public function testItReturnsTheTextValue(): void
    {
        $field = $this->getTextFieldWithProject();
        $text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
        );
        $this->assertEquals('Problems with my code: &lt;b&gt;example&lt;/b&gt;', $text->getValue());
    }

    public function testItReturnsTheHTMLValue(): void
    {
        $field = $this->getTextFieldWithProject();
        $text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
        );
        $this->assertEquals('Problems with my code: <b>example</b>', $text->getValue());
    }

    public function testItReturnsTheMarkdownValue(): void
    {
        $field = $this->getTextFieldWithProject();
        $text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            'Problems with my code: **example**',
            Tracker_Artifact_ChangesetValue_Text::MARKDOWN_CONTENT
        );
        $this->assertEquals("<p>Problems with my code: <strong>example</strong></p>\n", $text->getValue());
    }

    public function testItConsidersMarkdownAsTextFormat(): void
    {
        $field = $this->getTextFieldWithProject();
        $text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            'Problems with my code: **example**',
            Tracker_Artifact_ChangesetValue_Text::MARKDOWN_CONTENT
        );

        $this->assertEquals(Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT, $text->getFormat());
    }
}
