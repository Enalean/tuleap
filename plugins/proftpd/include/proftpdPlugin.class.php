<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class proftpdPlugin extends Plugin {

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'ProftpdPluginInfo')) {
            $this->pluginInfo = new ProftpdPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks() {
        $this->addHook('logs_daily');
        return parent::getHooksAndCallbacks();
    }

    public function logs_daily($params) {
        $dao = new Tuleap\ProFTPd\Xferlog\Dao();

        $params['logs'][] = array(
            'sql'   => $dao->getLogQuery($params['group_id'], $params['logs_cond']),
            'field' => $GLOBALS['Language']->getText('plugin_proftpd', 'log_filepath'),
            'title' => $GLOBALS['Language']->getText('plugin_proftpd', 'log_title')
        );
    }
}
