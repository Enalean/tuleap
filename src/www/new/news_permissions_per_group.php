<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\News\Admin\AdminNewsDao;
use Tuleap\News\Admin\PerGroup\NewsJSONPermissionsRetriever;
use Tuleap\News\Admin\PerGroup\NewsPermissionsManager;
use Tuleap\News\Admin\PerGroup\NewsPermissionsRepresentationBuilder;

require_once 'pre.php';

if (! $request->getCurrentUser()->isAdmin($request->getProject()->getID())) {
    $GLOBALS['Response']->send400JSONErrors(
        array(
            'error' => _(
                "You don't have permissions to see user groups."
            )
        )
    );
}

$news_permissions_retriever = new NewsJSONPermissionsRetriever(
    new NewsPermissionsRepresentationBuilder(
        new NewsPermissionsManager(
            PermissionsManager::instance(),
            new AdminNewsDao()
        )
    )
);

$news_permissions_retriever->retrieve(
    $request->getProject(),
    $request->get('selected_ugroup_id')
);
