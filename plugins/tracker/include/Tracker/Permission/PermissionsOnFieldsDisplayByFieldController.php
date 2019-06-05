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

class PermissionsOnFieldsDisplayByFieldController implements DispatchableWithRequest
{
    public const URL = '/permissions/fields-by-field';

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
        $tracker_manager = new \TrackerManager();

        $items = $tracker->getPermsItems();
        $title = $items['fields']['title'];
        $breadcrumbs = array(
            $items['fields']
        );
        $tracker->displayAdminPermsHeader($tracker_manager, $title, $breadcrumbs);
        echo '<h2>'. $title .'</h2>';

        $hp = Codendi_HTMLPurifier::instance();

        $selected_id = $request->get('selected_id');
        $selected_id = $selected_id ? $selected_id : false;
        $ugroups_permissions = plugin_tracker_permission_get_field_tracker_ugroups_permissions(
            $tracker->getGroupId(),
            $tracker->getId(),
            Tracker_FormElementFactory::instance()->getUsedFields($tracker)
        );

        $submit_permission = 'PLUGIN_TRACKER_FIELD_SUBMIT';
        $read_permission   = 'PLUGIN_TRACKER_FIELD_READ';
        $update_permission = 'PLUGIN_TRACKER_FIELD_UPDATE';
        $none              = 'PLUGIN_TRACKER_NONE';
        $attributes_for_selected = 'selected="selected" style="background:#EEE;"'; //TODO: put style in stylesheet

        $html = '';

        $url_action_with_group_first_for_js = self::getUrl($tracker) .'?selected_id=';

        $html .= <<<EOS
            <script type="text/javascript">
            <!--
            function changeFirstPartId(wanted) {
                location.href = '$url_action_with_group_first_for_js' + wanted;
            }
            //-->
            </script>
EOS;

