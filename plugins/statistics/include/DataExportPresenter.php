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

namespace Tuleap\Statistics;

class DataExportPresenter
{
    /**
     * @var AdminHeaderPresenter
     */
    public $header;
    /**
     * @var UsageProgressPresenter
     */
    public $usage_progress_presenter;
    /**
     * @var ServicesUsagePresenter
     */
    public $services_usage_presenter;
    /**
     * @var SCMStatisticsPresenter
     */
    public $scm_statistics_presenter;

    public function __construct(
        AdminHeaderPresenter $header,
        UsageProgressPresenter $usage_progress_presenter,
        ServicesUsagePresenter $services_usage_presenter,
        SCMStatisticsPresenter $scm_statistics_presenter
    ) {
        $this->header                   = $header;
        $this->usage_progress_presenter = $usage_progress_presenter;
        $this->services_usage_presenter = $services_usage_presenter;
        $this->scm_statistics_presenter = $scm_statistics_presenter;

        $this->services_usage_label = dgettext('tuleap-statistics', 'Service usage');
        $this->scm_statistics_label = dgettext('tuleap-statistics', 'SCM statistics');
        $this->usage_progress_label = dgettext('tuleap-statistics', 'Usage progress');
    }
}
