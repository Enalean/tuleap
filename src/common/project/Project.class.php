<?php

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 


//require_once('common/include/Error.class.php');
require_once('Group.class.php');
require_once('common/project/Service.class.php');
require_once('common/frs/ServiceFile.class.php');
require_once('common/svn/ServiceSVN.class.php');

require_once('ProjectManager.class.php');
require_once('ServiceNotAllowedForProjectException.class.php');

require_once 'UGroupManager.class.php';

/*

	An object wrapper for project data

	Extends the base object, Group

	Tim Perdue, August 28, 2000



	Example of proper use:

	//get a local handle for the object
	$pm = ProjectManager::instance();
    $grp = $pm->getProject($group_id);

	//now use the object to get the unix_name for the project
	$grp->getUnixName();

    @deprecated Use ProjectManager->getProject instead
*/

// see getProjectsDescFieldsInfos
function cmp($a, $b){
	if ($a["desc_rank"] == $b["desc_rank"]) {
        return 0;
    }
    return ($a["desc_rank"] < $b["desc_rank"]) ? -1 : 1;
}
 
function getProjectsDescFieldsInfos(){
	$sql = 'SELECT * FROM group_desc WHERE 1';
      
    $descfieldsinfos = array();
    if ($res = db_query($sql)) {
        while($data = db_fetch_array($res)) {
            $descfieldsinfos[] = $data;
        }
    }
    
	usort($descfieldsinfos, "cmp");
    return $descfieldsinfos;
}	


class Project extends Group implements PFO_Project {

    /**
     * The project is active
     */
    const STATUS_ACTIVE = 'A';
    
    /**
     * The project is pending
     */
    const STATUS_PENDING = 'P';
    
    /**
     * The project is public
     */
    const IS_PUBLIC = 1;
    
    
    var $project_data_array;

    // All data concerning services for this project
    var $service_data_array;
    var $use_service;
    var $cache_active_service;
    var $services;
    
    /**
     * @var array The classnames for services
     */
    protected $serviceClassnames;
    
    /*
		basically just call the parent to set up everything
                and set up services arrays
    */
    function Project($param) {
        $this->Group($param);
        
        //for right now, just point our prefs array at Group's data array
        //this will change later when we split the project_data table off from groups table
        $this->project_data_array = $this->data_array;
        
        // Get defined classname of services
        // TODO: Move this in a helper for performances pov (load of many projects)
        $this->serviceClassnames = array('file' => 'ServiceFile',
                            'svn'  => 'ServiceSVN');
        EventManager::instance()->processEvent(Event::SERVICE_CLASSNAMES, array('classnames' => &$this->serviceClassnames));
        
        // Get Service data
        $db_res = db_query("SELECT * FROM service WHERE group_id='" . db_es($this->group_id) . "' ORDER BY rank");
        $rows = db_numrows($db_res);
        if ($rows < 1) {
            $this->service_data_array = array();
        }
        for ($j = 0 ; $j < $rows ; $j++) {
            $res_row = db_fetch_array($db_res);
            $short_name = $res_row['short_name'];
            if (! $short_name) {
                $short_name = $j;
            }
            
            // needed for localisation
            $matches = array();
            if ($res_row['description'] == "service_" . $short_name . "_desc_key") {
                $res_row['description'] = $GLOBALS['Language']->getText('project_admin_editservice', $res_row['description']);
            } elseif (preg_match('/(.*):(.*)/', $res_row['description'], $matches)) {
                if ($GLOBALS['Language']->hasText($matches[1], $matches[2])) {
                    $res_row['description'] = $GLOBALS['Language']->getText($matches[1], $matches[2]);
                }
            }
            if ($res_row['label'] == "service_" . $short_name . "_lbl_key") {
                $res_row['label'] = $GLOBALS['Language']->getText('project_admin_editservice', $res_row['label']);
            } elseif (preg_match('/(.*):(.*)/', $res_row['label'], $matches)) {
                if ($GLOBALS['Language']->hasText($matches[1], $matches[2])) {
                    $res_row['label'] = $GLOBALS['Language']->getText($matches[1], $matches[2]);
                }
            }
            
            // Init Service object corresponding to given service
            try {
                $classname = $this->getServiceClassName($short_name);
                $s = new $classname($this, $res_row);
                $this->service_data_array[$short_name] = $res_row;
                if ($short_name) {
                    $this->use_service[$short_name] = $res_row['is_used'];
                }
                $this->services[$short_name] = $s;
                if ($res_row['is_active']) {
                    $this->cache_active_services[] = $s;
                }
            } catch (ServiceNotAllowedForProjectException $e) {
                //do nothing
            }
        }
    }
    
