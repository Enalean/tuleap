<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/permissions.php');
require($DOCUMENT_ROOT.'/file/file_utils.php');

// LJ Now only for registered users on CodeX
if (!user_isloggedin()) {
    /*
    Not logged in
    */
    exit_not_logged_in();
}

$authorized_user=false;
if (user_ismember($group_id,'R2') || user_ismember($group_id,'A')) {
    $authorized_user=true;
}
$num_packages=0;
$sql = "SELECT * FROM frs_package WHERE group_id='$group_id' AND status_id='1'";
$res = db_query( $sql );
// Retain only packages the user is authorized to access, or packages containing releases the user is authorized to access...
if (db_numrows($res)>0) {
    while ($row = db_fetch_array($res)) {
        $authorized=false;
        if (($authorized_user)||(permission_is_authorized('PACKAGE_READ',$row['package_id'],user_getid()))) {
            $authorized=true;
        } else {
            // Get corresponding releases and check access. 
            // When set, the release permission overwrite package permission
            $sql2= "SELECT * FROM frs_release WHERE status_id='1' AND package_id=".$row['package_id'];
            $res2=db_query( $sql2 );
            if (db_numrows($res2)>0) {
                while ($row2 = db_fetch_array($res2)) {
                    if (permission_exist('RELEASE_READ', $row2['release_id'])) {
                        if (permission_is_authorized('RELEASE_READ',$row2['release_id'],user_getid())) {
                            $authorized=true;
                            break;
                        }
                    }
                }
            }
        }
        
        if ($authorized) {
            $res_package[$row['package_id']]=$row['name'];
            $num_packages++;
        }
    }
}

file_utils_header(array('title'=>'Project Filelist'));

if ( $num_packages < 1) {
    echo '<h3>No File Packages</h3><p>There are no file packages available';
    file_utils_footer(array());
    exit;
}


echo '<h3>Package Releases '. help_button('FileReleaseJargon.html').'</h3>';
echo '<p>Select Release to see Release Notes and Change Log, &nbsp;';
echo 'Select File to request file download.</p>';

if (session_issecure()) {
    $url = "https://".$GLOBALS['sys_https_host'];
} else {
    $url = "http://".$GLOBALS['sys_default_domain'];
}
?>
<SCRIPT language="JavaScript">
<!--
function showConfirmDownload(group_id,file_id,filename) {
    url = "<?php echo $url; ?>/file/confirm_download.php?group_id=" + group_id + "&file_id=" + file_id + "&filename=" + filename;
    wConfirm = window.open(url,"confirm","width=450,height=360,resizable=1,scrollbars=1");
    wConfirm.focus();
}

