<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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

use Tuleap\User\Account\AccountTabPresenterCollection;

require_once __DIR__ . '/../include/pre.php';

session_require(array('isloggedin'=>'1'));

$em = EventManager::instance();
$um = UserManager::instance();

$user = $um->getCurrentUser();

$can_change_email    = true;
$extra_user_info     = array();

$em->processEvent(
    'display_change_email',
    array(
        'allow' => &$can_change_email
    )
);

$csrf = new CSRFSynchronizerToken('/account/index.php');

$tabs = $em->dispatch(new AccountTabPresenterCollection($user, '/account'));
assert($tabs instanceof AccountTabPresenterCollection);

$presenter = new
User_PreferencesPresenter(
    $user,
    $can_change_email,
    $csrf,
    $tabs
);

$HTML->header(array(
    'title'      => $Language->getText('account_options', 'title'),
    'body_class' => array('account-maintenance')
    ));

$renderer = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../../templates/user');
$renderer->renderToPage('account-maintenance', $presenter);

$HTML->footer(array());
