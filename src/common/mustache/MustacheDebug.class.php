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

require_once 'vendor/Mustache.php';
require_once 'MustacheDebugException.class.php';

/**
 * An improved version of the Mustache template engine.
 * 
 * It knows the current stack of templates/partials, and provides better error
 * messages.
 */
class MustacheDebug extends Mustache {
    
    /**
     * @var array Names of the recursively rendered templates up to the current one.
     */
    private $template_names_stack = array();
    
    /**
     * Adds the name of the template to be rendered to the template names stack;
     * 
     * Call this method right before rendering the template, in order for its
     * name to appear in some possible error message.
     * 
     * @param string $template_name The name of the template to be rendered.
     */
    private function _pushTemplateName($template_name) {
        $this->template_names_stack[] = $template_name;
    }
    
    /**
     * Removes the last rendered template from the top of the template names stack.
     * 
     * Call this method when you're done with rendering a template or a partial.
     */
    private function _popTemplateName() {
        array_pop($this->template_names_stack);
    }
    
    /**
     * Builds a new MustacheDebugException from a MustacheException, with a
     * better error message (including template names).
     *
     * @param MustacheException $source_exception
     * @return \MustacheDebugException 
     */
    private function buildExceptionWithTemplateNamesInMessage(MustacheException $source_exception) {
        $message  = $source_exception->getMessage();
        $message .= '<br/>in ';
        $message .= implode('.mustache<br/>in ', array_reverse($this->template_names_stack));
        $message .= '.mustache<br/>';
        
        return new MustacheDebugException($message, $source_exception->getCode());
    }
    
    /**
     * Renders a template by name.
     * 
     * This method replaces Mustache::render().
     * Given the template name, it adds it to the template names stack to provide better error messages
     * ("render" can't do it, since it doesn't know the template name).
     * 
     * @param string         $template_name The name of the template to render.
     * @param mixed          $view          The template evaluation context
     * @param MustacheLoader $loader        A template loader (that will provide the template content given its name)
     * 
     * @throws MustacheDebugException
     */
    public function renderByName($template_name, $view, $loader) {
        $output = '';
        $this->_pushTemplateName($template_name);
        try {
            $output = $this->render($loader[$template_name], $view, $loader);
        } catch (MustacheException $exception) {
            throw $this->buildExceptionWithTemplateNamesInMessage($exception);
        }
        $this->_popTemplateName();
        return $output;
    }
    
    /**
     * @see Mustache
     */
    protected function _renderPartial($tag_name, $leading, $trailing) {
        $output = '';
        $this->_pushTemplateName($tag_name);
        try {
            $output = parent::_renderPartial($tag_name, $leading, $trailing);
        } catch (MustacheException $exception) {
            throw $this->buildExceptionWithTemplateNamesInMessage($exception);
        }
        $this->_popTemplateName();
        return $output;
    }
}

?>
