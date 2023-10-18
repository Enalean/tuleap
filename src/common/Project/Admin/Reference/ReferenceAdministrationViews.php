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
use ReferenceManager;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Views;
use Tuleap\Reference\NatureCollection;

class ReferenceAdministrationViews extends Views
{
    /**
     * @var NatureCollection
     */
    private $nature_collection;

    /**
     * @var ReferenceManager
     */
    private $reference_manager;

    public function __construct($controler, $view = null)
    {
        $this->View($controler, $view);
        $this->reference_manager = ReferenceManager::instance();
        $this->nature_collection = $this->reference_manager->getAvailableNatures();
    }

    public function header()
    {
        project_admin_header(
            _('Editing reference patterns'),
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME
        );
    }

    public function footer()
    {
        project_admin_footer([]);
    }

    public function creation()
    {
        $request    = HTTPRequest::instance();
        $group_id   = $request->get('group_id');
        $purifier   = Codendi_HTMLPurifier::instance();
        $url        = '/project/admin/reference.php?group_id=' . urlencode($purifier->purify($group_id));
        $csrf_token = new \CSRFSynchronizerToken($url);

        $su = false;
        if (user_is_super_user()) {
            $su = true;
        }
        echo '
<h3>' . _('Create a new reference pattern') . '</h3>
<form name="form_create" method="post" action="' . $url . '">
<input type="hidden" name="action" VALUE="do_create">
' . $csrf_token->fetchHTMLInput() . '
<input type="hidden" name="view" VALUE="browse">
<input type="hidden" name="group_id" VALUE="' . $purifier->purify($group_id) . '">

<table width="100%" cellspacing=0 cellpadding=3 border=0>
<tr><td width="10%"><a href="#" title="' . _('Keyword that will trigger a reference creation') . '">' . _('Keyword') . ':</a>&nbsp;<font color="red">*</font></td>
<td><input type="text" name="keyword" size="25" maxlength="25"></td></tr>';
        echo '
<tr><td><a href="#" title="' . _('This description will be displayed in a tooltip above reference.') . '">' . _('Description') . '</a>:&nbsp;</td>
<td><input type="text" name="description" size="70" maxlength="255"></td></tr>';
        echo '
<tr><td><a href="#" title="' . _('Specify the nature of the reference, or Other if any.') . '">' . _('Nature') . '</a>:&nbsp;</td>
<td>';
        echo '<select name="nature" >';

        foreach ($this->nature_collection->getNatures() as $nature_key => $nature_desc) {
            if ($nature_desc->user_can_create_ref_with_nature) {
                echo '<option value="' . $purifier->purify($nature_key) . '">' . $purifier->purify($nature_desc->label) . '</option>';
            }
        }

        echo '</select>';

        echo '
</td></tr>';
        echo '
<tr><td><a href="#" title="' . _('URL pointed by the reference') . '">' . _('Link') . '</a>:&nbsp;<font color="red">*</font></td>
<td><input type="text" name="link" size="70" maxlength="255"> ';
            echo help_button('project-admin.html#reference-pattern-configuration');
            echo '</td></tr>';
        if (($group_id == 100) && ($su)) {
            echo '
<tr><td><a href="#" title="' . _('If the reference pattern is specific to one service, select it here') . '">' . _('Bound to service') . '</a>:</td>
<td>';
// Get list of services
            $result          = db_query("SELECT * FROM service WHERE group_id=100 ORDER BY `rank`");
            $serv_label      = [];
            $serv_short_name = [];
            while ($serv = db_fetch_array($result)) {
                $label = $serv['label'];
                if ($label == "service_" . $serv['short_name'] . "_lbl_key") {
                    $label = $GLOBALS['Language']->getOverridableText('project_admin_editservice', $label);
                }
                $serv_short_name[] = $serv['short_name'];
                $serv_label[]      = $label;
            }
            echo html_build_select_box_from_arrays($serv_short_name, $serv_label, "service_short_name");
            echo '</td></tr>';
            echo '
<tr><td><a href="#" title="' . _('Scope of the reference pattern: project only or system-wide') . '">' . _('Scope') . ':</a></td>
<td><FONT size="-1">' . _('system') . '
        </FONT></td></tr>';
            echo '<input type="hidden" name="scope" VALUE="S">';
        } else {
            echo '<input type="hidden" name="service_short_name" VALUE="100">';
            echo '<input type="hidden" name="scope" VALUE="P">';
        }
        echo '
<tr><td><a href="#" title="' . _('Automatically extract this keyword?') . '">' . _('Enabled') . ':</a> </td>
<td><input type="CHECKBOX" NAME="is_used" VALUE="1" CHECKED></td></tr>';
        if ($su) {
            echo '<tr><td><a href="#" title="' . _('Force reference pattern creation') . '">' . _('Bypass filters <em>(Site administrators only)</em>:') . '</a> </td><td><input type="CHECKBOX" NAME="force"></td></tr>';
        }
        echo '
</table>
<P><INPUT type="submit" name="Create" value="' . _('Create') . '">
</form>
<p><font color="red">*</font>: ' . _('fields required') . '</p>
';
    }

    public function edit()
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');

        $purifier = Codendi_HTMLPurifier::instance();

        $pm      = ProjectManager::instance();
        $project = $pm->getProject($group_id);

        $url        = '/project/admin/reference.php?group_id=' . urlencode($group_id);
        $csrf_token = new \CSRFSynchronizerToken($url);

        $refid = $request->get('reference_id');

        if (! $refid) {
            exit_error(
                _('Error'),
                _('A parameter is missing, please press the "Back" button and complete the form')
            );
        }

