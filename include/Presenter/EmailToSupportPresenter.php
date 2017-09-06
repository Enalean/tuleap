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

namespace Tuleap\MyTuleapContactSupport\Presenter;

class EmailToSupportPresenter
{
    /** @var string */
    public $mytuleap_name;
    /** @var string */
    public $current_user_real_name;
    /** @var string */
    public $message_title;
    /** @var string */
    public $message_content;
    /** @var string */
    public $more_info;

    public function __construct($mytuleap_name, $current_user_real_name, $message_title, $message_content)
    {
        $this->mytuleap_name          = $mytuleap_name;
        $this->current_user_real_name = $current_user_real_name;
        $this->message_title          = $message_title;
        $this->message_content        = $message_content;
        $this->more_info              = dgettext('tuleap-mytuleap_contact_support', 'More information about Tuleap');
    }
}
