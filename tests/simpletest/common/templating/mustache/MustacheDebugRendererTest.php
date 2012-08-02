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

require_once 'common/templating/mustache/MustacheDebugRenderer.class.php';

class MustacheDebugRenderer_DummyPresenter {
    function __toString() {
        return '';
    }
}

class MustacheDebugRenderer_TestCase extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->templates_dir = dirname(__FILE__).'/templates';
        $this->renderer      = new MustacheDebugRenderer($this->templates_dir);
        $this->presenter     = new MustacheDebugRenderer_DummyPresenter();
    }
    
    protected function failMissingException($exception_class_name = 'Exception') {
        $this->fail("Expected $exception_class_name, but nothing raised.");
    }
}

class MustacheDebugRenderer_ValidTemplateTest extends MustacheDebugRenderer_TestCase {
    
    public function itBehavesLikeMustacheRenderer() {
        $mustache_renderer = new MustacheRenderer($this->templates_dir);
        $mustache_output   = $mustache_renderer->renderToString('valid-template', $this->presenter);
        $debug_output      = $this->renderer->renderToString('valid-template', $this->presenter);
        
        $this->assertEqual($debug_output, $mustache_output);
    }
}

class MustacheDebugRenderer_InvalidTemplateTest extends MustacheDebugRenderer_TestCase {
    
    public function itIncludesTheNameOfTheInvalidTemplateInTheErrorMessage() {
        try {
            $this->renderer->renderToString('invalid-template', $this->presenter);
            $this->failMissingException();
        } catch(Exception $exception) {
            $this->assertPattern('/ invalid-template.mustache/', $exception->getMessage());
        }
    }
}

class MustacheDebug_InvalidPartialRenderingTest extends MustacheDebugRenderer_TestCase {
    public function setUp() {
        parent::setUp();
        try {
            $this->renderer->renderToString('valid-template-with-invalid-partial', $this->presenter);
            $this->failMissingException();
        } catch(Exception $exception) {
            $this->error_message = $exception->getMessage();
        }
    }
    
    public function itIncludesTheNameOfTheInvalidPartialInTheErrorMessage() {
        $this->assertPattern('/ invalid-template.mustache/', $this->error_message);
    }
    
    public function itIncludesTheNameOfTheRootPartialInTheErrorMessage() {
        $this->assertPattern('/ valid-template-with-invalid-partial.mustache/', $this->error_message);
    }
    
    public function itDoesNotIncludeTheNameOfValidChildTemplatesInTheErrorMessage() {
        $this->assertNoPattern('/ valid-template.mustache/', $this->error_message);
    }
}
?>
