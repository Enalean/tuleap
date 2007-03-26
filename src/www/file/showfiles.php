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
require_once ('common/frs/FileModuleMonitorFactory.class.php');
require_once ('common/permission/PermissionsManager.class.php');
require_once ('common/include/UserManager.class.php');
$Language->loadLanguageMsg('file/file');

$GLOBALS['HTML']->includeJavascriptFile("/scripts/prototype/prototype.js");

define("FRS_EXPANDED_ICON", util_get_image_theme("ic/toggle_minus.png"));
define("FRS_COLLAPSED_ICON", util_get_image_theme("ic/toggle_plus.png"));

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
$packages = array();
$num_packages = 0;
// Retain only packages the user is authorized to access, or packages containing releases the user is authorized to access...
$res = $frspf->getFRSPackagesFromDb($group_id);

foreach ($res as $package) {
    if ($frspf->userCanRead($group_id, $package->getPackageID(), user_getid())) {
        if (isset ($release_id)) {
            $row3 = & $frsrf->getFRSReleaseFromDb($release_id);
        }
    
        if (!isset ($release_id) || $row3->getPackageID() == $package->getPackageID()) {
            $packages[$package->getPackageID()] = $package;
            $num_packages++;
        }
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
    if (user_ismember($group_id,'R2')) {
        echo '<p><a href="admin/package.php?func=add&amp;group_id='. $group_id .'">['. $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p') .']</a></p>';
    }
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

?>
<SCRIPT language="JavaScript">
<!--
function showConfirmDownload(group_id,file_id,filename) {
    url = "/file/confirm_download.php?popup=1&group_id=" + group_id + "&file_id=" + file_id + "&filename=" + filename;
    wConfirm = window.open(url,"confirm","width=520,height=450,resizable=1,scrollbars=1");
    wConfirm.focus();
}

function download(group_id,file_id,filename) {
    url = "<?php 
        $p =& project_get_object($group_id);
        if ($p->usesService('file')) {
            echo $p->services['file']->getUrl("");
        }
    ?>/file/download.php/" + group_id + "/" + file_id +"/"+filename;
    wConfirm.close();
    self.location = url;
    
}

function toggle_package(package_id) {
    Element.toggle(package_id);
    toggle_image(package_id);
} 

function toggle_release(package_id, release_id) {
    $A(packages[package_id][release_id]).each(function(file_id) {
        // toggle the content of the release (the files)
        Element.toggle(package_id + release_id + file_id);
    }); 
    toggle_image(package_id + release_id);
}

function toggle_image(image_id) {
    var img_element = $('img_' + image_id);
    if (img_element.src.indexOf('<?php echo FRS_COLLAPSED_ICON; ?>') != -1) {
        img_element.src = '<?php echo FRS_EXPANDED_ICON; ?>';
    } else {
        img_element.src = '<?php echo FRS_COLLAPSED_ICON; ?>';
    }
}

-->
</SCRIPT>
<?
}
// get unix group name for path
$group_unix_name = group_getunixname($group_id);

$proj_stats['packages'] = $num_packages;
$pm = & PermissionsManager :: instance();
$fmmf =& new FileModuleMonitorFactory();
 
$javascript_packages_array = array();
 
