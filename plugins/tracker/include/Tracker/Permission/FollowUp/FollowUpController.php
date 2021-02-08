<?php
/**
 *  Copyright (c) Maximaster, 2020. All rights reserved
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Permission\FollowUp;

use HTTPRequest;
use ProjectUGroup;
use Tracker;
use Tracker_Permission_PermissionPresenterBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Permission\FollowUp\FollowUpPresenter;
use Tuleap\Tracker\Permission\FollowUp\PrivateComments\TrackerPrivateCommentsDao;

class FollowUpController implements DispatchableWithRequest
{
    public const URL = '/permissions/follow-up';

    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \TemplateRenderer
     */
    private $renderer;

    private $presenter_builder;

    public function __construct(\TrackerFactory $tracker_factory, \TemplateRenderer $renderer)
    {
        $this->tracker_factory = $tracker_factory;
        $this->renderer        = $renderer;
        $this->presenter_builder = new Tracker_Permission_PermissionPresenterBuilder();
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
        $tracker = $this->tracker_factory->getTrackerById($variables['id']);
        if (! $tracker || ! $tracker->isActive()) {
            throw new NotFoundException();
        }
        if (! $tracker->userIsAdmin($request->getCurrentUser())) {
            throw new ForbiddenException();
        }

        $this->display($tracker, $request);
    }

    protected function display(\Tracker $tracker, HTTPRequest $request)
    {
        $ugroups_permissions = $this->getUGroupList($tracker);

        $tracker_manager = new \TrackerManager();

        $title = dgettext('tuleap-tracker', 'Fallow Up Permission');

        $tracker->displayAdminPermsHeader($tracker_manager, $title);

        $this->renderer->renderToPage(
            'follow-up',
            new FollowUpPresenter(
                $tracker,
                $ugroups_permissions
            )
        );

        $tracker->displayFooter($tracker_manager);
    }

    private function getUGroupList(Tracker $tracker)
    {
        $ugroup_list = [];

        $ugroups_permissions = plugin_tracker_permission_get_tracker_ugroups_permissions($tracker->getGroupId(), $tracker->getId());
        ksort($ugroups_permissions);
        reset($ugroups_permissions);
        foreach ($ugroups_permissions as $ugroup_permissions) {
            $ugroup      = $ugroup_permissions['ugroup'];

            if ($ugroup['id'] != ProjectUGroup::PROJECT_ADMIN) {
                $ugroup_list[] = $ugroup;
            }
        }

        $private_comments_ugroup_list = $this->getPrivateCommentsUGroup($tracker);

        $private_comments_ugroup_list = array_column($private_comments_ugroup_list, 'ugroup_id');

        foreach ($ugroup_list as $key => $ugroup)
        {
            if (in_array((int)$ugroup['id'], $private_comments_ugroup_list)) {
                $ugroup_list[$key]['selected'] = true;
            }
        }

        unset($key, $ugroup);

        return $ugroup_list;
    }

    private function getPrivateCommentsUGroup(Tracker $tracker)
    {
        $dao = new TrackerPrivateCommentsDao();
        $private_comments_groups = $dao->getAccessUgroupsByTrackerId($tracker->getId());
        return $private_comments_groups;
    }

}
