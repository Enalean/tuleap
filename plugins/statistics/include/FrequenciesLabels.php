<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Statistics;

use Tuleap\Event\Dispatchable;

final class FrequenciesLabels implements Dispatchable
{
    /**
     * @var array<string, string>
     */
    private array $labels = [];
    public function __construct()
    {
        $this->labels = [
            'session'   => dgettext('tuleap-statistics', 'Sessions'),
            'user'      => dgettext('tuleap-statistics', 'Users'),
            'filedl'    => dgettext('tuleap-statistics', 'Files downloaded'),
            'file'      => dgettext('tuleap-statistics', 'Files released'),
            'groups'    => dgettext('tuleap-statistics', 'Project created'),
            'wikidl'    => dgettext('tuleap-statistics', 'Wiki pages viewed'),
            'oartifact' => dgettext('tuleap-statistics', 'Opened Artifacts (V3)'),
            'cartifact' => dgettext('tuleap-statistics', 'Closed (or wished end date) Artifacts (V3)'),
        ];
    }

    public function addLabel(string $key, string $label): void
    {
        $this->labels[$key] = $label;
    }

    public function getLabels(): array
    {
        return $this->labels;
    }
}
