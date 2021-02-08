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

namespace Tuleap\Tracker\Permission\FollowUp\PrivateComments;

use Feedback;
use HTTPRequest;
use Tracker;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Permission\Fields\ByGroup\ByGroupController;
use Tuleap\Tracker\Permission\Fields\ByField\ByFieldController;

class PermissionsOnPrivateCommentsUpdateController implements DispatchableWithRequest
{
    public const URL = '/permissions/private-comments';

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var TrackerPrivateCommentsDao */
    private $tracker_private_comments_dao;


    public function __construct(
        TrackerFactory $tracker_factory,
        TrackerPrivateCommentsDao $tracker_private_comments_dao
    ) {
        $this->tracker_factory = $tracker_factory;
        $this->tracker_private_comments_dao = $tracker_private_comments_dao;
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

        if ($tracker->userIsAdmin($request->getCurrentUser())) {
            if ($request->exist('update')) {
                if ($request->exist('ugroups') && is_array($request->get('ugroups'))) {
                    $this->tracker_private_comments_dao->updateUgroupsByTrackerId(
                        $tracker->getId(),
                        $request->get('ugroups')
                    );
                    $layout->addFeedback(
                        Feedback::INFO,
                        $GLOBALS['Language']->getText('project_admin_userperms', 'perm_upd')
                    );
                    $layout->redirect('/plugins/tracker/permissions/follow-up/' . $tracker->getId());
                } else {
                    $this->tracker_private_comments_dao->deleteUgroupsByTrackerId($tracker->getId());
                    $layout->addFeedback(
                        Feedback::INFO,
                        $GLOBALS['Language']->getText('project_admin_userperms', 'perm_upd')
                    );
                    $layout->redirect('/plugins/tracker/permissions/follow-up/' . $tracker->getId());
                }
            }
        } else {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-tracker',
                    'Access denied. You don\'t have permissions to perform this action.'
                )
            );
            $layout->redirect(TRACKER_BASE_URL . '/?tracker=' . $tracker->getId());
        }
    }

    public static function getUrl(Tracker $tracker)
    {
        return TRACKER_BASE_URL . self::URL . '/' . $tracker->getId();
    }
}
