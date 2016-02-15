<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once 'autoload.php';
require_once 'constants.php';

class templatePlugin extends Plugin {

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
    }

    /**
     * @return Tuleap\Template\Plugin\PluginInfo
     */
    public function getPluginInfo() {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new Tuleap\Template\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname() {
        return 'plugin_template';
    }

    public function process() {
        $renderer = TemplateRendererFactory::build()->getRenderer(TEMPLATE_BASE_DIR.'/template');
        $renderer->renderToPage('index', array('world' => 'World'));
    }
}
