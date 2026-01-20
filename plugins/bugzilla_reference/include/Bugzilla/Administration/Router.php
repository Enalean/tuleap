<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Bugzilla\Administration;

use Feedback;
use Override;
use PFUser;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

class Router implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(private readonly Controller $controller)
    {
    }

    #[Override]
    public function process(\Tuleap\HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $current_user = $request->getCurrentUser();
        $this->checkUserIsSiteAdmin($current_user);

        $assets = $this->getIncludeAsset();
        $layout->addJavascriptAsset(new JavascriptViteAsset($assets, 'src/bugzilla-reference.js'));

        $action = $request->get('action');
        switch ($action) {
            case 'add-reference':
                $this->controller->addReference($request);
                break;
            case 'edit-reference':
                $this->controller->editReference($request);
                break;
            case 'delete-reference':
                $this->controller->deleteReference($request);
                break;
            default:
                $this->controller->display();
        }
    }

    private function checkUserIsSiteAdmin(PFUser $user): void
    {
        if (! $user->isSuperUser()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );
            $GLOBALS['Response']->redirect('/');
        }
    }

    private function getIncludeAsset(): IncludeViteAssets
    {
        return new IncludeViteAssets(
            __DIR__ . '/../../../scripts/bugzilla-reference/frontend-assets',
            '/assets/bugzilla_reference/bugzilla-reference'
        );
    }
}
