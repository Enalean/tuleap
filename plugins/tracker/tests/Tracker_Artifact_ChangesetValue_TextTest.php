<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('bootstrap.php');
Mock::generate('Tracker_Artifact');

Mock::generatePartial('Tracker_Artifact_ChangesetValue_Text', 'Tracker_Artifact_ChangesetValue_TextTestVersion', array('getCodendi_HTMLPurifier'));

Mock::generate('Tracker_FormElement_Field_Text');

require_once('common/include/Codendi_HTMLPurifier.class.php');
Mock::generate('Codendi_HTMLPurifier');

class Tracker_Artifact_ChangesetValue_TextTest extends TuleapTestCase {
    
    function testTexts() {
        $field = aTextField()->withTracker(aTracker()->withProject(mock('Project'))->build())->build();
        $text = new Tracker_Artifact_ChangesetValue_Text(111, $field, false, 'Problems during installation', 'text');
        $this->assertEqual($text->getText(), 'Problems during installation');
        $this->assertEqual($text->getSoapValue(), array('value' => 'Problems during installation'));
        $this->assertEqual($text->getValue(), 'Problems during installation');
    }
    
    function testNoDiff() {
        $field = new MockTracker_FormElement_Field_Text();
        $text_1 = new Tracker_Artifact_ChangesetValue_Text(111, $field, false, 'Problems during installation', 'text');
        $text_2 = new Tracker_Artifact_ChangesetValue_Text(111, $field, false, 'Problems during installation', 'text');
        $this->assertFalse($text_1->diff($text_2));
        $this->assertFalse($text_2->diff($text_1));
    }
    
    function testDiff() {
        $field = new MockTracker_FormElement_Field_Text();
        $text_1 = new Tracker_Artifact_ChangesetValue_Text(111, $field, false, 'Problems during <ins> installation', 'text');
        $text_2 = new Tracker_Artifact_ChangesetValue_Text(111, $field, false, 'FullTextSearch does not work on Wiki pages', 'text');
        $this->assertEqual($text_1->diff($text_2), '<div class="diff">'.
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
        $this->assertEqual($text_2->diff($text_1), '<div class="diff">'.
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

?>