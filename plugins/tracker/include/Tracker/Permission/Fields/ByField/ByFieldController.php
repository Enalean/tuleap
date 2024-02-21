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

namespace Tuleap\Tracker\Permission\Fields\ByField;

use HTTPRequest;
use Tracker_FormElementFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ByFieldController implements DispatchableWithRequest
{
    public const URL = '/permissions/fields-by-field';

    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \TemplateRenderer
     */
    private $renderer;

    public function __construct(\TrackerFactory $tracker_factory, \TemplateRenderer $renderer)
    {
        $this->tracker_factory = $tracker_factory;
        $this->renderer        = $renderer;
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

        $this->display($tracker, $request, $layout);
    }

    protected function display(\Tracker $tracker, HTTPRequest $request, BaseLayout $layout): void
    {
        $selected_id = (int) $request->getValidated('selected_id', 'uint', 0);

        $ugroups_for_selected_field = $this->getUGroupPermissionsPerField($tracker, $selected_id);

        $ugroup_list = [];
        foreach ($ugroups_for_selected_field->getUgroupsOrderedWithPrecedence() as $ugroup) {
            if ($this->isFirstRow($ugroup_list)) {
                $ugroup_list[] = new ByFieldOneUGroupWithFieldListPresenter($ugroup, $ugroups_for_selected_field);
            } else {
                $ugroup_list[] = new ByFieldOneUGroupPresenter($ugroup, $ugroups_for_selected_field);
            }
        }

        $tracker_manager = new \TrackerManager();

        $title = dgettext('tuleap-tracker', 'Manage Fields Permissions');

        $tracker->displayAdminPermsHeader($tracker_manager, $title);

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../../../../../scripts/tracker-admin/frontend-assets', '/assets/trackers/tracker-admin'),
                'field-permissions.js'
            )
        );
        $this->renderer->renderToPage(
            'groups-by-field',
            new ByFieldPresenter(
                $tracker,
                $ugroups_for_selected_field->getFieldId(),
                $ugroup_list,
                $ugroups_for_selected_field->getMightNotHaveAccess()
            )
        );

        $tracker->displayFooter($tracker_manager);
    }

    private function getUGroupPermissionsPerField(\Tracker $tracker, int $selected_id): ByFieldGroupPermissions
    {
        $ugroups_permissions = plugin_tracker_permission_get_field_tracker_ugroups_permissions(
            $tracker->getGroupId(),
            $tracker->getId(),
            Tracker_FormElementFactory::instance()->getUsedFields($tracker)
        );

        $ugroups_for_selected_field = $this->getFieldsPermissionForGroupWithFirstMatchingGroup($ugroups_permissions, $selected_id);
        $field_list                 = [];
        foreach ($ugroups_permissions as $field_id => $value_field) {
            $field_id = (int) $field_id;

            if (! isset($field_list[$field_id])) {
                $field_list[$field_id] = new ByFieldFieldListPresenter(
                    $field_id,
                    $value_field['field']['field']->getLabel(),
                    $field_id === $ugroups_for_selected_field->getFieldId()
                );
            }

            foreach ($value_field['ugroups'] as $ugroup_id => $value_ugroup) {
                if ($field_id === $ugroups_for_selected_field->getFieldId()) {
                    $ugroups_for_selected_field->addUGroup($ugroup_id, $value_ugroup['ugroup']['name'], $value_ugroup['permissions'], $value_ugroup['tracker_permissions']);
                }
            }
        }

        $ugroups_for_selected_field->setFieldList($field_list);
        return $ugroups_for_selected_field;
    }

    private function getFieldsPermissionForGroupWithFirstMatchingGroup(array $ugroups_permissions, int $selected_id): ByFieldGroupPermissions
    {
        foreach ($ugroups_permissions as $field_id => $value_field) {
            $field_id = (int) $field_id;
            if ($selected_id === 0 || $field_id === $selected_id) {
                return new ByFieldGroupPermissions($value_field['field']['field']);
            }
        }
        throw new \LogicException('There are no fields with permissions set for this ugroup');
    }

    private function isFirstRow(array $ugroup_list): bool
    {
        return count($ugroup_list) === 0;
    }

    public static function getUrl(\Tracker $tracker): string
    {
        return TRACKER_BASE_URL . self::URL . '/' . $tracker->getId();
    }
}
