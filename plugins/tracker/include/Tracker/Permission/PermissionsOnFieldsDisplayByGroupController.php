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

namespace Tuleap\Tracker\Permission;

use Codendi_HTMLPurifier;
use HTTPRequest;
use Tracker_FormElementFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class PermissionsOnFieldsDisplayByGroupController implements DispatchableWithRequest
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
        $this->renderer = $renderer;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout  $layout
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
        $selected_id = $request->getValidated('selected_id', 'uint', false);
        $ugroups_permissions = plugin_tracker_permission_get_field_tracker_ugroups_permissions(
            $tracker->getGroupId(),
            $tracker->getId(),
            Tracker_FormElementFactory::instance()->getUsedFields($tracker)
        );

        $submit_permission = 'PLUGIN_TRACKER_FIELD_SUBMIT';
        $read_permission   = 'PLUGIN_TRACKER_FIELD_READ';
        $update_permission = 'PLUGIN_TRACKER_FIELD_UPDATE';

        //We reorganize the associative array
        $tablo = $ugroups_permissions;
        $ugroups_permissions = array();
        foreach ($tablo as $key_field => $value_field) {
            foreach ($value_field['ugroups'] as $key_ugroup => $value_ugroup) {
                if (!isset($ugroups_permissions[$key_ugroup])) {
                    $ugroups_permissions[$key_ugroup] = array(
                        'values'              => $value_ugroup['ugroup'],
                        'related_parts'       => array(),
                        'tracker_permissions' => $value_ugroup['tracker_permissions']
                    );
                }
                $ugroups_permissions[$key_ugroup]['related_parts'][$key_field] = array(
                    'values'       => $value_field['field'],
                    'permissions' => $value_ugroup['permissions']
                );
            }
        }
        ksort($ugroups_permissions);

        reset($ugroups_permissions);
        $key = key($ugroups_permissions);

        $field_list = [];
        $a_star_is_displayed = false;
        if (count($ugroups_permissions) >= 1) {
            $related_parts = array();
            //The select box for the ugroups or fields (depending $group_first)

            $ugroup_list = [];
            $first_part = [];
            foreach ($ugroups_permissions as $part_permissions) {
                if ($selected_id === false) {
                    $selected_id = $part_permissions['values']['id'];
                }
                $is_selected = false;
                $might_not_have_access = false;
                if ($part_permissions['values']['id'] === $selected_id) {
                    $first_part    = $part_permissions['values'];
                    $related_parts = $part_permissions['related_parts'];
                    $is_selected = true;
                }
                if (isset($part_permissions['tracker_permissions'])
                    && count($part_permissions['tracker_permissions']) === 0) {
                    $might_not_have_access = true;
                    $a_star_is_displayed = true;
                }
                $ugroup_list[] = new PermissionsUGroupListPresenter(
                    (int)$part_permissions['values']['id'],
                    $part_permissions['values']['name'],
                    $might_not_have_access,
                    $is_selected
                );
            }

            $nb_permissions = count($ugroups_permissions[$key]['related_parts']) + 1;

            $is_first = true;

            //The permissions for the current item (field or ugroup, depending $group_id)
            foreach ($related_parts as $ugroup_permissions) {
                $second_part = $ugroup_permissions['values'];
                $permissions = $ugroup_permissions['permissions'];

                //The permissions
                $can_submit = false;
                $can_update = false;
                if (isset($first_part['id'])) {
                    $can_submit = $second_part['field']->isSubmitable();
                    $can_update = $second_part['field']->isUpdateable();
                }

                if ($is_first) {
                    $field_list[] = new PermissionsFieldWithUGroupListPresenter(
                        (int) $second_part['id'],
                        $second_part['name'],
                        isset($first_part['id']) ? (int) $first_part['id'] : 0,
                        isset($permissions[$submit_permission]),
                        $can_submit,
                        $can_update,
                        !isset($permissions[$read_permission]) && !isset($permissions[$update_permission]),
                        isset($permissions[$read_permission]) && !isset($permissions[$update_permission]),
                        isset($permissions[$update_permission]),
                        $nb_permissions,
                        $ugroup_list
                    );
                    $is_first = false;
                } else {
                    $field_list[] = new PermissionsFieldPresenter(
                        (int)$second_part['id'],
                        $second_part['name'],
                        isset($first_part['id']) ? (int) $first_part['id'] : 0,
                        isset($permissions[$submit_permission]),
                        $can_submit,
                        $can_update,
                        !isset($permissions[$read_permission]) && !isset($permissions[$update_permission]),
                        isset($permissions[$read_permission]) && !isset($permissions[$update_permission]),
                        isset($permissions[$update_permission])
                    );
                }
            }
        }

        $tracker_manager = new \TrackerManager();

        $items = $tracker->getPermsItems();
        $title = $items['fields']['title'];
        $breadcrumbs = array(
            $items['fields']
        );
        $tracker->displayAdminPermsHeader($tracker_manager, $title, $breadcrumbs);

        $this->renderer->renderToPage(
            'fields-by-group',
            new PermissionsOnFieldsDisplayByGroupPresenter(
                $tracker,
                (int) $selected_id,
                $field_list,
                $a_star_is_displayed
            )
        );

        $tracker->displayFooter($tracker_manager);
    }

    public static function getUrl(\Tracker $tracker): string
    {
        return TRACKER_BASE_URL.self::URL.'/'.$tracker->getId();
    }
}
