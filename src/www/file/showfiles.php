<?php

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once ('pre.php');
require_once ('www/file/file_utils.php');
require_once ('common/frs/FRSPackageFactory.class.php');
require_once ('common/frs/FRSReleaseFactory.class.php');
require_once ('common/frs/FRSFileFactory.class.php');
require_once ('common/permission/PermissionsManager.class.php');
require_once ('common/include/UserManager.class.php');
$Language->loadLanguageMsg('file/file');

// LJ Now only for registered users on CodeX
if (!user_isloggedin()) {
	/*
	Not logged in
	*/
	exit_not_logged_in();
}

$authorized_user = false;
if (user_ismember($group_id, 'R2') || user_ismember($group_id, 'A')) {
	$authorized_user = true;
}

$frspf = new FRSPackageFactory();
$frsrf = new FRSReleaseFactory();
$frsff = new FRSFileFactory();
$num_packages = 0;
// Retain only packages the user is authorized to access, or packages containing releases the user is authorized to access...
$res = $frspf->getFRSPackagesFromDb($group_id, 1);

foreach ($res as $package) {
	if (isset ($release_id)) {
		$row3 = & $frsrf->getFRSReleaseFromDb($release_id);
	}

	if (!isset ($release_id) || $row3->getPackageID() == $package->getPackageID()) {
		$res_package[$package->getPackageID()] = $package->getName();
		$license_package[$package->getPackageID()] = $package->getApproveLicense();
		$num_packages++;
	}
}

$pv = isset ($pv) ? $pv : false;
$params = array (
	'title' => $Language->getText('file_showfiles',
	'file_p_for',
	group_getname($group_id
)), 'pv' => $pv);

file_utils_header($params);
if ($num_packages < 1) {
	echo '<h3>' . $Language->getText('file_showfiles', 'no_file_p') . '</h3><p>' . $Language->getText('file_showfiles', 'no_p_available');
	file_utils_footer($params);
	exit;
}

if ($pv) {
	echo '<h3>' . $Language->getText('file_showfiles', 'p_releases') . ':</h3>';
} else {
	echo "<TABLE width='100%'><TR><TD>";
	echo '<h3>' . $Language->getText('file_showfiles', 'p_releases') . ' ' . help_button('FileReleaseJargon.html') . '</h3>';
	echo "</TD>";
	echo "<TD align='left'> ( <A HREF='" . $PHP_SELF . "?group_id=$group_id&pv=1'><img src='" . util_get_image_theme("msg.png") . "' border='0'>&nbsp;" . $Language->getText('global', 'printer_version') . "</A> ) </TD>";
	echo "</TR></TABLE>";

	echo '<p>' . $Language->getText('file_showfiles', 'select_release') . '</p>';
}
?>
<SCRIPT language="JavaScript">
<!--
function showConfirmDownload(group_id,file_id,filename) {
    url = "/file/confirm_download.php?popup=1&group_id=" + group_id + "&file_id=" + file_id + "&filename=" + filename;
    wConfirm = window.open(url,"confirm","width=520,height=450,resizable=1,scrollbars=1");
    wConfirm.focus();
}

function download(group_id,file_id,filename) {
    url = "/file/download.php/" + group_id + "/" + file_id +"/"+filename;
    wConfirm.close();
    self.location = url;
    
}
-->
</SCRIPT>
<?

$title_arr = array ();
$title_arr[] = $Language->getText('file_admin_editpackagepermissions', 'p');
$title_arr[] = $Language->getText('file_showfiles', 'release_notes');
$title_arr[] = $Language->getText('file_admin_editreleases', 'filename');
$title_arr[] = $Language->getText('file_showfiles', 'size');
$title_arr[] = $Language->getText('file_showfiles', 'd_l');
$title_arr[] = $Language->getText('file_showfiles', 'arch');
$title_arr[] = $Language->getText('file_showfiles', 'type');
$title_arr[] = $Language->getText('file_showfiles', 'date');

// get unix group name for path
$group_unix_name = group_getunixname($group_id);

// print the header row
echo html_build_list_table_top($title_arr) . "\n";

