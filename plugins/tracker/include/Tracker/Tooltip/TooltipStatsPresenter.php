<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tooltip;

use Tuleap\Date\DateHelper;

/**
 * @psalm-immutable
 */
class TooltipStatsPresenter
{
    /**
     * @var int
     */
    public $tracker_id;

    /**
     * @var bool
     */
    public $has_semantic_status;

    /**
     * @var bool
     */
    public $can_display_nb_artifacts;

    /**
     * @var int
     */
    public $nb_open_artifacts;

    /**
     * @var int
     */
    public $total_nb_artifacts;

    /**
     * @var bool
     */
    public $has_last_creation_and_update_dates;

    /**
     * a <tlp-relative-date> component as a string
     * @var ?string
     */
    public $tlp_relative_date_last_update_purified = null;

    /**
     * a <tlp-relative-date> component as a string
     * @var ?string
     */
    public $tlp_relative_date_last_creation_purified = null;

    public function __construct(
        int $tracker_id,
        bool $has_semantic_status,
        TrackerStats $tooltip_stats,
        \PFUser $current_user,
    ) {
        $this->tracker_id               = $tracker_id;
        $this->has_semantic_status      = $has_semantic_status;
        $this->can_display_nb_artifacts = $has_semantic_status && ($tooltip_stats->getNbTotalArtifacts() > 0);
        $this->nb_open_artifacts        = $tooltip_stats->getNbOpenArtifacts();
        $this->total_nb_artifacts       = $tooltip_stats->getNbTotalArtifacts();

        $last_update_date                         = $tooltip_stats->getLastArtifactUpdateDate();
        $last_creation_date                       = $tooltip_stats->getLastArtifactCreationDate();
        $this->has_last_creation_and_update_dates = false;

        if ($last_update_date !== null && $last_creation_date !== null) {
            $this->tlp_relative_date_last_update_purified   = DateHelper::relativeDateInlineContext($last_update_date, $current_user);
            $this->tlp_relative_date_last_creation_purified = DateHelper::relativeDateInlineContext($last_creation_date, $current_user);

            $this->has_last_creation_and_update_dates = true;
        }
    }
}
