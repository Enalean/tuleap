<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Project\Admin\Reference;

use Codendi_HTMLPurifier;
use Event;
use EventManager;
use HTTPRequest;
use ProjectManager;
use Reference;
use ReferenceManager;
use Views;

class ReferenceAdministrationViews extends Views
{
    protected $natures;

    public function __construct($controler, $view = null)
    {
        $this->View($controler, $view);
        $referenceManager = ReferenceManager::instance();
        $this->natures = $referenceManager->getAvailableNatures();
    }

    public function header()
    {
        project_admin_header(
            array(
                'title' => $GLOBALS['Language']->getText('project_reference', 'edit_reference'),
                'group' => $GLOBALS['group_id'],
                'help'  => 'project-admin.html#reference-pattern-configuration'
            ),
            'references'
        );
    }

    public function footer()
    {
        project_admin_footer(array());
    }

    // {{{ Views
    public function browse()
    {
        $request  = HTTPRequest::instance();
        $pm       = ProjectManager::instance();
        $purifier = Codendi_HTMLPurifier::instance();
        $project  = $pm->getProject($request->get('group_id'));
        if ($request->get('group_id') == 100) {
            print '<P><h2>' . $GLOBALS['Language']->getText('project_reference', 'edit_system_s') . '</B></h2>';
        } else {
            print '<P><h2>' . $GLOBALS['Language']->getText('project_reference', 'edit_s_for', $purifier->purify($project->getPublicName())) . '</h2>';
        }
        print '
<P>
<H3>' . $GLOBALS['Language']->getText('project_reference', 'new_s') . '</H3>
<a href="/project/admin/reference.php?view=creation&group_id=' . $request->get('group_id') . '">' . $GLOBALS['Language']->getText('project_reference', 'create_s') . '</a>
<p>


<H3>' . $GLOBALS['Language']->getText('project_reference', 'manage_sys_r') . '</H3>
<P>
';
        /*
         Show the references that this project is using
        */
        $referenceManager = ReferenceManager::instance();
        $references = $referenceManager->getReferencesByGroupId($request->get('group_id')); // References are sorted by scope first

        echo '
<HR>
<TABLE width="100%" cellspacing=0 cellpadding=3 border=0>';

        $title_arr = array();
        if ($request->get('group_id') == 100) {
            $title_arr[] = $GLOBALS['Language']->getText('project_reference', 'id');
        }
        $title_arr[] = $GLOBALS['Language']->getText('project_reference', 'keyword');
        $title_arr[] = $GLOBALS['Language']->getText('project_reference', 'r_desc');
        $title_arr[] = $GLOBALS['Language']->getText('project_reference', 'r_nature');
        $title_arr[] = $GLOBALS['Language']->getText('global', 'status');
        if ($request->get('group_id') == 100) {
            $title_arr[] = $GLOBALS['Language']->getText('project_reference', 'scope');
            $title_arr[] = $GLOBALS['Language']->getText('project_reference', 'r_service');
            $title_arr[] = $GLOBALS['Language']->getText('project_reference', 'del?');
        }
        echo html_build_list_table_top($title_arr);
        $current_scope = 'S';
        $row_num       = 0;
        foreach ($references as $ref) {
            if ($ref->getScope() != $current_scope) {
                //changing from system to project
                echo '</TABLE><H3>' . $GLOBALS['Language']->getText('project_reference', 'manage_proj_r') . '</H3><P>';
                echo '<TABLE width="100%" cellspacing=0 cellpadding=3 border=0>';
                $title_arr_project = array();
                if ($request->get('group_id') == 100) {
                    $title_arr_project[] = $GLOBALS['Language']->getText('project_reference', 'id');
                }
                $title_arr_project[] = $GLOBALS['Language']->getText('project_reference', 'keyword');
                $title_arr_project[] = $GLOBALS['Language']->getText('project_reference', 'r_desc');
                $title_arr_project[] = $GLOBALS['Language']->getText('project_reference', 'r_nature');
                $title_arr_project[] = $GLOBALS['Language']->getText('global', 'status');
                if ($request->get('group_id') == 100) {
                    $title_arr_project[] = $GLOBALS['Language']->getText('project_reference', 'scope');
                    $title_arr_project[] = $GLOBALS['Language']->getText('project_reference', 'r_service');
                }
                $title_arr_project[] = $GLOBALS['Language']->getText('project_reference', 'del?');
                echo html_build_list_table_top($title_arr_project);
            }
            $current_scope = $ref->getScope();
            $this->displayReferenceRow($ref);
        }

        echo '
</TABLE>
';
    }

