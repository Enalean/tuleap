<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * ServiceFile
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
        $hp = Codendi_HTMLPurifier::instance();
        $ret = array(
            'title' => $GLOBALS['Language']->getText('include_project_home','latest_file_releases'),
            'content' => ''
        );
        
        $packages = $this->_getPackagesForUser(user_getid());
        if (count($packages)) {
            $ret['content'] .= '
                <table cellspacing="1" cellpadding="5" width="100%" border="0">
                    <tr class="boxitem">
                        <td>
                            '.$GLOBALS['Language']->getText('include_project_home','package').'
                        </td>
                        <td>
                            '.$GLOBALS['Language']->getText('include_project_home','version').'
                        </td>
                        <td>
                            '.$GLOBALS['Language']->getText('include_project_home','download').'
                        </td>
                    </tr>
            ';
            require_once('FileModuleMonitorFactory.class.php');
            $fmmf =& new FileModuleMonitorFactory();
            foreach($packages as $package) {
                // the icon is different whether the package is monitored or not
                if ($fmmf->isMonitoring($package['package_id'])) {
                    $monitor_img = $GLOBALS['HTML']->getImage("ic/notification_stop.png",array('alt'=>$GLOBALS['Language']->getText('include_project_home', 'stop_monitoring'), 'title'=>$GLOBALS['Language']->getText('include_project_home', 'stop_monitoring')));
                } else {
                    $monitor_img = $GLOBALS['HTML']->getImage("ic/notification_start.png",array('alt'=>$GLOBALS['Language']->getText('include_project_home', 'start_monitoring'), 'title'=>$GLOBALS['Language']->getText('include_project_home', 'start_monitoring')));
                }
            
                $ret['content'] .= '
                  <TR class="boxitem">
                  <TD>
                    <B>' .  $hp->purify(util_unconvert_htmlspecialchars($package['package_name']), CODENDI_PURIFIER_CONVERT_HTML)  . '</B>&nbsp;
                    <a HREF="/file/filemodule_monitor.php?filemodule_id=' . $package['package_id'] . '">'.
                        $monitor_img . '     
                    </a>
                  </TD>';
                // Releases to display
                $ret['content'] .= '<TD>'.  $hp->purify($package['release_name'], CODENDI_PURIFIER_CONVERT_HTML)  .'&nbsp;<A href="/file/shownotes.php?group_id=' . $this->getGroupId() . '&release_id=' . $package['release_id'] . '">' .
                    $GLOBALS['HTML']->getImage("ic/text.png",array('alt'=>$GLOBALS['Language']->getText('include_project_home','release_notes'), 'title'=>$GLOBALS['Language']->getText('include_project_home','release_notes'))) . ' 
                  </TD>
                  <TD><A HREF="/file/showfiles.php?group_id=' . $this->getGroupId() . '&release_id=' . $package['release_id'] . '">'.$GLOBALS['Language']->getText('include_project_home','download').'</A></TD></TR>';
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
    
    private function getFRSPackageFactory() {
        require_once('FRSPackageFactory.class.php');
        return new FRSPackageFactory();
    }
    
    /**
    * _getPackagesForUser
    * 
    * return the packages the user can see
    *
    * @param  user_id  
    */
    function _getPackagesForUser($user_id) {
        $frspf = $this->getFRSPackageFactory();
        $packages = array();
        $sql="SELECT frs_package.package_id,frs_package.name AS package_name,frs_release.name AS release_name,frs_release.release_id AS release_id,frs_release.release_date AS release_date ".
        "FROM frs_package,frs_release ".
        "WHERE frs_package.package_id=frs_release.package_id ".
        "AND frs_package.group_id='". db_ei($this->getGroupId()) ."' ".
        "AND frs_release.status_id=' ".db_ei($frspf->STATUS_ACTIVE)."' ".
        "ORDER BY frs_package.rank,frs_package.package_id,frs_release.release_date DESC, frs_release.release_id DESC";
        $res_files = db_query($sql);
        $rows_files = db_numrows($res_files);
        if ($res_files && $rows_files >= 1) {
            for ($f=0; $f<$rows_files; $f++) {
                $package_id=db_result($res_files,$f,'package_id');
                $release_id=db_result($res_files,$f,'release_id');
                if ($frspf->userCanRead($this->getGroupId(), $package_id, $user_id)) {
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
        }
        return $packages;
    }
    function isRequestedPageDistributed(&$request) {
        return in_array($_SERVER['SCRIPT_NAME'], array(
            '/file/admin/release.php', 
            '/file/admin/frsajax.php', 
            '/file/download.php',
        ));
    }
}
?>
