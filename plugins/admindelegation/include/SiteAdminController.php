<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\AdminDelegation;

use AdminDelegation_Service;
use AdminDelegation_UserServiceManager;
use ForgeConfig;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use UserManager;
use Valid_String;
use Valid_UInt;
use Valid_WhiteList;

class SiteAdminController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var AdminDelegation_UserServiceManager
     */
    private $user_delegation_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;

    public function __construct(AdminDelegation_UserServiceManager $user_delegation_manager, UserManager $user_manager, AdminPageRenderer $admin_page_renderer)
    {
        $this->user_delegation_manager = $user_delegation_manager;
        $this->user_manager = $user_manager;
        $this->admin_page_renderer = $admin_page_renderer;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        if ($request->isPost()) {
            $vFunc = new Valid_WhiteList('func', array('grant_user_service', 'revoke_user'));
            $vFunc->required();
            if ($request->valid($vFunc)) {
                $func = $request->get('func');
            } else {
                $func = '';
            }

            switch ($func) {
                case 'grant_user_service':
                    $vUser = new Valid_String('user_to_grant');
                    $vUser->required();
                    if ($request->valid($vUser)) {
                        $user = $this->user_manager->findUser($request->get('user_to_grant'));
                    } else {
                        $user = false;
                    }

                    $vService = new Valid_WhiteList('service', AdminDelegation_Service::getAllServices());
                    $vService->required();
                    if ($request->valid($vService)) {
                        $service = $request->get('service');
                    } else {
                        $service = false;
                    }

                    if ($user && $service) {
                        if ($this->user_delegation_manager->addUserService($user, $service, $_SERVER['REQUEST_TIME'])) {
                            $layout->addFeedback('info', 'Permission granted to user');
                        } else {
                            $layout->addFeedback('error', 'Fail to grant permission to user');
                        }
                    } else {
                        $layout->addFeedback('error', 'Either bad user or bad service');
                    }
                    break;

                case 'revoke_user':
                    $vUser = new Valid_UInt('users_to_revoke');
                    $vUser->required();
                    if ($request->validArray($vUser)) {
                        foreach ($request->get('users_to_revoke') as $userId) {
                            $user = $this->user_manager->getUserById($userId);
                            if ($user) {
                                $this->user_delegation_manager->removeUser($user, $_SERVER['REQUEST_TIME']);
                            } else {
                                $layout->addFeedback('error', 'Bad user');
                            }
                        }
                    } else {
                        $layout->addFeedback('error', 'Bad user');
                    }
                    break;

                default:
                    $layout->addFeedback('error', 'Bad action');
                    break;
            }
            $layout->redirect('/plugins/admindelegation/');
        }

        $assets = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/admindelegation',
            '/assets/admindelegation'
        );
        $layout->addCssAsset(new CssAsset($assets, 'style'));
        $layout->addJavascriptAsset(new JavascriptAsset($assets, 'admin-delegation.js'));
        $delegation_builder = new AdminDelegationBuilder($this->user_delegation_manager, $this->user_manager);
        $users              = $delegation_builder->buildUsers();
        $services           = $delegation_builder->buildServices();
        $presenter          = new AdminDelegationPresenter($users, $services);
        $this->admin_page_renderer->renderAPresenter(
            dgettext('tuleap-admindelegation', 'Admin rights delegation'),
            ForgeConfig::get('codendi_dir') . '/plugins/admindelegation/templates',
            'permission-delegation',
            $presenter
        );
    }
}