    /**
     * Return ready to display description of a reference
     *
     * @param Reference $ref Reference
     * @return String
     */
    public static function getReferenceDescription(Reference $ref)
    {
        $description = '';
        if (strpos($ref->getDescription(), "_desc_key") !== false) {
            $matches = array();
            if (preg_match('/(.*):(.*)/', $ref->getDescription(), $matches)) {
                if ($GLOBALS['Language']->hasText($matches[1], $matches[2])) {
                    $description = $GLOBALS['Language']->getText($matches[1], $matches[2]);
                }
            } else {
                $description = $GLOBALS['Language']->getText('project_reference', $ref->getDescription());
            }
        } else {
            $description = $ref->getDescription();
        }
        return $description;
    }

    private function displayReferenceRow($ref)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $can_be_deleted = ($ref->getScope() != "S") || ($ref->getGroupId() == 100);
        EventManager::instance()->processEvent(
            Event::GET_REFERENCE_ADMIN_CAPABILITIES,
            array(
                'reference'      => $ref,
                'can_be_deleted' => &$can_be_deleted
            )
        );

        if ($ref->getId() == 100) {
            return; // 'None' reference
        }

        $description = $purifier->purify($this->getReferenceDescription($ref));

        if (array_key_exists($ref->getNature(), $this->natures)) {
            $nature_desc = $purifier->purify($this->natures[$ref->getNature()]['label']);
        } else {
            $nature_desc = $purifier->purify($ref->getNature());
        }

        echo '<TR>';
        if ($ref->getGroupId() == 100) {
            echo '<TD><a href="/project/admin/reference.php?view=edit&group_id=' . $ref->getGroupId() . '&reference_id=' . $ref->getId() . '" title="' . $description . '">' . $ref->getId() . '</TD>';
        }
        echo '<TD><a href="/project/admin/reference.php?view=edit&group_id=' . $ref->getGroupId() . '&reference_id=' . $ref->getId() . '" title="' . $description . '">' . $purifier->purify($ref->getKeyword()) . '</TD>';
        echo '<TD>' . $description . '</TD>';
        echo '<TD>' . $nature_desc . '</TD>';

        echo '<TD align="center">' . ( $ref->isActive() ? $GLOBALS['Language']->getText('project_reference', 'enabled') : $GLOBALS['Language']->getText('project_reference', 'disabled') ) . '</TD>';
        if ($ref->getGroupId() == 100) {
            $scope = $GLOBALS['Language']->getText('project_reference', 'ref_scope_S');
            if ($ref->getScope() === 'P') {
                $scope = $GLOBALS['Language']->getText('project_reference', 'ref_scope_P');
            }
            echo'<TD align="center">' . $purifier->purify($scope) . '</TD>';
            echo'<TD align="center">' . $purifier->purify($ref->getServiceShortName()) . '</TD>';
        }

        if ($can_be_deleted) {
            echo '<TD align="center"><a href="/project/admin/reference.php?group_id=' . $ref->getGroupId() . '&reference_id=' . $ref->getId() . '&action=do_delete" onClick="return confirm(\'';
            if ($ref->getScope() == "S") {
                echo $purifier->purify($GLOBALS['Language']->getText('project_reference', 'warning_del_r', $ref->getKeyword()), CODENDI_PURIFIER_JS_QUOTE);
            } else {
                echo $GLOBALS['Language']->getText('project_reference', 'del_r');
            }
            echo '\')"><IMG SRC="' . util_get_image_theme("ic/trash.png") . '" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A></TD>';
        } else {
            echo '<td></td>';
        }
        echo '</TR>';
    }


    public function creation()
    {
        $request = HTTPRequest::instance();
        $group_id = $request->get('group_id');

        $su = false;
        if (user_is_super_user()) {
            $su = true;
        }

        echo '
<h3>' . $GLOBALS['Language']->getText('project_reference', 'r_creation') . '</h3>
<form name="form_create" method="post" action="/project/admin/reference.php?group_id=' . $group_id . '">
<input type="hidden" name="action" VALUE="do_create">
<input type="hidden" name="view" VALUE="browse">
<input type="hidden" name="group_id" VALUE="' . $group_id . '">

<table width="100%" cellspacing=0 cellpadding=3 border=0>
<tr><td width="10%"><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'r_keyword_desc') . '">' . $GLOBALS['Language']->getText('project_reference', 'r_keyword') . ':</a>&nbsp;<font color="red">*</font></td>
<td><input type="text" name="keyword" size="25" maxlength="25"></td></tr>';
        echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'r_desc_in_tooltip') . '">' . $GLOBALS['Language']->getText('project_reference', 'r_desc') . '</a>:&nbsp;</td>
<td><input type="text" name="description" size="70" maxlength="255"></td></tr>';
        echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'r_nature_desc') . '">' . $GLOBALS['Language']->getText('project_reference', 'r_nature') . '</a>:&nbsp;</td>
