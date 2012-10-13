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

require_once 'common/templating/TemplateRenderer.class.php';
require_once 'vendor/Mustache.php';
require_once 'vendor/MustacheLoader.php';

/**
 * Adapts the Mustache template engine to the expected Tuleap interface. 
 */
class MustacheRenderer extends TemplateRenderer {

    /**
     * @var Mustache
     */
    protected $template_engine;
    
    /**
     * @var MustacheLoader
     */
    protected $template_loader;
    
    /**
     * @var array
     */
    protected $options = array('throws_exceptions' => array(
        MustacheException::UNKNOWN_VARIABLE         => true,
        MustacheException::UNCLOSED_SECTION         => true,
        MustacheException::UNEXPECTED_CLOSE_SECTION => true,
        MustacheException::UNKNOWN_PARTIAL          => true,
        MustacheException::UNKNOWN_PRAGMA           => true,
    ));
    
    public function __construct($plugin_templates_dir) {
        parent::__construct($plugin_templates_dir);
        
        $this->template_engine = $this->buildTemplateEngine();
        $this->template_loader = new MustacheLoader($this->plugin_templates_dir);
    }
    
    /**
     * Builds a new Mustache instance.
     * 
     * @return \Mustache 
     */
    protected function buildTemplateEngine() {
        return new Mustache(null, null, null, $this->options);
    }
    
    /**
     * @see TemplateEngine
     * @return string
     */
    public function renderToString($template_name, $presenter) {
       return $this->template_engine->render($this->template_loader[$template_name], $presenter, $this->template_loader);
    }
}

?>
