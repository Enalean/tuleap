<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('www/project/admin/permissions.php');    
require_once('www/file/file_utils.php');
require_once('common/mail/Mail.class.php');
require_once('www/forum/forum_utils.php');
require_once('common/frs/FRSFileFactory.class.php');
require_once('common/frs/FRSReleaseFactory.class.php');
require_once('common/frs/FRSPackageFactory.class.php');
require_once('common/frs/FileModuleMonitorFactory.class.php');
$Language->loadLanguageMsg('file/file');


/*

File release system rewrite, Tim Perdue, SourceForge, Aug, 2000


	Sorry this is a large, complex page but this is a very complex process


	If you pass just the group_id, you will be given a list of releases
	with the option to edit those releases or create a new release


	If you pass the group_id plus the package_id, you will get the list of 
		releases with just the releases of that package shown
*/







if (!user_ismember($group_id,'R2')) {
    exit_permission_denied();
}

$frspf = new FRSPackageFactory();
$frsrf = new FRSReleaseFactory();
$frsff = new FRSFileFactory();


    if (isset($func) && ($func == "delete_release") && $group_id) {
    /*
         Delete a release with all the files included
         Delete the corresponding row from the database
         Delete the corresponding directory from the server
    */
    $res = $frsrf->delete_release($group_id, $release_id);
    if ($res == 0) {
      $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases','rel_not_yours'));
    } else {
      $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editreleases','rel_del'));
    }
  } 
	/*

		Show existing releases and a form to create a new release

	*/
  file_utils_admin_header(array('title'=>$Language->getText('file_admin_editreleases','release_new_file_version'),
				   'help' => 'FileReleaseDelivery.html#ReleaseCreation'));


	/*

		Show a list of existing releases
		for this project so they can
		be edited in detail

	*/
	if ($package_id) {
		//narrow the list to just this package's releases
		$pkg_str = "AND frs_package.package_id='$package_id'";
	}

	if ($package_id) {
		//narrow the list to just this package's releases
		$res = $frsrf->getFRSReleasesInfoListFromDb($group_id, $package_id);
        $package =& $frspf->getFRSPackageFromDb($package_id); 
	}else{
		$res = $frsrf->getFRSReleasesInfoListFromDb($group_id);
	}
	$rows=count($res);
	if (!$res || $rows < 1) {
	  echo '<h4>'.$Language->getText('file_admin_editreleases','no_releases_defined',(($package_id)?$Language->getText('file_admin_editreleases','of_this_package').' ':'')).'</h4>';
	} else {
        echo '<h4>'.$Language->getText('file_admin_editreleases','your_release', $package->getName()).'</H4>';	    
		/*

			Show a list of releases
			For this project or package

		*/
		$title_arr=array();
		$title_arr[]=$Language->getText('file_admin_editreleases','release_name');
		$title_arr[]=$Language->getText('global','status');
		$title_arr[]=$Language->getText('file_admin_editreleases','delete');

        $url = '';
        $p =& project_get_object($group_id);
        if ($p->usesService('file')) {
            $url = $p->services['file']->getUrl('');
        }
		echo html_build_list_table_top ($title_arr);
		$i = 0;
		foreach($res as $result) {
		  echo '<TR class="'. util_get_alt_row_color($i) .'">'.
		    '<TD><FONT SIZE="-1"><A HREF="'. $url .'/file/admin/editrelease.php?release_id='. 
		    $result['release_id'] .'&group_id='. $group_id .'" title="'.$Language->getText('file_admin_editreleases','edit_this_release').'">'.
		    $result['release_name'] .'</A></TD>'.
		    '<TD><FONT SIZE="-1">'. $Language->getText('file_admin_editpackages',$result['status_name']) .'</TD>'.
		    '<TD align="center"><FONT SIZE="-1">'. 
		    '<a href="/file/admin/editreleases.php?func=delete_release&group_id='. $group_id .'&release_id='.$result['release_id'].'&package_id='.$package_id.'">'.
		    '<img src="'.util_get_image_theme("ic/trash.png").'" border="0" onClick="return confirm(\''.$Language->getText('file_admin_editreleases','warn').'\')"></a>'.'</TD>'.
		    '</TR>       ';
		    $i++;
		}
		echo '</TABLE><BR/>';
	}

	echo '<A HREF="createrelease.php?package_id='. 
                        $package_id .'&group_id='. $group_id .'"><B>['.$Language->getText('file_admin_editpackages','add_releases').']</B></A><BR/><BR/>';

file_utils_footer(array());




?>
