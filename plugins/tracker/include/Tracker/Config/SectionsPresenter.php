<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
    public string $artifact_link_active     = '';
    public string $artifact_deletion_active = '';
    public string $email_gateway_active     = '';
    public string $report_active            = '';
    public string $emailgateway_url;
    public string $types_url;
    public string $report_config_url;
    public string $artifacts_deletion_url;

    public function __construct()
    {
        $this->emailgateway_url  = TRACKER_BASE_URL . '/config.php?' . http_build_query([
            'action'   => 'emailgateway',
        ]);
        $this->types_url         = TRACKER_BASE_URL . '/config.php?' . http_build_query([
            'action'   => 'artifact-links',
        ]);
        $this->report_config_url = TRACKER_BASE_URL . '/config.php?' . http_build_query([
            'action' => 'report-config',
        ]);

        $this->artifacts_deletion_url = TRACKER_BASE_URL . '/config.php?' . http_build_query([
            'action' => 'artifacts-deletion',
        ]);
    }
}
