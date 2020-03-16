<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

?>
<html>
<head>
<title><?php echo $Language->getText('tracker_selection', 'tracker_sel') ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo util_get_css_theme(); ?>">
<script language="JavaScript">

function doSelection(form) {
    if ( form.artifact_type_id.value != "" ) {
        window.opener.document.<?php echo preg_replace('/[^a-z0-9\$_]/', '', $request->get('opener_form')); ?>.<?php echo preg_replace('/[^a-z0-9\$_]/', '', $request->get('opener_field')); ?>.value = form.artifact_type_id.value;
    }
    close();
}

</script>
</head>
<body class="bg_help">
<center>
<form name="form_selection">
<table>
<tr valign="center"><td>
<div align="center">
<?php
    //    get the Group object
    $group_id = $request->getValidated('group_id', 'GroupId');
    $pm = ProjectManager::instance();
    $group = $pm->getProject($group_id);
if (!$group || !is_object($group) || $group->isError()) {
    exit_no_group();
}

        $count = 0;
    $atf = new ArtifactTypeFactory($group);
    $trackers_array = $atf->getArtifactTypesFromId($group_id);
if ($trackers_array !== false) {
           echo '<select name="artifact_type_id" size="5">';
           $hp = Codendi_HTMLPurifier::instance();

    foreach ($trackers_array as $tracker) {
        echo '<option value="' . (int) $tracker->getId() . '">' . $hp->purify($tracker->getName()) . '</option>';
        $count ++;
    }
}

?>
<?php if ($count > 0) { ?>
</select>
</td>
<td>
<input type="button" name="selection" value="Select" onClick="doSelection(form_selection)">
<?php } else { ?>
<b><?php echo $Language->getText('tracker_selection', 'no_tracker_available')?></b>
<br><br><input type="button" value="<?php echo $Language->getText('global', 'btn_close') ?>" onClick="window.close()">
</td>
<td>
<?php } ?>
</div>
</td></tr>
<table>
</form>
</center>
</body>
</html>
