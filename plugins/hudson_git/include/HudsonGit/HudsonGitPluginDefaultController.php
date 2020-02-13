<?php
/**
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

namespace Tuleap\HudsonGit;

use HTTPRequest;
use Tuleap\HudsonGit\Hook\HookController;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class HudsonGitPluginDefaultController implements DispatchableWithRequest
{
    /**
     * @var HookController
     */
    private $hook_controller;
    /**
     * @var bool
     */
    private $is_allowed;

    public function __construct(HookController $hook_controller, bool $is_allowed)
    {
        $this->hook_controller = $hook_controller;
        $this->is_allowed      = $is_allowed;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $action = $request->get('action');

        if (! $this->is_allowed) {
            throw new ForbiddenException();
        }

        switch ($action) {
            case 'save-jenkins':
                $this->hook_controller->save();
                break;

            case 'remove-webhook':
                if ($request->get('webhook_id') === 'jenkins') {
                    $this->hook_controller->remove();
                }
                break;
        }
    }
}
