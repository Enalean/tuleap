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

namespace Tuleap\Tracker\FormElement;

final class ChartConfigurationWarningCollection
{
    /**
     * @var list<ChartConfigurationWarningInterface>
     */
    public array $warnings = [];

    public function addWarning(ChartConfigurationWarningInterface $warning): void
    {
        $this->warnings[] = $warning;
    }

    public function getAsHTML(): string
    {
        if (count($this->warnings) === 0) {
            return '';
        }

        $html_warnings = array_map(static fn (ChartConfigurationWarningInterface $warning) => $warning->getAsHTML(), $this->warnings);

        return '<ul class="feedback_warning">' . implode('', $html_warnings) . '</ul>';
    }
}
