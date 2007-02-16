<?php
/**
* ServiceFile
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class ServiceFile extends Service {
    /**
    * getPublicArea
    * 
    * Return the link which will be displayed in public area in summary page
    */
    function getPublicArea() {
        $sql="SELECT frs_package.package_id,frs_package.name AS package_name,frs_release.name AS release_name,frs_release.release_id AS release_id,frs_release.release_date AS release_date ".
        "FROM frs_package,frs_release ".
        "WHERE frs_package.package_id=frs_release.package_id ".
        "AND frs_package.group_id='". $this->getGroupId() ."' ".
        "AND frs_release.status_id=1 ".
        "ORDER BY frs_package.rank,frs_package.package_id,frs_release.release_date DESC, frs_release.release_id DESC";
        $res_files = db_query($sql);
        $rows_files = db_numrows($res_files);
        $nb_packages = 0;
        if ($res_files && $rows_files >= 1) {
            for ($f=0; $f<$rows_files; $f++) {
                $package_id=db_result($res_files,$f,'package_id');
                $release_id=db_result($res_files,$f,'release_id');
                if (isset($package_displayed[$package_id]) && $package_displayed[$package_id]) {
                    //if ($package_id==db_result($res_files,($f-1),'package_id')) {
                    //same package as last iteration - don't show this release
                } else {
                    $authorized=false;
                    // check access.
                    if (permission_exist('RELEASE_READ', $release_id)) {
                        $authorized=permission_is_authorized('RELEASE_READ',$release_id ,user_getid(),$this->getGroupId());
                    } else {  
                        $authorized=permission_is_authorized('PACKAGE_READ',$package_id ,user_getid(),$this->getGroupId());
                    }
                    if ($authorized) {
                        $nb_packages++;
                    }
                }
            }
        }
        $html .= '<HR SIZE="1" NoShade><A href="/file/showfiles.php?group_id='.$this->getGroupId().'">';
        $html .= $GLOBALS['Response']->getImage("ic/file.png", array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','files')));
        $html .= ' '.$GLOBALS['Language']->getText('include_project_home','file_releases').'</A>';
        $html .= ' ( '.$GLOBALS['Language']->getText('include_project_home','packages',$nb_packages).' )';
        return $html;
    }
    
}
?>
