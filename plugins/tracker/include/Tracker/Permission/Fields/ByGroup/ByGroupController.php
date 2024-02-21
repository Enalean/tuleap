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

namespace Tuleap\Tracker\Permission\Fields\ByGroup;

use HTTPRequest;
use Tracker_FormElementFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ByGroupController implements DispatchableWithRequest
{
    public const URL = '/permissions/fields-by-group';

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

        $fields_for_selected_group = $this->getFieldsPermissionsPerGroup($tracker, $selected_id);

        $field_list = [];
        foreach ($fields_for_selected_group->getFields() as $field) {
            if ($this->isFirstRow($field_list)) {
                $field_list[] = new ByGroupOneFieldWithUGroupListPresenter($field, $fields_for_selected_group);
            } else {
                $field_list[] = new ByGroupOneFieldPresenter($field, $fields_for_selected_group);
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
            'fields-by-group',
            new ByGroupPresenter(
                $tracker,
                $fields_for_selected_group->getUgroupId(),
                $field_list,
                $fields_for_selected_group->getMightNotHaveAccess()
            )
        );

        $tracker->displayFooter($tracker_manager);
    }

    public static function getUrl(\Tracker $tracker): string
    {
        return TRACKER_BASE_URL . self::URL . '/' . $tracker->getId();
    }

    private function getFieldsPermissionsPerGroup(\Tracker $tracker, int $selected_id): ByGroupFieldsPermissions
    {
        $ugroups_permissions = plugin_tracker_permission_get_field_tracker_ugroups_permissions(
            $tracker->getGroupId(),
            $tracker->getId(),
            Tracker_FormElementFactory::instance()->getUsedFields($tracker)
        );

        $a_star_is_displayed       = false;
        $fields_for_selected_group = $this->getFieldsPermissionForGroupWithFirstMatchingGroup($ugroups_permissions, $selected_id);
        $ugroup_list               = [];
        foreach ($ugroups_permissions as $field_id => $value_field) {
            foreach ($value_field['ugroups'] as $ugroup_id => $value_ugroup) {
                $ugroup_id = (int) $ugroup_id;
                if ($ugroup_id === $fields_for_selected_group->getUgroupId()) {
                    $fields_for_selected_group->addField($value_field['field']['field'], $value_ugroup['permissions']);
                } else {
                    $fields_for_selected_group->addPermissionsForOtherGroups($value_field['field']['field'], (int) $value_ugroup['ugroup']['id'], $value_ugroup['ugroup']['name'], $value_ugroup['permissions']);
                }

                if (! isset($ugroup_list[$ugroup_id])) {
                    $might_not_have_access = false;
                    if (! isset($value_ugroup['tracker_permissions']) || count($value_ugroup['tracker_permissions']) === 0) {
                        $a_star_is_displayed   = true;
                        $might_not_have_access = true;
                    }
                    $ugroup_list[$ugroup_id] = new ByGroupUGroupListPresenter(
                        $ugroup_id,
                        $value_ugroup['ugroup']['name'],
                        $might_not_have_access,
                        $ugroup_id === $fields_for_selected_group->getUgroupId()
                    );
                }
            }
        }

        $fields_for_selected_group->setUgroupList($ugroup_list);
        $fields_for_selected_group->setGroupsMightNotHaveAccess($a_star_is_displayed);
        return $fields_for_selected_group;
    }

    private function getFieldsPermissionForGroupWithFirstMatchingGroup(array $ugroups_permissions, int $selected_id): ByGroupFieldsPermissions
    {
        foreach ($ugroups_permissions as $field_id => $value_field) {
            foreach ($value_field['ugroups'] as $ugroup_id => $value_ugroup) {
                $ugroup_id = (int) $ugroup_id;
                if ($selected_id === 0 || $ugroup_id === $selected_id) {
                    return new ByGroupFieldsPermissions($ugroup_id, $value_ugroup['ugroup']['name']);
                }
            }
        }
        throw new \LogicException('There are no fields with permissions set for this ugroup');
    }

    private function isFirstRow(array $field_list): bool
    {
        return count($field_list) === 0;
    }
}
