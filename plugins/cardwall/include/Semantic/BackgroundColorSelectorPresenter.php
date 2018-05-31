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

namespace Tuleap\Cardwall\Semantic;

use CSRFSynchronizerToken;
use Tracker_FormElement;

class BackgroundColorSelectorPresenter
{
    /**
     * @var bool
     */
    public $has_at_least_one_field_selectable_for_color;

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var string
     */
    public $form_url;
    /**
     * @var Tracker_FormElement[]
     */
    public $form_elements;

    public function __construct(array $form_elements, CSRFSynchronizerToken $csrf_token, $form_url)
    {
        $this->has_at_least_one_field_selectable_for_color = count($form_elements) > 0;
        $this->csrf_token                                  = $csrf_token;
        $this->form_url                                    = $form_url;
        $this->form_elements                               = $form_elements;
    }
}
