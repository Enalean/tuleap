<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\AdminDelegation\AdminDelegationBuilder;
use Tuleap\AdminDelegation\AdminDelegationPresenter;

require 'pre.php';

// First, check plugin availability
$pluginManager = PluginManager::instance();
$plugin        = $pluginManager->getPluginByName('admindelegation');
if (! $plugin || ! $pluginManager->isPluginAvailable($plugin)) {
    header('Location: ' . get_server_url());
}

// Grant access only to site admin
$user_manager = UserManager::instance();
if (! $user_manager->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect($plugin->getPluginPath() . '/permissions.php');
}

$user_delegation_manager = new AdminDelegation_UserServiceManager(
    new AdminDelegation_UserServiceDao(),
    new AdminDelegation_UserServiceLogDao()
);

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
                $user = $user_manager->findUser($request->get('user_to_grant'));
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
                if ($user_delegation_manager->addUserService($user, $service, $_SERVER['REQUEST_TIME'])) {
                    $GLOBALS['Response']->addFeedback('info', 'Permission granted to user');
                } else {
                    $GLOBALS['Response']->addFeedback('error', 'Fail to grant permission to user');
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', 'Either bad user or bad service');
            }
            break;

        case 'revoke_user':
            $vUser = new Valid_UInt('users_to_revoke');
            $vUser->required();
            if ($request->validArray($vUser)) {
                foreach ($request->get('users_to_revoke') as $userId) {
                    $user = $user_manager->getUserById($userId);
                    if ($user) {
                        $user_delegation_manager->removeUser($user, $_SERVER['REQUEST_TIME']);
                    } else {
                        $GLOBALS['Response']->addFeedback('error', 'Bad user');
                    }
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', 'Bad user');
            }
            break;

        default:
            $GLOBALS['Response']->addFeedback('error', 'Bad action');
            break;
    }
    $GLOBALS['Response']->redirect($plugin->getPluginPath() . '/permissions.php');
}


$delegation_builder = new AdminDelegationBuilder($user_delegation_manager, UserManager::instance());
$users              = $delegation_builder->buildUsers();
$services           = $delegation_builder->buildServices();
$presenter          = new AdminDelegationPresenter($users, $services);
$site_admin         = new AdminPageRenderer();
$site_admin->renderAPresenter(
    $GLOBALS['Language']->getText('plugin_admindelegation', 'permissions_page_title'),
    ForgeConfig::get('codendi_dir') . '/plugins/admindelegation/templates',
    'permission-delegation',
    $presenter
);
