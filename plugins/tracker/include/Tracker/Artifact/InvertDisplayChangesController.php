<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Artifact\View\ArtifactViewEdit;

class InvertDisplayChangesController implements DispatchableWithRequestNoAuthz
{
    /**
     * Is able to process a request routed by FrontRouter
     *
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if ($request->getCurrentUser()->isAnonymous()) {
            throw new ForbiddenException();
        }
        $request->getCurrentUser()->togglePreference(ArtifactViewEdit::USER_PREFERENCE_DISPLAY_CHANGES, 0, 1);
    }
}
