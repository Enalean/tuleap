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

require_once 'mustache/MustacheRenderer.class.php';
require_once 'mustache/MustacheDebugRenderer.class.php';

/**
 * Handles TemplateRenderer's instanciation. 
 */
class TemplateRendererFactory {
    
    /**
     * Creates a new factory instance.
     * 
     * Mostly used at places where renderers where instanciated manually, and
     * where injecting a factory needed a lot of refactoring.
     * 
     * @return \TemplateRendererFactory 
     */
    public static function build() {
        return new TemplateRendererFactory();
    }
    
    /**
     * Returns a new TemplateRenderer according to Config.
     * 
     * For now, it will mostly switch between MustacheRenderer and
     * MustacheDebugRenderer (when DEBUG_MODE is enabled).
     * 
     * @param string $plugin_templates_dir
     * @return TemplateRenderer
     */
    public function getRenderer($plugin_templates_dir) {
        $renderer_class = $this->getRendererClassName();
        return new $renderer_class($plugin_templates_dir);
    }

    /**
     * @return string
     */
    private function getRendererClassName() {
        return ForgeConfig::get('DEBUG_MODE') ? 'MustacheDebugRenderer' : 'MustacheRenderer';
    }
}
?>