$proj_stats['packages'] = $num_packages;
$pm = & PermissionsManager :: instance();
// Iterate and show the packages
while (list ($package_id, $package_name) = each($res_package)) {

	print '<TR><TD><B>' . $package_name;
	
	print '</B></TD><TD COLSPAN="7">&nbsp;</TD></TR>' . "\n";

	// get the releases of the package
	// Order by release_date and release_id in case two releases
	// are published the same day
	$res_release = $frsrf->getFRSReleasesFromDb($package_id, 1, $group_id);
	$num_releases = count($res_release);

	if (!isset ($proj_stats['releases']))
		$proj_stats['releases'] = 0;
	$proj_stats['releases'] += $num_releases;

	if (!$res_release || $num_releases < 1) {
		print '<TR><TD>&nbsp;</TD><TD><B>' . $Language->getText('file_showfiles', 'no_releases') . '</B></TD><TD COLSPAN="6">&nbsp;</TD></TR>' . "\n";
	} else {
		// iterate and show the releases of the package
		foreach ($res_release as $package_release) {
			$permission_exists = $pm->isPermissionExist($package_release->getReleaseID(), 'RELEASE_READ');
			
			// Highlight the release if one was chosen
			if (isset ($release_id) && ($release_id == $package_release->getReleaseID())) {
				$bgcolor = 'boxitemalt';
			} else {
				$bgcolor = 'boxitem';
			}

			print "\t" . '<TR class="' . $bgcolor . '"><TD>&nbsp;</TD><TD><B>' . '<A HREF="shownotes.php?release_id=' . $package_release->getReleaseID() . '" title="' . $package_release->getReleaseID() . " - " . $package_release->getName() . '">' . $package_release->getName() . '</A></B>';
			

			print '</TD><TD COLSPAN="5">&nbsp;</TD><TD>' . format_date("Y-m-d", $package_release->getReleaseDate()) . '</TD></TR>' . "\n";

			// get the files in this release....
			
			$res_file = $frsff->getFRSFileInfoListByReleaseFromDb($package_release->getReleaseID());
			$num_files = count($res_file);

			if (!isset ($proj_stats['files']))
				$proj_stats['files'] = 0;
			$proj_stats['files'] += $num_files;

			if (!$res_file || $num_files < 1) {
				print '<TR><TD COLSPAN=2>&nbsp;</TD><TD><B>' . $Language->getText('file_showfiles', 'no_files') . '</B></TD><TD COLSPAN="5">&nbsp;</TD></TR>' . "\n";
			} else {
				//get the file_type and processor type
				$q = "select * from frs_filetype";
				$res_filetype = db_query($q);
				while ($resrow = db_fetch_array($res_filetype)) {
					$file_type[$resrow['type_id']] = $resrow['name'];
				}

				$q = "select * from frs_processor";
				$res_processor = db_query($q);
				while ($resrow = db_fetch_array($res_processor)) {
					$processor[$resrow['processor_id']] = $resrow['name'];
				}

				// now iterate and show the files in this release....
				foreach($res_file as $file_release) {
					//$file_release = db_fetch_array($res_file);
					$filename = $file_release['filename'];
					$list = split('/', $filename);
					$fname = $list[sizeof($list) - 1];
					print "\t\t" . '<TR class="' . $bgcolor . '">' . '<TD COLSPAN=2>&nbsp;</TD>' . '<TD><B>';
					if (($license_package[$package_id] == 0) && (isset ($GLOBALS['sys_frs_license_mandatory']) && !$GLOBALS['sys_frs_license_mandatory'])) {
						// Allow direct download
						print '<A HREF="/file/download.php/' . $group_id . "/" . $file_release['file_id'] . "/" . $file_release['filename'] . '" title="' . $file_release['file_id'] . " - " . $fname . '">' . $fname . '</A>';
					} else {
						// Display popup
						print '<A HREF="javascript:showConfirmDownload(' . $group_id . ',' . $file_release['file_id'] . ',\'' . $file_release['filename'] . '\')" title="' . $file_release['file_id'] . " - " . $fname . '">' . $fname . '</A>';
					}
					print '</B></TD>' . '<TD>' . $file_release['file_size'] . '</TD>' . '<TD>' . ($file_release['downloads'] ? $file_release['downloads'] : '0') . '</TD>' . '<TD>' . (isset ($processor[$file_release['processor']]) ? $processor[$file_release['processor']] : "") . '</TD>' . '<TD>' . (isset ($file_type[$file_release['type']]) ? $file_type[$file_release['type']] : "") . '</TD>' . '<TD>' . format_date("Y-m-d", $file_release['release_time']) . '</TD>' . '</TR>' . "\n";
					if (!isset ($proj_stats['size']))
						$proj_stats['size'] = 0;
					$proj_stats['size'] += $file_release['file_size'];
					if (!isset ($proj_stats['downloads']))
						$proj_stats['downloads'] = 0;
					$proj_stats['downloads'] += $file_release['downloads'];
				}
			}
		}
	}

}

if (isset ($proj_stats['size'])) {
	print '<TR><TD COLSPAN="8">&nbsp;</TR>' . "\n";
	print '<TR><TD><B>' . $Language->getText('file_showfiles', 'proj_total') . ': </B></TD>' . '<TD><B><I>' . $proj_stats['releases'] . '</I></B></TD>' . '<TD><B><I>' . $proj_stats['files'] . '</I></B></TD>' . '<TD><B><I>' . $proj_stats['size'] . '</I></B></TD>' . '<TD><B><I>' . $proj_stats['downloads'] . '</I></B></TD>' . '<TD COLSPAN="3">&nbsp;</TD></TR>' . "\n";
}

print "</TABLE>\n\n";

file_utils_footer($params);
?>
