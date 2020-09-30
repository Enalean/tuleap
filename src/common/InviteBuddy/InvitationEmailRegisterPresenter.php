<?php
/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

use ForgeConfig;

class InvitationEmailRegisterPresenter
{
    /**
     * @var bool
     */
    public $has_custom_message;
    /**
     * @var string
     */
    public $register_url;
    /**
     * @var string
     */
    public $custom_message;
    /**
     * @var string
     */
    public $current_user_real_name;
    /**
     * @var string
     */
    public $instance_name;
    /**
     * @var string
     */
    public $recipient_name;

    public function __construct(\PFUser $current_user, string $register_url, ?string $custom_message)
    {
        $this->register_url       = $register_url;
        $this->custom_message     = (string) $custom_message;
        $this->has_custom_message = $custom_message && trim($custom_message) !== "";

        $this->current_user_real_name = (string) $current_user->getRealName();
        $this->instance_name          = (string) ForgeConfig::get('sys_name');
    }
}
