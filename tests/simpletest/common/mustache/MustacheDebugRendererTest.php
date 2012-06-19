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

require_once 'common/mustache/MustacheDebugRenderer.class.php';

class MustacheDebugRenderer_TestCase extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->templates_dir = dirname(__FILE__).'/templates';
        $this->renderer      = new MustacheDebugRenderer($this->templates_dir);
    }
    
    protected function failMissingException($exception_class_name = 'Exception') {
        $this->fail("Expected $exception_class_name, but nothing raised.");
    }
    
    protected function whatever() {
        return new stdClass();
    }
}

class MustacheDebugRenderer_ValidTemplateTest extends MustacheDebugRenderer_TestCase {
    
    public function itBehavesLikeMustacheRenderer() {
        $mustache_renderer = new MustacheRenderer($this->templates_dir);
        $presenter         = $this->whatever();
        $mustache_output   = $mustache_renderer->render('valid-template', $presenter, true);
        $debug_output      = $this->renderer->render('valid-template', $presenter, true);
        
        $this->assertEqual($debug_output, $mustache_output);
    }
}

class MustacheDebugRenderer_InvalidTemplateTest extends MustacheDebugRenderer_TestCase {
    
    public function itIncludesTheNameOfTheInvalidTemplateInTheErrorMessage() {
        try {
            $this->renderer->render('invalid-template', $this->whatever());
            $this->failMissingException();
        } catch(Exception $exception) {
            $this->assertPattern('/invalid-template/', $exception->getMessage());
        }
    }
}

class MustacheDebug_InvalidPartialRenderingTest extends TuleapTestCase {
    public function itIncludesTheNameOfTheInvalidPartialAtTheTopOfTheErrorMessage() {
        //
    }
    
    public function itIncludesTheNameOfTheRootPartialAtTheBottomOfTheErrorMessage() {
        //
    }
    
    public function itIncludesTheNameOfTheParentPartialsInTheErrorMessage() {
        //
    }
    
    public function itDoesNotIncludeTheNameOfValidChildTemplates() {
        //
    }
}
?>
