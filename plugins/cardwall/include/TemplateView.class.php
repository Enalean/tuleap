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

require_once 'View.class.php';

/**
 * A view that uses a template file to generate its output.
 */
abstract class TemplateView implements View {
    
    /**
     * @var String
     */
    private $template_name;
    
    /**
     * @var String
     */
    private $templates_dir;
    
    /**
     * @var View
     */
    protected $parent_view;
    
    public function __construct($template_name, $parent_view = null) {
        $this->template_name = $template_name;
        $this->templates_dir = dirname(__FILE__).'/../templates';
        $this->parent_view   = $parent_view;
    }
    
    /**
     * Computes the template absolute path, according to the $template_name
     * attribute.
     * 
     * @return string
     */
    protected function getTemplatePath() {
        return "$this->templates_dir/$this->template_name.php";
    }
    
    /**
     * @see View
     */
    public function renderToPage() {
        include $this->getTemplatePath();
    }
    
    /**
     * @see View
     */
    public function renderToString() {
        ob_start();
        $this->renderToPage();
        return ob_get_clean();
    }
    
    /**
     * Renders a child view.
     * 
     * @param string $view_class The full name of the view class.
     * @param mixed  $param      Some parameter to pass to the view constructor
     * 
     * @return string
     * 
     * @todo Make it more consistent with renderToPage/renderToString.
     */
    public function render($view_class, $param) {
        $view = new $view_class($param, $this);
        return $view->renderToString();
    }
}
?>
