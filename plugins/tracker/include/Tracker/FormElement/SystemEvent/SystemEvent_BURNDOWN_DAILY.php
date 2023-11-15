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

namespace Tuleap\Tracker\FormElement\SystemEvent;

use Psr\Log\LoggerInterface;
use SystemEvent;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\FormElement\BurndownCacheDateRetriever;
use Tuleap\Tracker\FormElement\Field\Burndown\BurndownFieldDao;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDaoCache;
use Tuleap\Tracker\FormElement\FieldCalculator;

class SystemEvent_BURNDOWN_DAILY extends SystemEvent //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const NAME = 'SystemEvent_BURNDOWN_DAILY';

    /**
     * @var BurndownFieldDao
     */
    private $burndown_dao;

    private LoggerInterface $logger;

    private FieldCalculator $field_calculator;

    private ComputedFieldDaoCache $cache_dao;

    /**
     * @var BurndownCacheDateRetriever
     */
    private $date_retriever;

    public function verbalizeParameters($with_link)
    {
        return '-';
    }

    public function injectDependencies(
        BurndownFieldDao $burndown_dao,
        FieldCalculator $field_calculator,
        ComputedFieldDaoCache $cache_dao,
        LoggerInterface $logger,
        BurndownCacheDateRetriever $date_retriever,
    ) {
        $this->burndown_dao     = $burndown_dao;
        $this->logger           = $logger;
        $this->field_calculator = $field_calculator;
        $this->cache_dao        = $cache_dao;
        $this->date_retriever   = $date_retriever;
    }

    public function process()
    {
        $this->cacheYesterdayValues();
        $this->done();

        return true;
    }

    public function cacheYesterdayValues()
    {
        $yesterday = $this->date_retriever->getYesterday();
        if (! DatePeriodWithoutWeekEnd::isNotWeekendDay($yesterday)) {
            return;
        }

        foreach ($this->burndown_dao->getArtifactsWithBurndown() as $burndown) {
            if (empty($burndown['duration'])) {
                $burndown_period = DatePeriodWithoutWeekEnd::buildFromEndDate(
                    $burndown['start_date'],
                    $burndown['end_date'],
                    $this->logger
                );
            } else {
                $burndown_period = DatePeriodWithoutWeekEnd::buildFromDuration(
                    $burndown['start_date'],
                    $burndown['duration']
                );
            }

            if ($burndown_period->getEndDate() >= $yesterday) {
                $this->logger->debug(
                    "Calculating burndown for artifact #" . $burndown['id'] . ' at ' . (date('Y-m-d H:i:s', $yesterday) ?: '')
                );

                $value = $this->field_calculator->calculate(
                    [$burndown['id']],
                    $yesterday,
                    true,
                    'remaining_effort',
                    $burndown['remaining_effort_field_id']
                );

                $this->logger->debug("Caching value $value for artifact #" . $burndown['id']);
                $this->cache_dao->saveCachedFieldValueAtTimestamp(
                    $burndown['id'],
                    $burndown['remaining_effort_field_id'],
                    $yesterday,
                    $value
                );

                $this->logger->debug("End calculs for artifact #" . $burndown['id']);
            }
        }
    }
}
