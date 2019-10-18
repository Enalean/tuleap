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

namespace Tuleap\SVN\Statistic;

use Statistics_Services_UsageFormatter;

class ServiceUsageCollector
{
    /**
     * @var ServiceUsageDao
     */
    private $dao;

    public function __construct(ServiceUsageDao $dao)
    {
        $this->dao = $dao;
    }

    public function collect(Statistics_Services_UsageFormatter $formatter, $start_date, $end_date)
    {
        $formatter->buildDatas(
            $this->dao->searchWriteOperationsByProject($start_date, $end_date),
            dgettext('tuleap-svn', 'SVN with multiple repositories activities')
        );
    }
}
