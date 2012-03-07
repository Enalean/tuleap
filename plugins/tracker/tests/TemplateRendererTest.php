<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../include/MustacheRenderer.class.php');

class TestPresenter {
    public function title() {
        return 'Tuleap';
    }
    public function getTreeItems() {
        return array(
            array('name' => 'T1', 'children' => array(
                array('name' => 'T2', 'children' => array(
                    array('name' => 'T3', 'children' => array()),
                )),
                array('name' => 'T4', 'children' => array())
            ))
        );
    }
    public $content = 'An open ALM solution.';
}

/**
 * extend this class for any new template engines
 */
abstract class TemplateRendererTest extends UnitTestCase {
    function setUp() {
        $this->presenter = new TestPresenter();
        ob_start();
        $this->renderer->render('test', $this->presenter);
        $this->output = ob_get_clean();
    }
    
    function assertOutputContains($content) {
        $this->assertPattern("/".$content."/", $this->output);
    }
    
    function testSimpleValue() {
        $this->assertOutputContains($this->presenter->title());
    }
    
    function testFunction() {
        $this->assertOutputContains($this->presenter->content);
    }
    
    function testCanBuildANestedList() {
        //dont know how to assert the nestedness in a proper way, so lets just assert that it contains all elements
        //atleast we know that the recursion is working
        $this->assertPattern("/T1.*T2.*T3.*T4/", $this->strip($this->output)); 
    }

    public function strip($string) {
        return "\n".preg_replace("/[ \t\n]+/", ' ', $string)."\n";
    }
}

/**
 * Replace this class or add a class for every template engine 
 */
class MustacheRendererTest extends TemplateRendererTest {
    function setUp() {
        $this->renderer = new MustacheRenderer(dirname(__FILE__).'/templates');
        parent::setUp();
    }
}

?>