<td>';
        echo '<select name="nature" >';

        foreach ($this->natures as $nature_key => $nature_desc) {
            $can_create = true;
            EventManager::instance()->processEvent(
                Event::CAN_USER_CREATE_REFERENCE_WITH_THIS_NATURE,
                array(
                    'nature'     => $nature_key,
                    'can_create' => &$can_create
                )
            );
            if ($can_create) {
                echo '<option value="' . $nature_key . '">' . $nature_desc['label'] . '</option>';
            }
        }

        echo '</select>';

        echo '
</td></tr>';
        echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'url') . '">' . $GLOBALS['Language']->getText('project_reference', 'r_link') . '</a>:&nbsp;<font color="red">*</font></td>
<td><input type="text" name="link" size="70" maxlength="255"> ';
            echo  help_button('project-admin.html#reference-pattern-configuration');
            echo '</td></tr>';
        if (($group_id == 100) && ($su)) {
            echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'r_service_desc') . '">' . $GLOBALS['Language']->getText('project_reference', 'r_service') . '</a>:</td>
<td>';
// Get list of services
            $result = db_query("SELECT * FROM service WHERE group_id=100 ORDER BY rank");
            $serv_label = array();
            $serv_short_name = array();
            while ($serv = db_fetch_array($result)) {
                $label = $serv['label'];
                if ($label == "service_" . $serv['short_name'] . "_lbl_key") {
                    $label = $GLOBALS['Language']->getText('project_admin_editservice', $label);
                }
                $serv_short_name[] = $serv['short_name'];
                $serv_label[] = $label;
            }
            echo html_build_select_box_from_arrays($serv_short_name, $serv_label, "service_short_name");
            echo '</td></tr>';
            echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'r_scope') . '">' . $GLOBALS['Language']->getText('project_reference', 'scope') . ':</a></td>
<td><FONT size="-1">' . $GLOBALS['Language']->getText('project_reference', 'system') . '
        </FONT></td></tr>';
            echo '<input type="hidden" name="scope" VALUE="S">';
        } else {
            echo '<input type="hidden" name="service_short_name" VALUE="100">';
            echo '<input type="hidden" name="scope" VALUE="P">';
        }
        echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'enabled_desc') . '">' . $GLOBALS['Language']->getText('project_reference', 'enabled') . ':</a> </td>
<td><input type="CHECKBOX" NAME="is_used" VALUE="1" CHECKED></td></tr>';
        if ($su) {
            echo '<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'force_desc') . '">' . $GLOBALS['Language']->getText('project_reference', 'force') . '</a> </td><td><input type="CHECKBOX" NAME="force"></td></tr>';
        }
        echo '
</table>
<P><INPUT type="submit" name="Create" value="' . $GLOBALS['Language']->getText('global', 'btn_create') . '">
</form>
<p><font color="red">*</font>: ' . $GLOBALS['Language']->getText('project_reference', 'fields_required') . '</p>
';
    }


    public function edit()
    {
        $request = HTTPRequest::instance();
        $group_id = $request->get('group_id');

        $purifier = Codendi_HTMLPurifier::instance();

        $pm      = ProjectManager::instance();
        $project = $pm->getProject($group_id);

        $refid = $request->get('reference_id');

        if (! $refid) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('project_reference', 'missing_parameter'));
        }

        $referenceManager = ReferenceManager::instance();
        $ref = $referenceManager->loadReference($refid, $group_id);

        if (! $ref) {
            echo  '<p class="alert alert-error"> ' . _('This reference does not exist') . '</p>';

            return;
        }

        $su = false;
        if (user_is_super_user()) {
            $su = true;
        }
        $star = '&nbsp;<font color="red">*</font>';

        // "Read-only" -> can only edit reference availability (system reference)
        $can_be_edited = true;
        EventManager::instance()->processEvent(
            Event::GET_REFERENCE_ADMIN_CAPABILITIES,
            array(
                'reference'     => $ref,
                'can_be_edited' => &$can_be_edited
            )
        );
        $ro = ! $can_be_edited || ($ref->isSystemReference() && $ref->getGroupId() != 100);
        if ($ro) {
            $star = "";
        }

        echo '
<h3>' . $GLOBALS['Language']->getText('project_reference', 'edit_r') . '</h3>
<form name="form_create" method="post" action="/project/admin/reference.php?group_id=' . $group_id . '">
<input type="hidden" name="action" VALUE="do_edit">
<input type="hidden" name="view" VALUE="browse">
<input type="hidden" name="group_id" VALUE="' . $group_id . '">
<input type="hidden" name="reference_id" VALUE="' . $refid . '">

