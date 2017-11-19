<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectUGroup;

class BindingAdditionalModalPresenter
{
    /**
     * @var string
     */
    public $purified_html_button;
    /**
     * @var string
     */
    public $purified_html_modal_content;

    /**
     * @param $purified_html_button string A purified html string containing a button meant to trigger a modal.
     * @param $purified_html_modal_content string A purified html string containing a modal.
     */
    public function __construct($purified_html_button, $purified_html_modal_content)
    {
        $this->purified_html_button        = $purified_html_button;
        $this->purified_html_modal_content = $purified_html_modal_content;
    }
}
