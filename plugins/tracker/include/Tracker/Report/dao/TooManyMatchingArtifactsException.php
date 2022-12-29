<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\dao;

use Tracker_Exception;

final class TooManyMatchingArtifactsException extends Tracker_Exception
{
    public function __construct(int $tracker_id, int $nb_matching_artifacts, int $limit)
    {
        parent::__construct(sprintf(dgettext('tuleap-tracker', 'Tracker %d has too many artifacts (%d) while limit was set to %d. Please refine your query'), $tracker_id, $nb_matching_artifacts, $limit));
    }
}
