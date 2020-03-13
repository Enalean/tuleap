<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Written for Codendi by Stephane Bouhet
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

require_once __DIR__ . '/../include/pre.php';


$gf = new GroupFactory();
?>
<html>
<head>
<title><?php echo $Language->getText('tracker_group_selection', 'project_sel'); ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo util_get_css_theme(); ?>">
<script language="JavaScript">

function doSelection(form) {
    if ( form.group_id.value != "" ) {
        window.opener.document.<?php echo preg_replace('/[^a-z0-9\$_]/', '', $request->get('opener_form')); ?>.<?php echo preg_replace('/[^a-z0-9\$_]/', '', $request->get('opener_field')); ?>.value = form.group_id.value;
    }
    close();
}

function onChangeMemberFilter() {
    window.location = "/tracker/group_selection.php?opener_form=form_create&opener_field=group_id_template&filter=member";
}

function onChangeAllFilter() {
    window.location = "/tracker/group_selection.php?opener_form=form_create&opener_field=group_id_template&filter=all";
}

</script>
</head>
<body class="bg_help">
<center>
<form name="form_selection">
<table border="0" cellspacing="0" cellpadding="5">
  <tr valign="center">
    <td colspan="2" align="center">
<select name="group_id" size="8">
<?php
    $filter = $request->get('filter');
if ($filter == "member") {
    $results = $gf->getMemberGroups();
} else {
    $results = $gf->getAllGroups();
}
    $hp = Codendi_HTMLPurifier::instance();
while ($groups_array = db_fetch_array($results)) {
    echo '<option value="' . (int) $groups_array["group_id"] . '">' . $hp->purify(html_entity_decode($groups_array["group_name"])) . '</option>';
}

?>
</select>
    </td>
  </tr>
  <tr>  
    <td><input type="radio" name="radiobutton" value="radiobutton"<?php if ($filter == "member") {
        echo " checked";
                                                                  } ?> onClick="onChangeMemberFilter()"> <?php echo $Language->getText('tracker_group_selection', 'my_proj'); ?></td>
    <td><input type="radio" name="radiobutton" value="radiobutton"<?php if ($filter == "all") {
        echo " checked";
                                                                  } ?> onClick="onChangeAllFilter()"> <?php echo $Language->getText('tracker_group_selection', 'all_proj'); ?></td>
  </tr>
  <tr>
    <td colspan="2" align="center">
        <input type="button" name="selection" value="<?php echo $Language->getText('global', 'select'); ?>" onClick="doSelection(form_selection)">
    </td>
  </tr>
</table>

</form>
</center>
</body>
</html>
