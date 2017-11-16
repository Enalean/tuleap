<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use Tracker;
use Tracker_FormElement_Field;

class SemanticDoneAdminPresenter
{
    /**
     * @var bool
     */
    public $semantic_status_is_defined;

    /**
     * @var int
     */
    public $tracker_id;

    /**
     * @var array
     */
    public $closed_values;

    /**
     * @var string
     */
    public $go_back_url;

    public function __construct(
        Tracker $tracker,
        array $closed_values,
        Tracker_FormElement_Field $semantic_status_field = null
    ) {
        $this->semantic_status_is_defined = (boolean)($semantic_status_field !== null);
        $this->tracker_id                 = $tracker->getId();
        $this->closed_values              = $closed_values;

        $this->go_back_url = TRACKER_BASE_URL. '/?' . http_build_query(array(
            'tracker' => $this->tracker_id,
            'func'    => 'admin-semantic'
        ));
    }
}