if (!$pv && user_ismember($group_id,'R2')) {
    echo '<p><a href="admin/package.php?func=add&amp;group_id='. $group_id .'">['. $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p') .']</a></p>';
}
// Iterate and show the packages
while (list ($package_id, $package) = each($packages)) {
    $can_see_package = false;
    if ($package->isActive()) {
        $emphasis = 'strong';
        $can_see_package = true;
    } else {
        $emphasis = 'em';
        if (user_ismember($group_id,'R2')) {
            $can_see_package = true;
        }
    }
    if ($can_see_package) {
        print '<fieldset class="package">';
        print '<legend>';
        if (!$pv) {
            print '<a href="#" onclick="javascript:toggle_package(\'p_'.$package_id.'\'); return false;" /><img src="'.FRS_EXPANDED_ICON.'" id="img_p_'.$package_id.'" /></a>&nbsp;';
        }
        print " <$emphasis>". $package->getName() ."</$emphasis>";
        if (!$pv) {
            if (user_ismember($group_id,'R2')) {
                print '     <a href="admin/package.php?func=edit&amp;group_id='. $group_id .'&amp;id=' . $package_id . '" title="'. htmlentities($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), ENT_QUOTES) .'">';
                print '       '. $GLOBALS['HTML']->getImage('ic/edit.png');
                print '     </a>';
                //print '     <a href="admin/package.php?func=delete&amp;group_id='. $group_id .'&amp;id=' . $package_id . '">'. $GLOBALS['HTML']->getImage('ic/trash.png') .'</a>';
            }
            print ' &nbsp; ';
            print '  <a href="filemodule_monitor.php?filemodule_id=' . $package_id . '">';
            if ($fmmf->isMonitoring($package_id)) {
                print '<img src="'.util_get_image_theme("ic/notification_stop.png").'" alt="'.$Language->getText('file_showfiles', 'stop_monitoring').'" title="'.$Language->getText('file_showfiles', 'stop_monitoring').'" />';
            } else {
                print '<img src="'.util_get_image_theme("ic/notification_start.png").'" alt="'.$Language->getText('file_showfiles', 'start_monitoring').'" title="'.$Language->getText('file_showfiles', 'start_monitoring').'" />';
            }
            print '  </a>';
        }
        print '</legend>';
        if (!$package->isActive()) {
            //TODO i18n
            print '<div style="text-align:center"><em>This package is inactive.</em></div>';
        }
        // get the releases of the package
        // Order by release_date and release_id in case two releases
        // are published the same day
        $res_release = $frsrf->getFRSReleasesFromDb($package_id, null, $group_id);
        $num_releases = count($res_release);
    
        if (!isset ($proj_stats['releases']))
            $proj_stats['releases'] = 0;
        $proj_stats['releases'] += $num_releases;
    
        $javascript_releases_array = array();
        print '<div id="p_'.$package_id.'">';
        if (!$pv && user_ismember($group_id,'R2')) {
            echo '<p><a href="admin/release.php?func=add&amp;group_id='. $group_id .'&amp;package_id='. $package_id .'">['. $GLOBALS['Language']->getText('file_admin_editpackages', 'add_releases') .']</a></p>';
        }
        if (!$res_release || $num_releases < 1) {
            print '<B>' . $Language->getText('file_showfiles', 'no_releases') . '</B>' . "\n";
        } else {
            $cpt_release = 0;
            // iterate and show the releases of the package
            foreach ($res_release as $package_release) {
                $can_see_release = false;
                if ($frsrf->userCanRead($group_id, $package_id, $package_release->getReleaseID(), user_getid())) {
                    if ($package_release->isActive()) {
                        $emphasis = 'strong';
                        $can_see_release = true;
                    } else {
                        $emphasis = 'em';
                        if (user_ismember($group_id,'R2')) {
                            $can_see_release = true;
                        }
                    }
                }
                if ($can_see_release) {
                    
                    $permission_exists = $pm->isPermissionExist($package_release->getReleaseID(), 'RELEASE_READ');
                    
                    // Highlight the release if one was chosen
                    if (isset ($release_id) && ($release_id == $package_release->getReleaseID())) {
                        $bgcolor = 'boxitemalt';
                    } else {
                        $bgcolor = 'boxitem';
                    }
                    print '<table width="100%" class="release">';
                    print ' <TR id="p_'.$package_id.'r_'.$package_release->getReleaseID().'">';
                    print '  <TD>';
                    if (!$pv) {
                        print '<a href="#" onclick="javascript:toggle_release(\'p_'.$package_id.'\', \'r_'.$package_release->getReleaseID().'\'); return false;" /><img src="'.FRS_EXPANDED_ICON.'" id="img_p_'.$package_id.'r_'.$package_release->getReleaseID().'" /></a>';
                    }
                    print "     <$emphasis>". $package_release->getName() . "</$emphasis>";
                    if (!$pv) {
                        if (user_ismember($group_id,'R2')) {
                            print '     <a href="admin/release.php?func=edit&amp;group_id='. $group_id .'&amp;package_id='. $package_id .'&amp;id=' . $package_release->getReleaseID() . '" title="'. htmlentities($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), ENT_QUOTES) .'">'. $GLOBALS['HTML']->getImage('ic/edit.png') .'</a>';
                        }
                        print ' &nbsp; ';
                        print '     <a href="shownotes.php?release_id=' . $package_release->getReleaseID() . '"><img src="'.util_get_image_theme("ic/text.png").'" alt="'.$Language->getText('file_showfiles', 'read_notes').'" title="'.$Language->getText('file_showfiles', 'read_notes').'" /></a>';
                    }
                    print '  </td>';
                    print ' <td style="text-align:center">';
                    if (!$package_release->isActive()) {
                        print '<em>This release is inactive.</em>';
                    } 
                    print '</td> ';
                    print '  <TD class="release_date">' . format_date("Y-m-d", $package_release->getReleaseDate()) . '';
                    if (!$pv && user_ismember($group_id,'R2')) {
                        print ' <a href="admin/release.php?func=delete&amp;group_id='. $group_id .'&amp;package_id='. $package_id .'&amp;id=' . $package_release->getReleaseID() . '" title="'. htmlentities($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), ENT_QUOTES) .'" onclick="return confirm(\''. htmlentities($GLOBALS['Language']->getText('file_admin_editreleases', 'warn'), ENT_QUOTES) .'\');">'. $GLOBALS['HTML']->getImage('ic/trash.png') .'</a>';
                    }
                    print '</TD></TR>' . "\n";
                    print '</table>';
                    
                    // get the files in this release....
                    $res_file = $frsff->getFRSFileInfoListByReleaseFromDb($package_release->getReleaseID());
                    $num_files = count($res_file);
        
                    if (!isset ($proj_stats['files']))
                        $proj_stats['files'] = 0;
                    $proj_stats['files'] += $num_files;
        
                    $javascript_files_array = array();
                    if (!$res_file || $num_files < 1) {
                        print '<span class="files" id="p_'.$package_id.'r_'.$package_release->getReleaseID().'f_0"><B>' . $Language->getText('file_showfiles', 'no_files') . '</B></span>' . "\n";
                        $javascript_files_array[] = "'f_0'";
                    } else {
                        $javascript_files_array[] = "'f_0'";
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
                        
                        print '<span class="files" id="p_'.$package_id.'r_'.$package_release->getReleaseID().'f_0">';
                        
                        $title_arr = array ();
                        $title_arr[] = $Language->getText('file_admin_editreleases', 'filename');
                        $title_arr[] = $Language->getText('file_showfiles', 'size');
                        $title_arr[] = $Language->getText('file_showfiles', 'd_l');
                        $title_arr[] = $Language->getText('file_showfiles', 'arch');
                        $title_arr[] = $Language->getText('file_showfiles', 'type');
                        $title_arr[] = $Language->getText('file_showfiles', 'date');
                        echo html_build_list_table_top($title_arr, false, false, true, null, "files_table") . "\n";
                        
                        // colgroup is used here in order to avoid table resizing when expand or collapse files, with CSS properties.
                        echo '<colgroup>';
                        echo ' <col class="frs_filename_col">';
                        echo ' <col class="frs_size_col">';
                        echo ' <col class="frs_downloads_col">';
                        echo ' <col class="frs_architecture_col">';
                        echo ' <col class="frs_filetype_col">';
                        echo ' <col class="frs_date_col">';
                        echo '</colgroup>';
        
                            // now iterate and show the files in this release....
                        foreach($res_file as $file_release) {
                            $filename = $file_release['filename'];
                            $list = split('/', $filename);
                            $fname = $list[sizeof($list) - 1];
                            print "\t\t" . '<TR id="p_'.$package_id.'r_'.$package_release->getReleaseID().'f_'.$file_release['file_id'].'" class="' . $bgcolor . '"><TD><B>';
                            
                            $javascript_files_array[] = "'f_".$file_release['file_id']."'";
                            
                            if (($package->getApproveLicense() == 0) && (isset ($GLOBALS['sys_frs_license_mandatory']) && !$GLOBALS['sys_frs_license_mandatory'])) {
                                // Allow direct download
                                print '<A HREF="/file/download.php/' . $group_id . "/" . $file_release['file_id'] . "/" . $file_release['filename'] . '" title="' . $file_release['file_id'] . " - " . $fname . '">' . $fname . '</A>';
                            } else {
                                // Display popup
                                print '<A HREF="javascript:showConfirmDownload(' . $group_id . ',' . $file_release['file_id'] . ',\'' . $file_release['filename'] . '\')" title="' . $file_release['file_id'] . " - " . $fname . '">' . $fname . '</A>';
                            }
                            $size_precision = 0;
                            if ($file_release['file_size'] < 1024) {
                                $size_precision = 2;
                            }
                            print '</B></TD>' . '<TD>' . file_utils_convert_bytes_to_kbytes($file_release['file_size'], $size_precision) . '</TD>' . '<TD>' . ($file_release['downloads'] ? $file_release['downloads'] : '0') . '</TD>' . '<TD>' . (isset ($processor[$file_release['processor']]) ? $processor[$file_release['processor']] : "") . '</TD>' . '<TD>' . (isset ($file_type[$file_release['type']]) ? $file_type[$file_release['type']] : "") . '</TD>' . '<TD>' . format_date("Y-m-d", $file_release['release_time']) . '</TD>' . '</TR>' . "\n";
                            if (!isset ($proj_stats['size']))
                                $proj_stats['size'] = 0;
                            $proj_stats['size'] += $file_release['file_size'];
                            if (!isset ($proj_stats['downloads']))
                                $proj_stats['downloads'] = 0;
                            $proj_stats['downloads'] += $file_release['downloads'];
                        }
                        print '</table>';
                        print '</span>';
                    }
                    $javascript_releases_array[] = "'r_".$package_release->getReleaseID()."': [" . implode(",", $javascript_files_array) . "]";
                    $cpt_release = $cpt_release + 1;
                }
            }
            if (!$cpt_release) {
                print '<B>' . $Language->getText('file_showfiles', 'no_releases') . '</B>' . "\n";
            }
        }
        print '</div>';
        print '</fieldset>';
        $javascript_packages_array[] = "'p_".$package_id."': {" . implode(",", $javascript_releases_array) . "}";
    }
}

