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
        $html  = '';
        $html .= '<HR SIZE="1" NoShade><A href="/file/showfiles.php?group_id='.$this->getGroupId().'">';
        $html .= $GLOBALS['Response']->getImage("ic/file.png", array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','files')));
        $html .= ' '.$GLOBALS['Language']->getText('include_project_home','file_releases').'</A>';
        $html .= ' ( '.$GLOBALS['Language']->getText('include_project_home','packages',count($this->_getPackagesForUser(user_getid()))).' )';
        return $html;
    }
    /**
    * getSummaryPageContent
    *
    * Return the text to display on the summary page
    * @return arr[title], arr[content]
    */
    function getSummaryPageContent() {
        $ret = array(
            'title' => $GLOBALS['Language']->getText('include_project_home','latest_file_releases'),
            'content' => ''
        );
        $packages = $this->_getPackagesForUser(user_getid());
        if (count($packages)) {
            $ret['content'] .= '
                <table cellspacing="1" cellpadding="5" width="100%" border="0">
                    <tr class="boxitem">
                        <td align="left"">
                            '.$GLOBALS['Language']->getText('include_project_home','package').'
                        </td>
                        <td align="center">
                            '.$GLOBALS['Language']->getText('include_project_home','version').'
                        </td>
                        <td align="center">
                            '.$GLOBALS['Language']->getText('include_project_home','notes').'
                        </td>
                        <td align="center">
                            '.$GLOBALS['Language']->getText('include_project_home','download').'
                        </td>
                    </tr>
            ';
            foreach($packages as $package) {
                $ret['content'] .= '
                  <TR class="boxitem" ALIGN="center">
                  <TD ALIGN="left">
                  <B>' . $package['package_name']. '</B></TD>';
                // Releases to display
                $ret['content'] .= '<TD>'. $package['release_name'] .'
                  </TD>
                  <TD align="center"><A href="/file/shownotes.php?group_id=' . $this->getGroupId() . '&release_id=' . $package['release_id'] . '">';
                $ret['content'] .= $GLOBALS['HTML']->getImage("ic/manual16b.png",array('alt'=>$GLOBALS['Language']->getText('include_project_home','release_notes')));
                $ret['content'] .= '</A> - <A HREF="/file/filemodule_monitor.php?filemodule_id=' .	$package['package_id'] . '">';
                $ret['content'] .= $GLOBALS['HTML']->getImage("ic/mail16b.png",array('alt'=>$GLOBALS['Language']->getText('include_project_home','monitor_pack')));
                $ret['content'] .= '</A>
                  </TD>
                  <TD align="center"><A HREF="/file/showfiles.php?group_id=' . $this->getGroupId() . '&release_id=' . $package['release_id'] . '">'.$GLOBALS['Language']->getText('include_project_home','download').'</A></TD></TR>';
            }
            $ret['content'] .= '</table>';
        } else {
            $ret['content'] .= '<b>'. $GLOBALS['Language']->getText('include_project_home','no_files_released') .'</b>';
        }
        $ret['content'] .= '
            <div align="center">
                <a href="/file/showfiles.php?group_id='.$this->getGroupId().'">['.$GLOBALS['Language']->getText('include_project_home','view_all_files').']</A>
            </div>
        ';
        return $ret;
    }
    /**
    * _getPackagesForUser
    * 
    * return the packages the user can see
    *
    * @param  user_id  
    */
    function _getPackagesForUser($user_id) {
        $packages = array();
        $sql="SELECT frs_package.package_id,frs_package.name AS package_name,frs_release.name AS release_name,frs_release.release_id AS release_id,frs_release.release_date AS release_date ".
        "FROM frs_package,frs_release ".
        "WHERE frs_package.package_id=frs_release.package_id ".
        "AND frs_package.group_id='". $this->getGroupId() ."' ".
        "AND frs_release.status_id=1 ".
        "ORDER BY frs_package.rank,frs_package.package_id,frs_release.release_date DESC, frs_release.release_id DESC";
        $res_files = db_query($sql);
        $rows_files = db_numrows($res_files);
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
                        $authorized=permission_is_authorized('RELEASE_READ',$release_id, $user_id ,$this->getGroupId());
                    } else {  
                        $authorized=permission_is_authorized('PACKAGE_READ',$package_id, $user_id ,$this->getGroupId());
                    }
                    if ($authorized) {
                        $packages[] = array(
                            'package_name' => db_result($res_files,$f,'package_name'),
                            'release_name' => db_result($res_files,$f,'release_name'),
                            'release_id'   => $release_id,
                            'package_id'   => $package_id,
                        );
                        $package_displayed[$package_id] = true;
                     }
                }
            }
        }
        return $packages;
    }
}
?>
