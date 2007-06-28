<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2006. All rights reserved
 * 
 * 
 *
 * Reference Manager
 */

require_once('common/dao/ReferenceDao.class.php');
require_once('common/dao/CodexDataAccess.class.php');
require_once('common/include/Reference.class.php');
require_once('common/include/ReferenceInstance.class.php');
require_once('common/include/GroupFactory.class.php');


/**
 * Reference Manager
 * Performs all operations on references, including DB access (through ReferenceDAO)
 */
class ReferenceManager {
    
    /**
     * array of active Reference objects arrays of arrays, indexed by group_id, keyword, and num args. 
     * Example: $activeReferencesByProject[101]['art'][1] return the reference object for project 101, keyword 'art' and one argument.
     * @var array
     */
    var $activeReferencesByProject; 
    /**
     * array of Reference objects arrays indexed by group_id
     * Example: $activeReferencesByProject[101][1] return the first reference object for project 101
     * @var array
     */
    var $referencesByProject;
    var $referenceDao;
    var $reservedKeywords=array("art","artifact","doc","file","wiki","cvs","svn","news","forum","msg","cc","git","tracker","release","tag","thread","im","project","folder","plugin","img","commit","rev","revision","patch","bug","sr","task","proj","dossier"); //should be elsewhere?
    var $groupIdByName;

   
    function ReferenceManager() {
        $this->activeReferencesByProject = array();
    }

    function &instance() {
        static $_referencemanager_instance;
        if (!$_referencemanager_instance) {
            $_referencemanager_instance = new ReferenceManager();
        }
        return $_referencemanager_instance;
    }


    function &getReferencesByGroupId($group_id) {
        $p = false;
        if (isset($this->referencesByProject[$group_id])) {
            $p =& $this->referencesByProject[$group_id];
        } else {
            $p = array();
            $reference_dao =& $this->_getReferenceDao();
            $dar =& $reference_dao->searchByGroupID($group_id);
            while ($row = $dar->getRow()) {
                $p[] =& $this->_buildReference($row);
            }
            $this->referencesByProject[$group_id] =& $p;
        }
        return $p;
    }

    // Create a referenec
    // First, check that keyword is valid, except if $force is true
    function createReference(&$ref,$force=false) {
        $reference_dao =& $this->_getReferenceDao();
        if (!$force) {
            // Check if keyword is valid [a-z0-9]
            if (!$this->_isValidKeyword($ref->getKeyword())) return false;
            // Check that there is no system reference with the same keyword
            if ($this->_isSystemKeyword($ref->getKeyword())) return false;
            // Check list of reserved keywords 
            if ($this->_isReservedKeyword($ref->getKeyword())) return false;
            // Check list of existing keywords 
            $num_args=Reference::computeNumParam($ref->getLink());
            if ($this->_keywordAndNumArgsExists($ref->getKeyword(),$num_args,$ref->getGroupId())) return false;
        }
        # Create new reference
        $id = $reference_dao->create($ref->getKeyword(),
                                     $ref->getDescription(),
                                     $ref->getLink(),
                                     $ref->getScope(),
                                     $ref->getServiceShortName());
        $ref->setId($id);
        $rgid = $reference_dao->create_ref_group($id,
                                               $ref->isActive(),
                                               $ref->getGroupId());
        return $rgid;
    }

    // When creating a system reference, add occurence to all projects
    function createSystemReference($ref,$force=false) {
        $reference_dao =& $this->_getReferenceDao();

        // Check if keyword is valid [a-z0-9]
        if (!$this->_isValidKeyword($ref->getKeyword())) return false;
        // Check that it is a system reference
        if (!$ref->isSystemReference()) return false;
        if ($ref->getGroupId() != 100) return false;

        // Create reference
        $rgid=$this->createReference($ref,$force);

        // Create reference for all groups
        // Ugly SQL, needed until we have a proper Group/GroupManager class
        $sql="SELECT group_id FROM groups WHERE group_id!=100";
        $result=db_query($sql);
        while ($arr = db_fetch_array($result)) {
            $my_group_id=$arr['group_id'];
            // Create new reference
            $new_rgid = $reference_dao->create_ref_group($ref->getId(),
                                                     $ref->isActive(),
                                                     $my_group_id);
        }
        return $rgid;
    }