if (!$pv) {
    $javascript_array = 'var packages = {';
    $javascript_array .= implode(",", $javascript_packages_array);
    $javascript_array .= '}';
    echo '<script language="javascript">'.$javascript_array.'</script>';
    
    ?>
    
    <script language="javascript">
    // at page loading, we only expand the first release of the package, and collapse the others
    var cpt_release;
    $H(packages).keys().each(function(package_id) {
        cpt_release = 0;
        $H(packages[package_id]).keys().each(function(release_id) {
            if (cpt_release > 0) {
                //Element.toggle(package_id + release_id);
                toggle_release(package_id, release_id); 
            }
            cpt_release++;
        });
    });
    </script>
    
    <?php
}
// project totals (statistics) 
if (isset ($proj_stats['size'])) {
	
    $total_size = file_utils_convert_bytes_to_kbytes($proj_stats['size']);

    print '<p>';
    print '<b>' . $Language->getText('file_showfiles', 'proj_total') . ': </b>';
    print $proj_stats['releases'].' '.$Language->getText('file_showfiles', 'stat_total_nb_releases').', ';
    print $proj_stats['files'].' '.$Language->getText('file_showfiles', 'stat_total_nb_files').', ';
    print $total_size.' '.$Language->getText('file_showfiles', 'stat_total_size').', ';
    print $proj_stats['downloads'].' '.$Language->getText('file_showfiles', 'stat_total_nb_downloads').'.';
    print '</p>';
}

file_utils_footer($params);
?>