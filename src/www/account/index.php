<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once 'pre.php';

session_require(array('isloggedin'=>'1'));

$em = EventManager::instance();
$um = UserManager::instance();

$user = $um->getCurrentUser();

$third_paty_html     = '';
$can_change_password = true;
$can_change_realname = true;
$can_change_email    = true;
$extra_user_info     = array();
$ssh_keys_extra_html = '';

$em->processEvent(
    Event::MANAGE_THIRD_PARTY_APPS,
    array(
        'user' => $user,
        'html' => &$third_paty_html
    )
);

$em->processEvent(
    'display_change_password',
    array(
        'allow' => &$can_change_password
    )
);

$em->processEvent(
    'display_change_realname',
    array(
        'allow' => &$can_change_realname
    )
);

$em->processEvent(
    'display_change_email',
    array(
        'allow' => &$can_change_email
    )
);

$em->processEvent(
    'account_pi_entry',
    array(
        'user'      => $user,
        'user_info' => &$extra_user_info,
    )
);

$em->processEvent(
    Event::LIST_SSH_KEYS,
    array(
        'user' => $user,
        'html' => &$ssh_keys_extra_html
    )
);

$presenter = new User_PreferencesPresenter(
    $user,
    $can_change_realname,
    $can_change_email,
    $can_change_password,
    $extra_user_info,
    $um->getUserAccessInfo($user),
    $ssh_keys_extra_html,
    $third_paty_html
);

$HTML->header(array('title' => $Language->getText('account_options', 'title')));
$renderer = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../../templates/user');
$renderer->renderToPage('account-maintenance', $presenter);
$HTML->footer(array());
