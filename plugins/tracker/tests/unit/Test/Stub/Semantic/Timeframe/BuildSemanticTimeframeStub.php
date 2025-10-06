<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Semantic\Timeframe;

use Tuleap\Tracker\Semantic\Timeframe\BuildSemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeConfigInvalid;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\Tracker;

final class BuildSemanticTimeframeStub implements BuildSemanticTimeframe
{
    private function __construct(private readonly SemanticTimeframe $semantic_timeframe)
    {
    }

    public static function withTimeframeSemanticBasedOnEndDate(
        Tracker $tracker,
        \Tuleap\Tracker\FormElement\Field\Date\DateField $start_field,
        \Tuleap\Tracker\FormElement\Field\Date\DateField $end_field,
    ): self {
        return self::withTimeframeCalculator($tracker, new TimeframeWithEndDate($start_field, $end_field));
    }

    public static function withTimeframeSemanticBasedOnDuration(
        Tracker $tracker,
        \Tuleap\Tracker\FormElement\Field\Date\DateField $start_field,
        \Tuleap\Tracker\FormElement\Field\NumericField $duration_field,
    ): self {
        return self::withTimeframeCalculator($tracker, new TimeframeWithDuration($start_field, $duration_field));
    }

    public static function withTimeframeSemanticNotConfigured(Tracker $tracker): self
    {
        return self::withTimeframeCalculator($tracker, new TimeframeNotConfigured());
    }

    public static function withTimeframeSemanticConfigInvalid(Tracker $tracker): self
    {
        return self::withTimeframeCalculator($tracker, new TimeframeConfigInvalid());
    }

    public static function withTimeframeCalculator(Tracker $tracker, IComputeTimeframes $timeframe): self
    {
        return new self(
            new SemanticTimeframe(
                $tracker,
                $timeframe,
            )
        );
    }

    #[\Override]
    public function getSemantic(Tracker $tracker): SemanticTimeframe
    {
        return $this->semantic_timeframe;
    }
}