    /**
     * Return the name of the class to instantiate a service based on its short name
     *
     * @param string $short_name the short name of the service
     *
     * @return string
     */
    public function getServiceClassName($short_name) {
        $classname = 'Service';
        if (isset($this->serviceClassnames[$short_name])) {
            $classname = $this->serviceClassnames[$short_name];
        }
        return $classname;
    }

    /**
     * Return service corresponding to project
     *
     * @param String $service_name
     * 
     * @return Service
     */
    public function getService($service_name) {
        return $this->usesService($service_name) ? $this->services[$service_name] : null;
    }
    
    /**
     * 
     * @return array
     */
    public function getAllUsedServices() {
        $used_services = array();
        foreach($this->use_service as $service_name => $is_service_used) {
            if($is_service_used) {
                $used_services[] = $service_name;
            }
        }
        
        return $used_services;
    }
    
    public function getActiveServices() {
        return $this->cache_active_services;
    }
    
    function usesHomePage() {
        return isset($this->use_service['homepage']) && $this->use_service['homepage'];
    }
    
    function usesAdmin() {
        return isset($this->use_service['admin']) && $this->use_service['admin'];
    }
    
    function usesSummary() {
        return isset($this->use_service['summary']) && $this->use_service['summary'];
    }

    function usesTracker() {
        return isset($this->use_service['tracker']) && $this->use_service['tracker'];
    }

    function usesCVS() {
        return isset($this->use_service['cvs']) && $this->use_service['cvs'];
    }

    function usesSVN() {
        return isset($this->use_service['svn']) && $this->use_service['svn'];
    }

    function usesDocman() {
        return isset($this->use_service['doc']) && $this->use_service['doc'];
    }

    function usesFile() {
        return isset($this->use_service['file']) && $this->use_service['file'];
    }

    //whether or not this group has opted to use mailing lists
    function usesMail() {
        return isset($this->use_service['mail']) && $this->use_service['mail'];
    }

    //whether or not this group has opted to use news
    function usesNews() {
        return isset($this->use_service['news']) && $this->use_service['news'];
    }

    //whether or not this group has opted to use discussion forums
    function usesForum() {
        return isset($this->use_service['forum']) && $this->use_service['forum'];
    }       

    //whether or not this group has opted to use surveys
    function usesSurvey() {
        return isset($this->use_service['survey']) && $this->use_service['survey'];
    }       

    //whether or not this group has opted to use wiki
    function usesWiki() {
        return isset($this->use_service['wiki']) && $this->use_service['wiki'];
    }   


    // Generic versions

    function usesService($service_short_name) {
        return isset($this->use_service[$service_short_name]) && $this->use_service[$service_short_name];
    }

    /*
        The URL for this project's home page
    */
    function getHomePage() {
        return $this->usesHomePage() ? $this->service_data_array['homepage']['link'] : '';
    }
    
    function getWikiPage(){
        return $this->service_data_array['wiki']['link'];
    }

    function getForumPage(){
        return $this->service_data_array['forum']['link'];
    }
    
    function getMailPage(){
        return $this->service_data_array['mail']['link'];
    }
    
    function getCvsPage(){
        return $this->service_data_array['cvs']['link'];
    }
    
    function getSvnPage(){
        return $this->service_data_array['svn']['link'];
    }
    
    function getTrackerPage(){
        return $this->service_data_array['tracker']['link'];
    }
    
    /*

    Subversion and CVS settings

    */

