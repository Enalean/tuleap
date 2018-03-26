<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Velocity;

class VelocityChartPresenter
{
    /**
     * @var array
     */
    public $backlog_items_representation;
    /**
     * @var bool
     */
    public $has_backlog_items_with_computed_velocity;
    /**
     * @var bool
     */
    public $has_invalid_artifacts;
    /**
     * @var int
     */
    public $nb_invalid_artifacts;
    /**
     * @var array
     */
    public $invalid_artifacts;

    public function __construct(array $backlog_items_representation, array $invalid_artifacts)
    {
        $this->backlog_items_representation             = json_encode($backlog_items_representation);
        $this->has_backlog_items_with_computed_velocity = count($backlog_items_representation) > 0;
        $this->invalid_artifacts                        = $invalid_artifacts;
        $this->has_invalid_artifacts                    = count($invalid_artifacts) > 0;
        $this->nb_invalid_artifacts                     = count($invalid_artifacts);
    }
}
