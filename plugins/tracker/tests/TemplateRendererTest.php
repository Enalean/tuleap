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
    public $content = 'An open ALM solution.';
}

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
}

class MustacheRendererTest extends TemplateRendererTest {
    function setUp() {
        $this->renderer = new MustacheRenderer(dirname(__FILE__));
        parent::setUp();
    }
}

?>
