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

/**
 * In order to use a template engine with src/common/mvc2/Controller.class.php
 * subclasses, one need to extend the following class.
 *
 * For now, at least one renderer instance per plugin is needed, since
 * each plugin has its own templates directory.
 *
 * REMAINING ISSUES:
 *
 * - This pseudo Presenter pattern could evolve to a View one, where View classes
 *   would contain the rendering logic, so Controllers would only need to
 *   instanciate and render the View, instead of ensuring communication between
 *   Template and Presenter.
 *
 * - There is no simple way to share templates between plugins for now.
 *
 * - The expected Presenter object may differ with some future engines.
 */
abstract class TemplateRenderer
{

    /**
     * Renders a template, given its name and a presenter (providing the
     * template logic), and returns the result as a string.
     *
     * @param String $template_name The basename of the template file, relative
     *                              to the plugin templates directory (e.g.
     *                              'my-template' would match
     *                              'plugins/myplugin/templates/my-template.foo').
     * @param mixed  $presenter     Any PHP object usable as a context for the
     *                              template engine (e.g. its method could be
     *                              called from the template). It can even be
     *                              an associative array.
     *
     * @return String The generated output.
     */
    abstract public function renderToString($template_name, $presenter);

    /**
     * Same as renderToString, but outputs to the page instead of returning a
     * string.
     */
    public function renderToPage($template_name, $presenter)
    {
        echo $this->renderToString($template_name, $presenter);
    }
}
