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

namespace Tuleap\Git\Repository\Settings;

use HTTPRequest;
use Tuleap\Git\RouterLink;

class WebhookRouter extends RouterLink
{
    /**
     * @var WebhookAddController
     */
    private $add_controller;
    /**
     * @var WebhookEditController
     */
    private $edit_controller;
    /**
     * @var WebhookDeleteController
     */
    private $delete_controller;

    public function __construct(
        WebhookAddController $add_controller,
        WebhookEditController $edit_controller,
        WebhookDeleteController $delete_controller
    ) {
        parent::__construct();
        $this->add_controller    = $add_controller;
        $this->edit_controller   = $edit_controller;
        $this->delete_controller = $delete_controller;
    }

    public function process(HTTPRequest $request)
    {
        switch ($request->get('action')) {
            case 'remove-webhook':
                $this->delete_controller->removeWebhook($request);
                break;
            case 'edit-webhook':
                $this->edit_controller->editWebhook($request);
                break;
            case 'add-webhook':
                $this->add_controller->addWebhook($request);
                break;
            default:
                parent::process($request);
        }
    }
}
