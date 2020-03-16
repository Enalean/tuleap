<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Permission\Fields;

use HTTPRequest;
use Tracker_FormElementFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Permission\Fields\ByGroup\ByGroupController;
use Tuleap\Tracker\Permission\Fields\ByField\ByFieldController;

class PermissionsOnFieldsUpdateController implements DispatchableWithRequest
{
    public const URL = '/permissions/fields';

    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;

    public function __construct(\TrackerFactory $tracker_factory)
    {
        $this->tracker_factory = $tracker_factory;
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
                if ($request->exist('permissions') && is_array($request->get('permissions'))) {
                    plugin_tracker_permission_process_update_fields_permissions(
                        $tracker->getGroupId(),
                        $tracker->getId(),
                        Tracker_FormElementFactory::instance()->getUsedFields($tracker),
                        $request->get('permissions')
                    );
                    $layout->addFeedback(\Feedback::INFO, $GLOBALS['Language']->getText('project_admin_userperms', 'perm_upd'));
                    if ($request->get('origin') === 'fields-by-group') {
                        $layout->redirect(ByGroupController::getUrl($tracker) . '?selected_id=' . $request->get('selected_id'));
                    } else {
                        $layout->redirect(ByFieldController::getUrl($tracker) . '?selected_id=' . $request->get('selected_id'));
                    }
                }
            }
        } else {
            $layout->addFeedback(\Feedback::ERROR, $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
            $layout->redirect(TRACKER_BASE_URL . '/?tracker=' . $tracker->getId());
        }
    }

    public static function getUrl(\Tracker $tracker)
    {
        return TRACKER_BASE_URL . self::URL . '/' . $tracker->getId();
    }
}
