<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

use Tuleap\Tracker\Tracker;

class RequiredTrackerPresenter
{
    /** @var string */
    public $name;
    /** @var MissingRequiredSemanticPresenter[] */
    public $misconfigured_semantics;
    /** @var int */
    public $nb_semantic_misconfigured;
    /** @var bool */
    public $has_misconfigured_semantics;
    /** @var string */
    public $tracker_url;

    public function build(Tracker $tracker, array $misconfigured_semantics)
    {
        $this->name                        = $tracker->getName();
        $this->tracker_url                 = TRACKER_BASE_URL . '?' . http_build_query(
            [
                'tracker' => $tracker->getId(),
                'func'    => 'admin',
            ]
        );
        $this->misconfigured_semantics     = $misconfigured_semantics;
        $this->nb_semantic_misconfigured   = count($misconfigured_semantics);
        $this->has_misconfigured_semantics = count($misconfigured_semantics) > 0;
    }
}
