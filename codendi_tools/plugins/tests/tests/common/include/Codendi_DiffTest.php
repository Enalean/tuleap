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

require_once('common/include/Codendi_Diff.class.php');

class Codendi_DiffTest extends UnitTestCase {
    public function setUp() {
        $this->a = array('Line 1', 'Line 2');
        $this->b = array('Line 1', 'Line 2');
        $this->c = array('Line 1', 'Line 2 modified');
        $this->d = array('Line 1');
        $this->e = array();
    }
    public function tearDown() {
        unset($this->a);
        unset($this->b);
        unset($this->c);
        unset($this->d);
        unset($this->e);
    }
    public function testHtmlUnifiedDiffFormatter_NoChanges() {
        
        $formatter = new Codendi_HtmlUnifiedDiffFormatter();
        
        $this->assertEqual($formatter->format(new Codendi_Diff($this->a, $this->b)), 
                           '');
    }
    
    public function testHtmlUnifiedDiffFormatter_LineModified() {
        
        $formatter = new Codendi_HtmlUnifiedDiffFormatter();
        $this->assertEqual($formatter->format(new Codendi_Diff($this->b, $this->c)),
                                '<div class="block">'.
                                    '<tt>@@ -1,2 +1,2 @@'."\n".
                                    '</tt>'.
                                    '<div class="difftext">'.
                                        '<div class="context">'.
                                            '<tt class="prefix">&nbsp;</tt>Line 1&nbsp;'.
                                        '</div>'.
                                    '</div>'.
                                    '<div class="difftext">'.
                                        '<div class="original">'.
                                            '<tt class="prefix">-</tt>Line 2&nbsp;'.
                                        '</div>'.
                                    '</div>'.
                                    '<div class="difftext">'.
                                        '<div class="final">'.
                                            '<tt class="prefix">+</tt>Line 2 <ins>modified</ins>&nbsp;'.
                                        '</div>'.
                                    '</div>'.
                                '</div>');
    }
    
    public function testHtmlUnifiedDiffFormatter_LineDeleted() {
        
        $formatter = new Codendi_HtmlUnifiedDiffFormatter();
        $this->assertEqual($formatter->format(new Codendi_Diff($this->b, $this->d)),
                                '<div class="block">'.
                                    '<tt>@@ -1,2 +1 @@'."\n".
                                    '</tt>'.
                                    '<div class="difftext">'.
                                        '<div class="context">'.
                                            '<tt class="prefix">&nbsp;</tt>Line 1&nbsp;'.
                                        '</div>'.
                                    '</div>'.
                                    '<div class="difftext">'.
                                        '<div class="deleted">'.
                                            '<tt class="prefix">-</tt><del>Line 2</del>&nbsp;'.
                                        '</div>'.
                                    '</div>'.
                                '</div>');
    }
    
    public function testHtmlUnifiedDiffFormatter_LineAdded() {
        
        $formatter = new Codendi_HtmlUnifiedDiffFormatter();
        $this->assertEqual($formatter->format(new Codendi_Diff($this->e, $this->d)),
                                '<div class="block">'.
                                    '<tt>@@ -1,0 +1 @@'."\n".
                                    '</tt>'.
                                    '<div class="difftext">'.
                                        '<div class="added">'.
                                            '<tt class="prefix">+</tt><ins>Line 1</ins>&nbsp;'.
                                        '</div>'.
                                    '</div>'.
                                '</div>');
    }
}
?>