<table width="100%" cellspacing=0 cellpadding=3 border=0>
<tr><td width="10%"><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'r_keyword_desc') . '">' . $GLOBALS['Language']->getText('project_reference', 'r_keyword') . ':</a>' . $star . '</td>
<td>';
        if ($ro) {
            echo $purifier->purify($ref->getKeyWord());
        } else {
            echo '<input type="text" name="keyword" size="25" maxlength="25" value="' . $purifier->purify($ref->getKeyWord()) . '">';
        }
        echo '</td></tr>';
        echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'r_desc_in_tooltip') . '">' . $GLOBALS['Language']->getText('project_reference', 'r_desc') . '</a>:&nbsp;</td>
<td>';
        if ($ro) {
            if ($ref->getDescription() == "reference_" . $ref->getKeyWord() . "_desc_key") {
                echo $purifier->purify($GLOBALS['Language']->getText('project_reference', $ref->getDescription()));
            } else {
                echo $purifier->purify($ref->getDescription());
            }
        } else {
            echo '<input type="text" name="description" size="70" maxlength="255" value="' . $purifier->purify($ref->getDescription()) . '">';
        }
        echo '</td></tr>';
        echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'r_nature_desc') . '">' . $GLOBALS['Language']->getText('project_reference', 'r_nature') . '</a>:&nbsp;</td>
<td>';
        if ($ro) {
            echo $purifier->purify($ref->getNature());
        } else {
            echo '<select name="nature" >';
            foreach ($this->natures as $nature_key => $nature_desc) {
                $can_create = true;
                EventManager::instance()->processEvent(
                    Event::CAN_USER_CREATE_REFERENCE_WITH_THIS_NATURE,
                    array(
                        'nature'     => $nature_key,
                        'can_create' => &$can_create
                    )
                );
                if ($can_create) {
                    if ($ref->getNature() == $nature_key) {
                        $selected = 'selected="selected"';
                    } else {
                        $selected = '';
                    }
                    echo '<option value="' . $purifier->purify($nature_key) . '" ' . $selected . '>' . $purifier->purify($nature_desc['label']) . '</option>';
                }
            }
            echo '</select>';
        }
        echo '</td></tr>';
        echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'url') . '">' . $GLOBALS['Language']->getText('project_reference', 'r_link') . '</a>:' . $star . '</td>
<td>';
        if ($ro) {
            echo $purifier->purify($ref->getLink());
        } else {
            echo '<input type="text" name="link" size="70" maxlength="255" value="' . $purifier->purify($ref->getLink()) . '"> ';
            echo  help_button('project-admin.html#creating-or-updating-a-reference-pattern');
        }
        echo '</td></tr>';
        if ($group_id == 100) {
            echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'r_service_desc') . '">' . $GLOBALS['Language']->getText('project_reference', 'r_service') . '</a>:</td>
<td>';
            // Get list of services
            $result = db_query("SELECT * FROM service WHERE group_id=100 ORDER BY rank");
            $serv_label = array();
            $serv_short_name = array();
            while ($serv = db_fetch_array($result)) {
                $label = $serv['label'];
                if ($label == "service_" . $serv['short_name'] . "_lbl_key") {
                    $label = $GLOBALS['Language']->getText('project_admin_editservice', $label);
                }
                $serv_short_name[] = $serv['short_name'];
                $serv_label[] = $label;
            }
            if ($ro) {
                echo $purifier->purify($ref->getServiceShortName());
            } else {
                echo html_build_select_box_from_arrays($serv_short_name, $serv_label, "service_short_name", $ref->getServiceShortName());
            }
            echo '</td></tr>';
            echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'r_scope') . '">' . $GLOBALS['Language']->getText('project_reference', 'scope') . ':</a></td>
<td><FONT size="-1">' . ($ref->getScope() == 'S' ? $GLOBALS['Language']->getText('project_reference', 'system') : $GLOBALS['Language']->getText('project_reference', 'project')) . '</FONT></td></tr>';
        }
        echo '
<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'enabled_desc') . '">' . $GLOBALS['Language']->getText('project_reference', 'enabled') . ':</a> </td>
<td><input type="CHECKBOX" NAME="is_used" VALUE="1"' . ($ref->isActive() ? " CHECKED" : '') . '></td></tr>';
        if ($su) {
            echo '<tr><td><a href="#" title="' . $GLOBALS['Language']->getText('project_reference', 'force_desc') . '">'
                      . $GLOBALS['Language']->getText('project_reference', 'force') . '</a> </td>
                       <td><input type="CHECKBOX" NAME="force"></td></tr>';
        }
        echo '
</table>

<P><INPUT type="submit" name="Create" value="' . $GLOBALS['Language']->getText('global', 'btn_update') . '">
</form>';
        if (!$ro) {
            echo '<p>' . $star . ': ' . $GLOBALS['Language']->getText('project_reference', 'fields_required') . '</p>';
        }
    }
}
