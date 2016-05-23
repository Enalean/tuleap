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

namespace Tuleap\HudsonGit;

use Tuleap\Git\Webhook\CreateWebhookButtonPresenter;

class GitWebhooksSettingsCreateJenkinsButtonPresenter extends CreateWebhookButtonPresenter
{
    public function __construct($has_already_a_jenkins)
    {
        parent::__construct();
        $this->label                 = $GLOBALS['Language']->getText('plugin_hudson_git', 'add_jenkins_hook');
        $this->has_reached_the_limit = $has_already_a_jenkins;
        $this->only_one              = $GLOBALS['Language']->getText('plugin_hudson_git', 'only_one');
        $this->target_modal          = 'modal-create-jenkins';
    }
}