        foreach ($ugroups_permissions as $key_field => $value_field) {
            $ugroups_permissions[$key_field]['values']        =& $ugroups_permissions[$key_field]['field'];
            $ugroups_permissions[$key_field]['related_parts'] =& $ugroups_permissions[$key_field]['ugroups'];
            foreach ($value_field['ugroups'] as $key_ugroup => $value_ugroup) {
                $ugroups_permissions[$key_field]['related_parts'][$key_ugroup]['values'] =& $ugroups_permissions[$key_field]['related_parts'][$key_ugroup]['ugroup'];
            }
            ksort($ugroups_permissions[$key_field]['related_parts']);
            reset($ugroups_permissions[$key_field]['related_parts']);
        }
        $header = array(
            $GLOBALS['Language']->getText('plugin_tracker_include_report', 'field_label'),
            $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'ugroup'),
            $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $submit_permission),
            $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'permissions')) ;

        reset($ugroups_permissions);
        $key = key($ugroups_permissions);

        //header
        if (count($ugroups_permissions[$key]['related_parts']) < 1) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'fields_no_ugroups');
        } else {
            //The permission form
            $html .= '<form name="form_tracker_permissions" action="'.PermissionsOnFieldsUpdateController::getUrl($tracker).'" method="post">';
            $html .= '<div>';
            $html .= '<input type="hidden" name="origin" value="fields-by-field" />';
            $html .= '<input type="hidden" name="selected_id" value="'. (int)$selected_id .'" />';

            //intro
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'fields_tracker_intro');

            //We display 'group_first' or 'field_first'

            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'fields_tracker_toggle_group', PermissionsOnFieldsDisplayByGroupController::getUrl($tracker));

            $html .= html_build_list_table_top($header);

            //body
            $i = 0;
            $a_star_is_displayed = false;
            $related_parts = array();
            //The select box for the ugroups or fields (depending $group_first)
            $html .= '<tr class="'. util_get_alt_row_color($i++) .'">';
            $html .= '<td rowspan="'. (count($ugroups_permissions[$key]['related_parts'])+1) .'" style="vertical-align:top;">';
            $html .= '<select onchange="changeFirstPartId(this.options[this.selectedIndex].value);">';

            foreach ($ugroups_permissions as $part_permissions) {
                if ($selected_id === false) {
                    $selected_id = $part_permissions['values']['id'];
                }
                $html .= '<option value="'. (int)$part_permissions['values']['id'] .'" ';
                if ($part_permissions['values']['id'] === $selected_id) {
                    $first_part    = $part_permissions['values'];
                    $related_parts = $part_permissions['related_parts'];
                    $html .= $attributes_for_selected;
                }
                $html .= ' >';
                $html .= $hp->purify($part_permissions['values']['name']);
                $html .= '</option>';
            }
            $html .= '</select>';
            $html .= '</td>';
            $is_first = true;

            //The permissions for the current item (field or ugroup, depending $group_id)
            foreach ($related_parts as $ugroup_permissions) {
                $second_part = $ugroup_permissions['values'];
                $permissions = $ugroup_permissions['permissions'];

                //The group
                if (!$is_first) {
                    $html .= '<tr class="'. util_get_alt_row_color($i++) .'">';
                } else {
                    $is_first = false;
                }
                $html .= '<td>';

                $name = '<a href="'. PermissionsOnFieldsDisplayByGroupController::getUrl($tracker).'?selected_id='. (int)$second_part['id'] .'">';
                $name .=  $hp->purify($second_part['name']) ;
                $name .= '</a>';
                if (isset($ugroup_permissions['tracker_permissions']) && count($ugroup_permissions['tracker_permissions']) === 0) {
                    $name = '<span >'. $name .' *</span>'; //TODO css
                    $a_star_is_displayed = true;
                }
                $html .= $name;

                $html .= '</td>';

                //The permissions
                if (isset($first_part['id'])) {
                    //Submit permission
                    $html .= '<td style="text-align:center;">';
                    $name_of_variable = "permissions[".(int)$first_part['id']."][".(int)$second_part['id']."]";
                    $html .= '<input type="hidden" name="'. $name_of_variable .'[submit]" value="off"/>';

                    $can_submit = $first_part['field']->isSubmitable();

                    $can_update = $first_part['field']->isUpdateable();

                    $html .= "<input type='checkbox' name=\"".$name_of_variable.'[submit]"  '.
                        (isset($permissions[$submit_permission])?"checked='checked'":"")." ".($can_submit?"":"disabled='disabled'")." /> ";
                    $html .= "</td><td>";


                    //Other permissions (R/W)
                    $html .= "<select name='".$name_of_variable."[others]' >";
                    $html .= "<option value='100' ".(!isset($permissions[$read_permission]) && !isset($permissions[$update_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $none)."</option>";
                    $html .= "<option value='0' ".(isset($permissions[$read_permission]) && !isset($permissions[$update_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $read_permission)."</option>";

                    if ($can_update) {
                        $html .= "<option value='1' ".(isset($permissions[$update_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $update_permission)."</option>";
                    }
                    $html .= "</select>";
                }
                $html .= "</td>";
                $html .= "</tr>\n";
            }

            //end of table
            $html .= "</table>";
            if ($a_star_is_displayed) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'ug_may_have_no_access', TRACKER_BASE_URL."/?tracker=".(int)$tracker->getId()."&func=admin-perms-tracker");
            }
            $html .= "<input type='submit' name='update' value=\"".$GLOBALS['Language']->getText('project_admin_permissions', 'submit_perm')."\" />";
        }
        $html .= "</div></form>";
        $html .= "<p>";
        $html .= $GLOBALS['Language']->getText('project_admin_permissions', 'admins_create_modify_ug', ["/project/admin/ugroup.php?group_id=".(int)$tracker->getGroupId()]);
        $html .= "</p>";
        print $html;

        $tracker->displayFooter($tracker_manager);
    }

    public static function getUrl(\Tracker $tracker) : string
    {
        return TRACKER_BASE_URL.self::URL.'/'.$tracker->getId();
    }
}
