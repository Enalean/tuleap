<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Plugin;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\ServiceInstrumentation;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class PluginLegacyController implements DispatchableWithRequest
{
    /**
     * @var PluginWithLegacyInternalRouting
     */
    private $plugin;

    public function __construct(PluginWithLegacyInternalRouting $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables) : void
    {
        ServiceInstrumentation::increment($this->plugin->getName());
        $this->plugin->process();
    }
}