        $ref = $this->reference_manager->loadReference($refid, $group_id);

        if (! $ref) {
            echo '<p class="alert alert-error"> ' . _('This reference does not exist') . '</p>';

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
            [
                'reference'     => $ref,
                'can_be_edited' => &$can_be_edited,
            ]
        );
        $ro = ! $can_be_edited || ($ref->isSystemReference() && $ref->getGroupId() != 100);
        if ($ro) {
            $star = "";
        }

        echo '
<h3>' . _('Edit reference pattern') . '</h3>
<form name="form_create" method="post" action="' . $url . '">
<input type="hidden" name="action" VALUE="do_edit">
' . $csrf_token->fetchHTMLInput() . '
<input type="hidden" name="view" VALUE="browse">
<input type="hidden" name="group_id" VALUE="' . $purifier->purify($group_id) . '">
<input type="hidden" name="reference_id" VALUE="' . $purifier->purify($refid) . '">

<table width="100%" cellspacing=0 cellpadding=3 border=0>
<tr><td width="10%"><a href="#" title="' . _('Keyword that will trigger a reference creation') . '">' . _('Keyword') . ':</a>' . $star . '</td>
<td>';
        if ($ro) {
            echo $purifier->purify($ref->getKeyWord());
        } else {
            echo '<input type="text" name="keyword" size="25" maxlength="25" value="' . $purifier->purify($ref->getKeyWord()) . '">';
        }
        echo '</td></tr>';
        echo '
<tr><td><a href="#" title="' . _('This description will be displayed in a tooltip above reference.') . '">' . _('Description') . '</a>:&nbsp;</td>
<td>';
        if ($ro) {
            if ($ref->getDescription() == "reference_" . $ref->getKeyWord() . "_desc_key") {
                echo $purifier->purify($GLOBALS['Language']->getOverridableText('project_reference', $ref->getDescription()));
            } else {
                echo $purifier->purify($ref->getDescription());
            }
        } else {
            echo '<input type="text" name="description" size="70" maxlength="255" value="' . $purifier->purify($ref->getDescription()) . '">';
        }
        echo '</td></tr>';
        echo '
<tr><td><a href="#" title="' . _('Specify the nature of the reference, or Other if any.') . '">' . _('Nature') . '</a>:&nbsp;</td>
<td>';
        if ($ro) {
            echo $purifier->purify($ref->getNature());
        } else {
            echo '<select name="nature" >';
            foreach ($this->nature_collection->getNatures() as $nature_key => $nature_desc) {
                if ($nature_desc->user_can_create_ref_with_nature) {
                    if ($ref->getNature() == $nature_key) {
                        $selected = 'selected="selected"';
                    } else {
                        $selected = '';
                    }
                    echo '<option value="' . $purifier->purify($nature_key) . '" ' . $selected . '>' . $purifier->purify($nature_desc->label) . '</option>';
                }
            }
            echo '</select>';
        }
        echo '</td></tr>';
        echo '
<tr><td><a href="#" title="' . _('URL pointed by the reference') . '">' . _('Link') . '</a>:' . $star . '</td>
<td>';
        if ($ro) {
            echo $purifier->purify($ref->getLink());
        } else {
            echo '<input type="text" name="link" size="70" maxlength="255" value="' . $purifier->purify($ref->getLink()) . '"> ';
            echo help_button('project-admin.html#creating-or-updating-a-reference-pattern');
        }
        echo '</td></tr>';
        if ($group_id == 100) {
            echo '
<tr><td><a href="#" title="' . _('If the reference pattern is specific to one service, select it here') . '">' . _('Bound to service') . '</a>:</td>
<td>';
            // Get list of services
            $result          = db_query("SELECT * FROM service WHERE group_id=100 ORDER BY `rank`");
            $serv_label      = [];
            $serv_short_name = [];
            while ($serv = db_fetch_array($result)) {
                $label = $serv['label'];
                if ($label == "service_" . $serv['short_name'] . "_lbl_key") {
                    $label = $GLOBALS['Language']->getOverridableText('project_admin_editservice', $label);
                }
                $serv_short_name[] = $serv['short_name'];
                $serv_label[]      = $label;
            }
            if ($ro) {
                echo $purifier->purify($ref->getServiceShortName());
            } else {
                echo html_build_select_box_from_arrays($serv_short_name, $serv_label, "service_short_name", $ref->getServiceShortName());
            }
            echo '</td></tr>';
            echo '
<tr><td><a href="#" title="' . _('Scope of the reference pattern: project only or system-wide') . '">' . _('Scope') . ':</a></td>
<td><FONT size="-1">' . ($ref->getScope() == 'S' ? _('system') : _('project')) . '</FONT></td></tr>';
        }
        echo '
<tr><td><a href="#" title="' . _('Automatically extract this keyword?') . '">' . _('Enabled') . ':</a> </td>
<td><input type="CHECKBOX" NAME="is_used" VALUE="1"' . ($ref->isActive() ? " CHECKED" : '') . '></td></tr>';
        if ($su) {
            echo '<tr><td><a href="#" title="' . _('Force reference pattern creation') . '">'
                      . _('Bypass filters <em>(Site administrators only)</em>:') . '</a> </td>
                       <td><input type="CHECKBOX" NAME="force"></td></tr>';
        }
        echo '
</table>

<P><INPUT type="submit" name="Create" value="' . _('Update') . '">
</form>';
        if (! $ro) {
            echo '<p>' . $star . ': ' . _('fields required') . '</p>';
        }
    }
}