    function updateReference($ref,$force=false) {
        $reference_dao =& $this->_getReferenceDao();
        // Check if keyword is valid [a-z0-9]
        if (!$this->_isValidKeyword($ref->getKeyword())) return false;

        // Check list of existing keywords 
        $num_args=Reference::computeNumParam($ref->getLink());
        $refid=$this->_keywordAndNumArgsExists($ref->getKeyword(),$num_args,$ref->getGroupId());
        if (!$force) {
            if ($refid) {
                if ($refid != $ref->getId()) {
                    // The same keyword exists for another reference
                    return false;
                }
                # Don't check keyword if the reference is the same
            } else {
                // Check that there is no system reference with the same keyword
                if ($this->_isSystemKeyword($ref->getKeyword())) {
                    if ($ref->getGroupId()!= 100) return false;
                } else {
                    // Check list of reserved keywords 
                    if ($this->_isReservedKeyword($ref->getKeyword())) return false;
                }
            }
        }

        # update reference
        $reference_dao->update_ref($ref->getId(),
                               $ref->getKeyword(),
                               $ref->getDescription(),
                               $ref->getLink(),
                               $ref->getScope(),
                               $ref->getServiceShortName());
        $rgid = $reference_dao->update_ref_group($ref->getId(),
                                                 $ref->isActive(),
                                                 $ref->getGroupId());
        return $rgid;
    }

    function deleteReference($ref) {
        $reference_dao =& $this->_getReferenceDao();
        // delete reference for this group_id
        $status=$reference_dao->removeRefGroup($ref->getId(),$ref->getGroupId());
        // delete reference itself if it is not used
        if ($this->_referenceNotUsed($ref->getId())){
            $status = $status & $reference_dao->removeById($ref->getId());
        }
        return $status;
    }

    // When deleting a system reference, delete all occurences for all projects
    function deleteSystemReference($ref) {
        $reference_dao =& $this->_getReferenceDao();
        if ($ref->isSystemReference()) {
            return $reference_dao->removeAllById($ref->getId());
        } else return false;
    }


    function &loadReferenceFromKeywordAndNumArgs($keyword,$group_id=100,$num_args=1) {
        $reference_dao =& $this->_getReferenceDao();
        $dar = $reference_dao->searchByKeywordAndGroupID($keyword,$group_id);
        $ref=null;
        while($row = $dar->getRow()) {
            $ref =& $this->_buildReference($row);
            if ($ref->getNumParam()==$num_args) return $ref;
        }
        return null;
    }

    function &loadReference($refid,$group_id) {
        $reference_dao =& $this->_getReferenceDao();
        $dar = $reference_dao->searchByIdAndGroupID($refid,$group_id);
        $ref=null;
        if ($row = $dar->getRow()) {
            $ref =& $this->_buildReference($row);
        }
        return $ref;
    }


    function updateIsActive($ref,$is_active) {
        $reference_dao =& $this->_getReferenceDao();
        $dar = $reference_dao->update_ref_group($ref->getId(),$is_active,$ref->getGroupId());
    }

    /** Add all system references associated to the given service */
    function addSystemReferencesForService($template_id,$group_id,$short_name) {
      $reference_dao =& $this->_getReferenceDao();
      $dar = $reference_dao->searchByServiceShortName($short_name);
      while ($row = $dar->getRow()) {
	$this->createSystemReferenceGroup($template_id,$group_id,$row['id']);
      }
      return true;
    }

    /** Add all system references not associated to any service */
    function addSystemReferencesWithoutService($template_id, $group_id) {
      $reference_dao =& $this->_getReferenceDao();
      $dar = $reference_dao->searchByScopeAndServiceShortName('S',"");
      while ($row = $dar->getRow()) {
	$this->createSystemReferenceGroup($template_id,$group_id,$row['id']);
      }
      return true;
    }

    /** Add project references which are not system references.
     Make sure that references for trackers that have been added
     separately in project/register.php script are not created twice
    */
    function addProjectReferences($template_id, $group_id) {
      $reference_dao =& $this->_getReferenceDao();
      $dar = $reference_dao->searchByScopeAndServiceShortNameAndGroupId('P',"",$template_id);
      while ($row = $dar->getRow()) {
	$dares = $reference_dao->searchByKeywordAndGroupIdAndDescriptionAndLinkAndScope($row['keyword'],$group_id,$row['description'],$row['link'],$row['scope']);
	if ($dares && $dares->rowCount() > 0) {continue;}
	// Create corresponding reference
	$ref=& new Reference(0, // no ID yet
			     $row['keyword'],
			     $row['description'],
			     preg_replace('`group_id='. $template_id .'(&|$)`', 'group_id='. $group_id .'$1', $row['link']), // link
			     'P', // scope is 'project'
			     '',  // service ID - N/A
			     $row['is_active'], // is_used
			     $group_id);
	$this->createReference($ref,true); // Force reference creation because default trackers use reserved keywords
      }
      return true;
    }

