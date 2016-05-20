<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Git\Webhook;

class CreateWebhookButtonPresenter
{
    public $label;
    public $has_reached_the_limit;
    public $target_modal;
    public $only_one;

    public function __construct()
    {
        $this->label                 = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_create');
        $this->has_reached_the_limit = false;
        $this->only_one              = '';
        $this->target_modal          = 'modal-create-webhook';
    }
}
