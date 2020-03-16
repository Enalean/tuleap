<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Config;

class SectionsPresenter
{
    public $artifact_link_active = '';
    public $artifact_deletion_active = '';
    public $email_gateway_active = '';
    public $report_active = '';
    public $emailgateway;
    public $natures;
    public $report_config_panel;
    public $emailgateway_url;
    public $natures_url;
    public $report_config_url;

    public $artifacts_deletion_url;

    public function __construct()
    {
        $this->emailgateway        = $GLOBALS['Language']->getText('plugin_tracker_config', 'email_gateway');
        $this->natures             = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'title');
        $this->report_config_panel = $GLOBALS['Language']->getText('plugin_tracker_report_config', 'report_config_panel');

        $this->emailgateway_url = TRACKER_BASE_URL . '/config.php?' . http_build_query(array(
            'action'   => 'emailgateway'
        ));
        $this->natures_url = TRACKER_BASE_URL . '/config.php?' . http_build_query(array(
            'action'   => 'natures'
        ));
        $this->report_config_url = TRACKER_BASE_URL . '/config.php?' . http_build_query(array(
            'action' => 'report-config'
        ));

        $this->artifacts_deletion_url = TRACKER_BASE_URL . '/config.php?' . http_build_query([
            'action' => 'artifacts-deletion'
        ]);
    }
}