    function cvsMailingList() {
        return $this->project_data_array['cvs_events_mailing_list'];
    }

    function getCVSMailingHeader() {
        return $this->project_data_array['cvs_events_mailing_header'];
    }

    function isCVSTracked() {
        return $this->project_data_array['cvs_tracker'];
    }

    function getCVSWatchMode() {
        return $this->project_data_array['cvs_watch_mode'];
    }

    function getCVSpreamble() {
        return $this->project_data_array['cvs_preamble'];
    }
    
    function isCVSPrivate() {
        return $this->project_data_array['cvs_is_private'];
    }

    function getSVNMailingHeader() {
        return $this->project_data_array['svn_events_mailing_header'];
    }

    function isSVNTracked() {
        return $this->project_data_array['svn_tracker'];
    }

    function isSVNMandatoryRef() {
        return $this->project_data_array['svn_mandatory_ref'];
    }
    
    function getSVNpreamble() {
        return $this->project_data_array['svn_preamble'];
    }

    function isSVNPrivate() {
        // TODO XXXX not implemented yet.
        return false;
    }
    
    function getSVNAccess() {
        return $this->project_data_array['svn_accessfile'];
    }

    function getProjectsCreatedFrom() {
        $sql = 'SELECT * FROM groups WHERE built_from_template = '. $this->getGroupId() ." AND status <> 'D'";
        $subprojects = array();
        if ($res = db_query($sql)) {
            while($data = db_fetch_array($res)) {
                $subprojects[] = $data;
            }
        }
        return $subprojects;
    }
    
    function getProjectsDescFieldsValue(){
    	$sql = 'SELECT group_desc_id, value FROM group_desc_value WHERE group_id='.$this->getGroupId() ;
        
        $descfieldsvalue = array();
        if ($res = db_query($sql)) {
            while($data = db_fetch_array($res)) {
                $descfieldsvalue[] = $data;
            }
        }
        
        return $descfieldsvalue;
    }
    
    function displayProjectsDescFieldsValue(){
    	$descfieldsvalue=$this->getProjectsDescFieldsValue();
    	$descfields = getProjectsDescFieldsInfos();
    	$hp = Codendi_HTMLPurifier::instance();
    	
    	for($i=0;$i<sizeof($descfields);$i++){
	
			$displayfieldname[$i]=$descfields[$i]['desc_name'];
			$displayfieldvalue[$i]='';
			for($j=0;$j<sizeof($descfieldsvalue);$j++){
				
				if($descfieldsvalue[$j]['group_desc_id']==$descfields[$i]['group_desc_id']){
					$displayfieldvalue[$i]=$descfieldsvalue[$j]['value'];
				}	
			}
			
			$descname=$displayfieldname[$i];
                        if (preg_match('/(.*):(.*)/', $descname, $matches)) {
                            if ($GLOBALS['Language']->hasText($matches[1], $matches[2])) {
                                $descname = $GLOBALS['Language']->getText($matches[1], $matches[2]);
                            }
                        }
			
			echo "<h3>".$hp->purify($descname,CODENDI_PURIFIER_LIGHT,$this->getGroupId())."</h3>";
			echo "<p>";
			echo ($displayfieldvalue[$i] == '') ? $GLOBALS['Language']->getText('global','none') : $hp->purify($displayfieldvalue[$i], CODENDI_PURIFIER_LIGHT,$this->getGroupId())  ;
			echo "</p>";
			
		}
    	
    }

    private function getUGroupManager() {
        return new UGroupManager();
    }

    /**
     * @return array of User admin of the project
     */
    public function getAdmins() {
        return $this->getUGroupManager()->getDynamicUGroupsMembers(Ugroup::PROJECT_ADMIN, $this->getID());
    }

    /**
     * @return array of User members of the project
     */
    public function getMembers() {
        return $this->getUGroupManager()->getDynamicUGroupsMembers(Ugroup::PROJECT_MEMBERS, $this->getID());
    }

    /**
     * Alias of @see getMembers()
     */
    public function getUsers() {
        return $this->getMembers();
    }
}
?>
