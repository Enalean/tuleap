<?php
/**
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Admin;

use Tuleap\Tracker\FormElement\BurndownChartFieldUsage;
use Tuleap\Tracker\FormElement\ChartConfigurationWarningInterface;
use Tuleap\Tracker\FormElement\FetchChartConfigurationWarnings;
use Tuleap\Tracker\FormElement\Field\RetrieveBurndownField;
use Tuleap\Tracker\Tracker;

readonly class FieldsConfigurationWarningsRetriever
{
    public function __construct(
        private RetrieveBurndownField $retrieve_burndown_field,
        private FetchChartConfigurationWarnings $fetch_chart_configuration_warnings,
    ) {
    }

    /**
     * @return array<int, ChartConfigurationWarningInterface[]>
     */
    public function retrieveWarnings(Tracker $tracker, \PFUser $user): array
    {
        $warnings       = [];
        $burndown_field = $this->retrieve_burndown_field->getABurndownField($user, $tracker);
        if ($burndown_field === null) {
            return $warnings;
        }

        $burndown_warnings = $this->fetch_chart_configuration_warnings->fetchWarnings($burndown_field, BurndownChartFieldUsage::build())->warnings;
        if (count($burndown_warnings) > 0) {
            $warnings[$burndown_field->getId()] = $burndown_warnings;
        }

        return $warnings;
    }
}
