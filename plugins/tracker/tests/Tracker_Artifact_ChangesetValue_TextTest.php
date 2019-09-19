<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once('bootstrap.php');

Mock::generatePartial('Tracker_Artifact_ChangesetValue_Text', 'Tracker_Artifact_ChangesetValue_TextTestVersion', array('getCodendi_HTMLPurifier'));

class Tracker_Artifact_ChangesetValue_TextTest extends TuleapTestCase
{

    private $field;
    private $user;

    public function setUp()
    {
        parent::setUp();

        $base_language = mock('BaseLanguage');
        stub($base_language)->getText('plugin_tracker_include_artifact', 'toggle_diff')->returns('Show diff');

        $GLOBALS['Language'] = $base_language;

        $this->field = stub('Tracker_FormElement_Field_Text')->getName()->returns('field_text');
        $this->user  = aUser()->withId(101)->build();

        $this->changeset = mock('Tracker_Artifact_Changeset');
    }

    public function tearDown()
    {
        unset($GLOBALS['Language']);

        parent::tearDown();
    }

    public function testTexts()
    {
        $field = aTextField()->withTracker(aTracker()->withProject(mock('Project'))->build())->build();
        $text  = new Tracker_Artifact_ChangesetValue_Text(111, $this->changeset, $field, false, 'Problems during installation', 'text');
        $this->assertEqual($text->getText(), 'Problems during installation');
        $this->assertEqual($text->getValue(), 'Problems during installation');
    }

    public function testNoDiff()
    {
        $text_1 = new Tracker_Artifact_ChangesetValue_Text(111, $this->changeset, $this->field, false, 'Problems during installation', 'text');
        $text_2 = new Tracker_Artifact_ChangesetValue_Text(111, $this->changeset, $this->field, false, 'Problems during installation', 'text');
        $this->assertFalse($text_1->diff($text_2));
        $this->assertFalse($text_2->diff($text_1));
    }

    public function testDiff()
    {
        $text_1 = new Tracker_Artifact_ChangesetValue_Text(111, $this->changeset, $this->field, false, 'Problems during <ins> installation', 'text');
        $text_2 = new Tracker_Artifact_ChangesetValue_Text(111, $this->changeset, $this->field, false, 'FullTextSearch does not work on Wiki pages', 'text');
        $this->assertEqual($text_1->diff($text_2), '<button class="btn btn-mini toggle-diff">' . $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'toggle_diff') . '</button>'.
                                                    '<div class="diff" style="display: none">'.
                                                    '<div class="block">'.
                                                        '<div class="difftext">'.
                                                            '<div class="original">'.
                                                                '<tt class="prefix">-</tt>'.
                                                                '<del>FullTextSearch does not work on Wiki pages</del>&nbsp;'.
                                                            '</div>'.
                                                        '</div>'.
                                                        '<div class="difftext">'.
                                                            '<div class="final">'.
                                                                '<tt class="prefix">+</tt>'.
                                                                '<ins>Problems during &lt;ins&gt; installation</ins>&nbsp;'.
                                                            '</div>'.
                                                        '</div>'.
                                                    '</div>'.
                                                    '</div>');
        $this->assertEqual($text_2->diff($text_1), '<button class="btn btn-mini toggle-diff">' . $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'toggle_diff') . '</button>'.
                                                    '<div class="diff" style="display: none">'.
                                                    '<div class="block">'.
                                                        '<div class="difftext">'.
                                                            '<div class="original">'.
                                                                '<tt class="prefix">-</tt>'.
                                                                '<del>Problems during &lt;ins&gt; installation</del>&nbsp;'.
                                                            '</div>'.
                                                        '</div>'.
                                                        '<div class="difftext">'.
                                                            '<div class="final">'.
                                                                '<tt class="prefix">+</tt>'.
                                                                '<ins>FullTextSearch does not work on Wiki pages</ins>&nbsp;'.
                                                            '</div>'.
                                                        '</div>'.
                                                    '</div>'.
                                                    '</div>');
    }
}

class Tracker_Artifact_ChangesetValue_Text_getContentAsTextTest extends TuleapTestCase
{

    public function itReturnsTheValueWhenFormatIsText()
    {
        $field = aTextField()->withTracker(aTracker()->withProject(mock('Project'))->build())->build();
        $text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            mock('Tracker_Artifact_Changeset'),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
        );
        $this->assertEqual($text->getContentAsText(), 'Problems with my code: <b>example</b>');
    }

    public function itStripHTMLWhenFormatIsHTML()
    {
        $field = aTextField()->withTracker(aTracker()->withProject(mock('Project'))->build())->build();
        $text = new Tracker_Artifact_ChangesetValue_Text(
            111,
            mock('Tracker_Artifact_Changeset'),
            $field,
            false,
            'Problems with my code: <b>example</b>',
            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
        );
        $this->assertEqual($text->getContentAsText(), 'Problems with my code: example');
    }
}

class Tracker_Artifact_ChangesetValue_Text_RESTTest extends TuleapTestCase
{

    public function itReturnsTheRESTValue()
    {
        $field = stub('Tracker_FormElement_Field_Text')->getName()->returns('field_text');
        $user  = aUser()->withId(101)->build();

        $changeset = new Tracker_Artifact_ChangesetValue_Text(111, mock('Tracker_Artifact_Changeset'), $field, true, 'myxedemic enthymematic', 'html');
        $representation = $changeset->getRESTValue($user, $changeset);

        $this->assertEqual($representation->value, 'myxedemic enthymematic');
        $this->assertEqual($representation->format, 'html');
    }
}
