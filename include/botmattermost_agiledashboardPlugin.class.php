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

use Tuleap\BotMattermostAgileDashboard\Plugin\PluginInfo;

require_once 'autoload.php';
require_once 'constants.php';

class botmattermost_agiledashboardPlugin extends Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        if (defined('AGILEDASHBOARD_BASE_URL')) {
            $this->addHook(AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ADMIN);
        }
    }

    public function getDependencies()
    {
        return array('agiledashboard', 'botmattermost');
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function agiledashboard_event_additional_panes_admin(array $params)
    {
        $render = $this->getRenderToString();
        $params['additional_panes']['notificationMattermost'] = array (
            'title'     => 'Notification',
            'output'    => $render,
        );
    }

    private function getRenderToString()
    {
        $render = TemplateRendererFactory::build()->getRenderer(PLUGIN_BOT_MATTERMOST_AGILE_DASHBOARD_BASE_DIR.'/template');
        return $render->renderToString('index', array('content' => 'This content is not available yet.'));
    }

    public function process()
    {
    }
}
