<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
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

namespace Tuleap\ForumML;

use HTTPRequest;
use Tuleap\ForumML\Threads\ThreadsController;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

class WriteMailController implements DispatchableWithRequest
{
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $layout->redirect(ThreadsController::getUrl((int) $request->get('list')));
    }
}
