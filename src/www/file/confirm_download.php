<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
require_once __DIR__ . '/../include/pre.php';

$vGroupId = new Valid_GroupId();
$vGroupId->required();
$vFileId = new Valid_UInt('file_id');
$vFileId->required();
if ($request->valid($vGroupId) && $request->valid($vFileId)) {
    $group_id = $request->get('group_id');
    $file_id  = $request->get('file_id');
} else {
    exit_missing_param();
}

  $frsff = new FRSFileFactory();
  $frspf = new FRSPackageFactory();
  // Must have a group_id and file_id otherwise
  // we cannot do much
if (!$file_id || !$group_id) {
    exit_missing_param();
}

if (!$GLOBALS['sys_frs_license_mandatory']) {
    // Display license popup
    // This is useful when using a 'file #123' reference, that points to this script
    $res = $frspf->getFRSPackageByFileIdFromDb($file_id);
    if ($res !== null) {
        if ($res->getApproveLicense() == 0) {
            // Directly display file
            $location = 'Location: /file/download/' . urlencode($file_id);
            header($location);
            exit();
        }
    }
}

if ($request->exist('popup')) {
    $dlscript = 'opener.download';
    $cancelscript = 'window.close()';
} else {
    $dlscript = 'download_local';
    $cancelscript = 'history.back()';
}
?>
<html>
<head>
   <title><?php echo $Language->getText('file_confirm_download', 'download_agreement'); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<body>
<SCRIPT language="JavaScript">
<!--

function download_local(file_id) {
    url = "/file/download/" + file_id;
    self.location = url;
}
-->
</SCRIPT>
<table width="100%" height="100%" cellpadding="5" class="bg_confirmdownload">
<tr><td>
<span class="small">
<div align="center">
<?php

$exchange_policy_url = ForgeConfig::get('sys_exchange_policy_url');
if (! $exchange_policy_url) {
    $exchange_policy_url = 'javascript:;';
}

echo $Language->getText('file_confirm_download', 'download_explain', array($GLOBALS['sys_org_name'], $GLOBALS['sys_email_contact'], $exchange_policy_url));
?><br>

<br><br>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="normal">
  <tr> 
    <td> 
      <div align="center"><a href="javascript:<?php echo "$dlscript($file_id);"; ?>"><b><?php echo $Language->getText('file_confirm_download', 'agree'); ?></b></a></div>
    </td>
    <td> 
      <div align="center"><a href="javascript:<?php echo "$cancelscript"?>;"><b><?php echo $Language->getText('file_confirm_download', 'decline'); ?></b></a></div>
    </td>
  </tr>
<?php if (!$request->exist('popup')) {
    echo '<p>  <tr><td colspan="2" class="small"><a href="javascript:history.back();">' . $Language->getText('file_confirm_download', 'back') . '</a></td></tr>';
} ?>
</table>
</span>
</td></tr>
</table>
</body>
</html>
