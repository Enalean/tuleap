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

namespace Tuleap\Tracker\Test\Stub\FormElement;

use Tuleap\Tracker\FormElement\ChartConfigurationWarningCollection;
use Tuleap\Tracker\FormElement\ChartFieldUsage;
use Tuleap\Tracker\FormElement\FetchChartConfigurationWarnings;
use Tuleap\Tracker\FormElement\Field\TrackerField;

final readonly class FetchChartConfigurationWarningsStub implements FetchChartConfigurationWarnings
{
    private function __construct(private ChartConfigurationWarningCollection $warnings_collection)
    {
    }

    public static function withWarnings(ChartConfigurationWarningCollection $warnings_collection): self
    {
        return new self($warnings_collection);
    }

    public static function withoutWarnings(): self
    {
        return new self(new ChartConfigurationWarningCollection());
    }

    #[\Override]
    public function fetchWarnings(TrackerField $field, ChartFieldUsage $usage): ChartConfigurationWarningCollection
    {
        return $this->warnings_collection;
    }
}
