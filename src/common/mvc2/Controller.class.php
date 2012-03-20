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

require_once 'common/include/Codendi_Request.class.php';
require_once 'common/templating/TemplateRenderer.class.php';
require_once 'common/mustache/MustacheRenderer.class.php';

/**
 * Base class for controllers (differs from 'src/common/mvc/Controller.class.php').
 * 
 * Typical use:
 * 
 *     class PeopleController extends Controller {
 *         public function __construct(Codendi_Request $request, ...) {
 *             parent::__construct('myplugin', $request);
 *             
 *             // Assign controller-specific parameters
 *         }
 *         
 *         public function index() {
 *             // Render a list of persons
 *         }
 *         
 *         public function new_() {
 *             // Render a form to create a new person
 *         }
 *         
 *         public function create() {
 *             // Create a person and redirect to show (or index)
 *         }
 *         
 *         public function show() {
 *             // Display the person information
 *         }
 *         
 *         public function edit() {
 *             // Render a form to edit the person
 *         }
 *         
 *         public function update() {
 *             // Update the person and redirect to show (or index)
 *         }
 *         
 *         public function delete() {
 *             // Render a confirmation page before deletion
 *         }
 *         
 *         public function destroy() {
 *             // Destroy the person and redirect to the index
 *         }
 *     }
 * 
 * REMAINING ISSUES:
 * - Separate classes for collection and single ressources ?
 * - Renderer implementation coupling
 */
abstract class Controller {
    
    /**
     * @var Codendi_Request 
     */
    protected $request;
    
    /**
     * @var String
     */
    private $plugin_name;
    
    /**
     * @var TemplateRenderer
     */
    private $renderer;
    
    public function __construct($plugin_name, Codendi_Request $request) {
        $this->request     = $request;
        $this->plugin_name = $plugin_name;
        $this->renderer    = new MustacheRenderer($this->getTemplatesDir());
    }
    
    private function getTemplatesDir() {
        return dirname(__FILE__).'/../../../plugins/'.$this->plugin_name.'/templates';
    }
    
    protected function render($template_name, $presenter) {
        $this->renderer->render($template_name, $presenter);
    }
    
    protected function addFeedback($type, $message) {
        $GLOBALS['Response']->addFeedback('error', 'All fields are mandatory');
    }
    
    protected function redirect($query_parts) {
        $redirect = http_build_query($query_parts);
        $GLOBALS['Response']->redirect('/plugins/'.$this->plugin_name.'/?'.$redirect);
    }
}
?>
