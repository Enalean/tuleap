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

require_once 'MediawikiAdminPresenter.class.php';
require_once 'MediawikiUserGroupsMapper.class.php';

class MediawikiAdminController {

    /** @var MediawikiDao */
    private $dao;

    public function __construct() {
        $this->dao = new MediawikiDao();
    }

    public function index(ServiceMediawiki $service, HTTPRequest $request) {
        $GLOBALS['HTML']->includeFooterJavascriptFile(MEDIAWIKI_BASE_URL.'/forgejs/admin.js');
        $service->renderInPage(
            $request,
            '',
            'admin',
            new MediawikiAdminPresenter($request->getProject())
        );
    }

    public function save(ServiceMediawiki $service, HTTPRequest $request) {
        if($request->isPost()) {
            $project          = $request->getProject();
            $groups_mapper    = new MediawikiUserGroupsMapper($this->dao);
            $new_mapping_list = $this->getSelectedMappingsFromRequest($request);
            $groups_mapper->saveMapping($new_mapping_list, $project);
        }

        $GLOBALS['Response']->redirect(MEDIAWIKI_BASE_URL .'/forge_admin?'. http_build_query(
            array(
                'group_id'   => $request->get('group_id'),
            )
        ));
    }

    private function getSelectedMappingsFromRequest(HTTPRequest $request) {
        $list = array();
        foreach(MediawikiUserGroupsMapper::$MEDIAWIKI_GROUPS_NAME as $mw_group_name) {
            $list[$mw_group_name] = array_filter(explode(',', $request->get('hidden_selected_'.$mw_group_name)));
        }
        return $list;
    }
}