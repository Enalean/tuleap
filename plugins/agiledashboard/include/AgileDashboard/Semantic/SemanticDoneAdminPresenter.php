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

use CSRFSynchronizerToken;
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

    /**
     * @var bool
     */
    public $has_closed_values_selectable;

    /**
     * @var string
     */
    public $form_url;

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        Tracker $tracker,
        array $closed_values,
        $form_url,
        $go_back_url,
        ?Tracker_FormElement_Field $semantic_status_field = null
    ) {
        $this->semantic_status_is_defined = (bool) ($semantic_status_field !== null);
        $this->tracker_id                 = $tracker->getId();
        $this->closed_values              = $closed_values;

        $this->has_closed_values_selectable = count($this->closed_values) > 0;

        $this->go_back_url = $go_back_url;
        $this->form_url    = $form_url;
        $this->csrf_token  = $csrf;
    }
}