    /** update reference associated to the given service and group_id */
    function updateReferenceForService($group_id,$short_name,$is_active) {
        $reference_dao =& $this->_getReferenceDao();
        $dar = $reference_dao->searchByServiceShortName($short_name);
        while ($row = $dar->getRow()) {
            $reference_dao->update_ref_group($row['id'],$is_active,$group_id);
        }
        return true;
    }

     function createSystemReferenceGroup($template_id,$group_id,$refid) {
        $reference_dao =& $this->_getReferenceDao();
        $proj_ref=& $this->loadReference($refid, $template_id);// Is it active in template project ?
        $rgid = $reference_dao->create_ref_group($refid,
                                                 ($proj_ref==null?false:$proj_ref->isActive()),
                                                 $group_id);
    }
       



    function &_buildReference($row) {
        if (isset($row['reference_id'])) $refid=$row['reference_id'];
        else $refid=$row['id'];
        return new Reference($refid,$row['keyword'],$row['description'],$row['link'],
                              $row['scope'],$row['service_short_name'],$row['is_active'],$row['group_id']);
    }


    /**
     * insert html links in text
     * @param $html the string which may contain invalid 
     */
    function insertReferences(&$html,$group_id) {

        $html = preg_replace_callback('/(\w+) #(\w+:)?([\w\/&]+)+/',
                                      array(&$this,"_insertRefCallback"), // method _insertRefCallback of this class
                                      $html);
    }

    /**
     * extract references from text $html
     * @param $html the text to be extracted
     * @param $group_id the group_id of the project
     * @return array of {ReferenceInstance} : an array of project references extracted in the text $html
     */
    function extractReferences($html,$group_id) {

        $referencesInstances=array();
        $count=preg_match_all('/(\w+) #(\w+:)?([\w\/&]+)+/', $html, $matches,PREG_SET_ORDER);
        foreach ($matches as $match) {
            $ref_instance=$this->_getReferenceInstanceFromMatch($match);
            if (!$ref_instance) continue;
            $ref =& $ref_instance->getReference();

            // Replace description key with real description if needed
            if (strpos($ref->getDescription(),"_desc_key")!==false) {
                $GLOBALS['Language']->loadLanguageMsg('project/project');
                $desc=$GLOBALS['Language']->getText('project_reference',$ref->getDescription());
            } else {
                $desc=$ref->getDescription();
            }
            $ref->setDescription($desc);

            $referencesInstances[]=$ref_instance;
        }
        return $referencesInstances;
    }

    /**
     * extract references from text $html (same as extractReferences) but returns them grouped by Description, and removes the duplicates references
     * @param $html the text to be extracted
     * @param $group_id the group_id of the project
     * @return array referenceinstance with the following structure: array[$description][$match] = {ReferenceInstance}
     */
    function extractReferencesGrouped($html,$group_id) {
        $referencesInstances = $this->extractReferences($html,$group_id);
        $groupedReferencesInstances = array();
        foreach ($referencesInstances as $idx => $referenceInstance) {
            $reference =& $referenceInstance->getReference();
            // description to group the references
            // match to remove duplicates entries
            $groupedReferencesInstances[$reference->getDescription()][$referenceInstance->getMatch()] = $referenceInstance;
        }
        return $groupedReferencesInstances;
    }

    // callback function
    function _insertRefCallback($match) {
        $ref_instance=$this->_getReferenceInstanceFromMatch($match);
        if (!$ref_instance) return $match[1]." #".$match[2].$match[3];
        else {
            $ref =& $ref_instance->getReference();
            if (strpos($ref->getDescription(),"_desc_key")!==false) {
                $desc=$GLOBALS['Language']->getText('project_reference',$ref->getDescription());
            } else {
                $desc=$ref->getDescription();
            }

            return "<a href=\"".$ref_instance->getGotoLink()."\" title=\"".$desc."\">".$ref_instance->getMatch()."</a>";
        }
    }


