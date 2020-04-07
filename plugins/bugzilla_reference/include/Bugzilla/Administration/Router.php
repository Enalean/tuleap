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
use HTTPRequest;
use PFUser;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

class Router implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var Controller
     */
    private $controller;

    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        $this->checkUserIsSiteAdmin($current_user);

        $assets = $this->getIncludeAsset();
        $layout->includeFooterJavascriptFile($assets->getFileURL('bugzilla-reference.js'));
        $layout->addCssAsset(new CssAsset($assets, 'burningparrot-style'));

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

    private function checkUserIsSiteAdmin(PFUser $user)
    {
        if (! $user->isSuperUser()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );
            $GLOBALS['Response']->redirect('/');
        }
    }

    private function getIncludeAsset(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../../../src/www/assets/bugzilla_reference',
            '/assets/bugzilla_reference'
        );
    }
}