function download(group_id,file_id,filename) {
    url = "<?php echo $url; ?>/file/download.php/" + group_id + "/" + file_id +"/"+filename;
    wConfirm.close();
    self.location = url;
    
}
-->
</SCRIPT>
<?
    $title_arr = array();
    $title_arr[] = 'Package';
    $title_arr[] = 'Release<BR>&amp; Notes';
    $title_arr[] = 'Filename';
    $title_arr[] = 'Size';
    $title_arr[] = 'D/L';
    $title_arr[] = 'Arch.';
    $title_arr[] = 'Type';
    $title_arr[] = 'Date';

    // get unix group name for path
    $group_unix_name=group_getunixname($group_id);

    // print the header row
    echo html_build_list_table_top($title_arr) . "\n";

    $proj_stats['packages'] = $num_packages;

    // Iterate and show the packages
    while (list($package_id, $package_name) = each($res_package)) {

	print '<TR><TD><B>'.$package_name;
        if ($authorized_user) {
            if (permission_exist('PACKAGE_READ',$package_id)) {
                print ' <a href="/file/admin/editpackagepermissions.php?package_id='.$package_id.
                    '&group_id='.$group_id.'"><img src="'.util_get_image_theme("ic/lock.png").'" border="0"></a>';
            }
        }
        print '</B></TD><TD COLSPAN="7">&nbsp;</TD></TR>'."\n";


	// get the releases of the package
	// Order by release_date and release_id in case two releases
	// are published the same day
	$sql	= "SELECT * FROM frs_release WHERE package_id='". $package_id . "' "
		. "AND status_id=1 ORDER BY release_date DESC, release_id DESC";
	$res_release = db_query( $sql );
	$num_releases = db_numrows( $res_release );

	$proj_stats['releases'] += $num_releases;

	if ( !$res_release || $num_releases < 1 ) {
		print '<TR><TD>&nbsp;</TD><TD><B>No Releases</B></TD><TD COLSPAN="6">&nbsp;</TD></TR>'."\n";
	} else {
		   // iterate and show the releases of the package
		for ( $r = 0; $r < $num_releases; $r++ ) {
			$package_release = db_fetch_array( $res_release );

                        // Check permissions for release, then package
                        $permission_exists = permission_exist('RELEASE_READ', $package_release['release_id']);
                        if (($permission_exists)&&(!$authorized_user)) {
                            if (!permission_is_authorized('RELEASE_READ',$package_release['release_id'],user_getid())) {
                                // Skip this release
                                continue;
                            } // else OK, display the release
                        } else if (!permission_is_authorized('PACKAGE_READ',$package_id,user_getid())) {
                                // Skip this release
                                continue;
                        } // else OK, display the release

			   // Highlight the release if one was chosen
			if ( $release_id == $package_release['release_id'] ) {
				$bgcolor = 'boxitemalt';
			} else {
				$bgcolor = 'boxitem';
			}

			print "\t" . '<TR class="'. $bgcolor .'"><TD>&nbsp;</TD><TD><B>'
				. '<A HREF="shownotes.php?release_id='.$package_release['release_id'].'">'
				. $package_release['name'] .'</A></B>';
                        if ($authorized_user) {
                            if ($permission_exists) {
                                print ' <a href="/file/admin/editreleasepermissions.php?release_id='.$package_release['release_id'].
                                    '&group_id='.$group_id.'&package_id='.$package_id.'"><img src="'.util_get_image_theme("ic/lock.png").'" border="0"></a>';
                            }
                        }

                        print '</TD><TD COLSPAN="5">&nbsp;</TD><TD>'
				. format_date("Y-m-d", $package_release['release_date'] ) .'</TD></TR>'."\n";

			   // get the files in this release....
			$sql = "SELECT frs_file.file_id AS file_id,"
				. "frs_file.filename AS filename,"
				. "frs_file.file_size AS file_size,"
				. "frs_file.release_time AS release_time,"
				. "frs_file.type_id AS type,"
				. "frs_file.processor_id AS processor,"
				. "frs_dlstats_filetotal_agg.downloads AS downloads "
				. "FROM frs_file LEFT JOIN frs_dlstats_filetotal_agg ON frs_dlstats_filetotal_agg.file_id=frs_file.file_id "
				. "WHERE release_id='". $package_release['release_id']."'";
			$res_file = db_query( $sql );
			$num_files = db_numrows( $res_file );

			$proj_stats['files'] += $num_files;

			if ( !$res_file || $num_files < 1 ) {
				print '<TR><TD COLSPAN=2>&nbsp;</TD><TD><B>No Files</B></TD><TD COLSPAN="5">&nbsp;</TD></TR>'."\n";
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
				for ( $f = 0; $f < $num_files; $f++ ) {
					$file_release = db_fetch_array( $res_file );
					$filename = $file_release['filename'];$list = split('/', $filename);
					$fname = $list[sizeof($list) - 1];
					print "\t\t" . '<TR class="' . $bgcolor .'">'
						. '<TD COLSPAN=2>&nbsp;</TD>'
						. '<TD><B><A HREF="javascript:showConfirmDownload('.$group_id.','.$file_release['file_id'].',\''.$file_release['filename'].'\')">'

						. $fname .'</A></B></TD>'
						. '<TD>'. $file_release['file_size'] .'</TD>'
						. '<TD>'. ($file_release['downloads'] ? $file_release['downloads'] : '0') .'</TD>'
						. '<TD>'. $processor[$file_release['processor']] .'</TD>'
						. '<TD>'. $file_type[$file_release['type']] .'</TD>'
						. '<TD>'. format_date( "Y-m-d", $file_release['release_time'] ) .'</TD>'
						. '</TR>' . "\n";

					$proj_stats['size'] += $file_release['file_size'];
					$proj_stats['downloads'] += $file_release['downloads'];
				}	
			}
		}
	}

}

if ( $proj_stats['size'] ) {
	print '<TR><TD COLSPAN="8">&nbsp;</TR>'."\n";
	print '<TR><TD><B>Project Totals: </B></TD>'
		. '<TD><B><I>' . $proj_stats['releases'] . '</I></B></TD>'
		. '<TD><B><I>' . $proj_stats['files'] . '</I></B></TD>'
		. '<TD><B><I>' . $proj_stats['size'] . '</I></B></TD>'
		. '<TD><B><I>' . $proj_stats['downloads'] . '</I></B></TD>'
		. '<TD COLSPAN="3">&nbsp;</TD></TR>'."\n";
}

print "</TABLE>\n\n";

file_utils_footer(array());


?>