    // Get a Reference object from a matching pattern
    // if it is not a reference (e.g. wrong keyword) return null;
    function &_getReferenceInstanceFromMatch($match) {
        // Analyse match
        $key=strtolower($match[1]);
        if ($match[2]) {
            // A target project name or ID was specified
            // remove trailing colon
            $target_project=substr($match[2],0,strlen($match[2])-1);
            // id or name?
            if (is_numeric($target_project)) {
                $ref_gid = $target_project;
            } else {
                // project name instead...
                $this->_initGroupHash();
                if (isset($this->groupIdByName[$target_project])) {
                    $ref_gid = $this->groupIdByName[$target_project];
                } else {
                    return null;
                }
            }
        } else {
            if (array_key_exists('group_id', $GLOBALS)) {
                $ref_gid=$GLOBALS['group_id']; // might not be set
            } else {
                $ref_gid = '';
            }
        }

        $value=$match[3];
        if ($ref_gid=="") $ref_gid=100; // use system references only
        $num_args=substr_count($value,'/')+1; // Count number of arguments in detected reference
        $ref =& $this->_getReferenceFromKeywordAndNumArgs($key,$ref_gid,$num_args);
        if (!$ref) return null;
        $refInstance= new ReferenceInstance($match[1]." #".$match[2].$match[3],$ref);
        $refInstance->computeGotoLink($key,$match[3],$ref_gid);
        return $refInstance;
    }


    function &_getReferenceFromKeywordAndNumArgs($keyword,$group_id,$num_args) {
        $this->_initProjectReferences($group_id);
        $refs = $this->activeReferencesByProject[$group_id];
        if (isset($refs["$keyword"]))
            if (isset($refs["$keyword"][$num_args]))
                return $refs["$keyword"][$num_args];
        return null;
    }

    /**
     */
    function _initProjectReferences($group_id) {
        if (!isset($this->activeReferencesByProject[$group_id])) {
            $p = array();
            $reference_dao =& $this->_getReferenceDao();
            $dar =& $reference_dao->searchActiveByGroupID($group_id);
            while ($row = $dar->getRow()) {
                $ref =& $this->_buildReference($row);
                $num_args=$ref->getNumParam();
                if (!isset($p[$ref->getKeyword()])) {
                    $p[$ref->getKeyword()] = array();
                }
                if (isset($p[$ref->getKeyword()][$num_args])) {
                    // Project reference overload system reference 
                    // (but you can't normally create such references, except in CX 2.6 to 2.8 migration)
                    if ($ref->isSystemReference()) continue;
                }
                $p[$ref->getKeyword()][$num_args] =& $ref;
            }
            $this->activeReferencesByProject[$group_id] =& $p;
        }
    }

    function _initGroupHash() {
        if (!isset($this->groupIdByName)) {
                $gf = new GroupFactory();
                $p=array();
                $results = $gf->getAllGroups();
                while ($groups_array = db_fetch_array($results)) {
                    $p[$groups_array["unix_group_name"]]=$groups_array["group_id"];
                }
                $this->groupIdByName =& $p;
            }
    }


    function &_referenceNotUsed($refid) {
        $reference_dao =& $this->_getReferenceDao();
        $dar = $reference_dao->searchById($refid);
        if ($row = $dar->getRow())
            return false;
        else return true;
    }

    function _isReservedKeyword($keyword) {
        if (in_array($keyword,$this->reservedKeywords)) return true;
        else return false;
    }

    // Only allow lower case letters and digits
    function _isValidKeyword($keyword) {
        if (!preg_match('/^[a-z0-9]+$/',$keyword)) {
            return false;
        } else return true;
    }

    function _isSystemKeyword($keyword) {
        // Not cached because the information is only used when creating a new reference
        $reference_dao =& $this->_getReferenceDao();
        $dar=$reference_dao->searchByScope('S');
        while ($row = $dar->getRow()) {
            if ($keyword == $row['keyword']) {
                return true;
            }
        }
        return false;
    }

    function _keywordAndNumArgsExists($keyword,$num_args,$group_id) {
        $reference_dao =& $this->_getReferenceDao();
        $dar=$reference_dao->searchByKeywordAndGroupId($keyword,$group_id);
        $existing_refs=array();
        while($row = $dar->getRow()) {
            if (Reference::computeNumParam($row['link'])==$num_args)
                return $row['reference_id'];
        }
        return false;
    }

    function &_getReferenceDao() {
        if (!is_a($this->referenceDao, 'ReferenceDao')) {
            $this->referenceDao =& new ReferenceDao(CodexDataAccess::instance());
        }
        return $this->referenceDao;
    }


}
?>
