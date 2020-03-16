<?php
/**
 * Copyright (c) Enalean, 2012-2017. All Rights Reserved.
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

use Tuleap\Templating\Mustache\MustacheEngine;
use Tuleap\Templating\TemplateCacheInterface;

/**
 * Adapts the Mustache template engine to the expected Tuleap interface.
 */
class MustacheRenderer extends TemplateRenderer
{
    /**
     * @var MustacheEngine
     */
    private $template_engine;

    public function __construct(TemplateCacheInterface $template_cache, $plugin_templates_dir)
    {
        $templates_directories = (array) $plugin_templates_dir;

        $common_templates_dir = ForgeConfig::get('codendi_dir') . '/src/templates/common/';
        if (is_dir($common_templates_dir)) {
            $templates_directories[] = $common_templates_dir;
        }

        $template_loader = new Mustache_Loader_CascadingLoader();
        foreach ($templates_directories as $templates_directory) {
            $template_loader->addLoader(new Mustache_Loader_ProductionFilesystemLoader($templates_directory));
        }

        $this->template_engine = $this->getEngine($template_loader, $template_cache);
    }

    /**
     * @return MustacheEngine
     */
    protected function getEngine(\Mustache_Loader $loader, TemplateCacheInterface $template_cache)
    {
        return new MustacheEngine($loader, $template_cache);
    }

    /**
     * @see TemplateEngine
     * @return string
     */
    public function renderToString($template_name, $presenter)
    {
        return $this->template_engine->render($template_name, $presenter);
    }
}
