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


require_once('common/dao/ReferenceDao.class.php');
require_once('common/dao/CodendiDataAccess.class.php');
require_once('common/reference/Reference.class.php');
require_once('common/reference/ReferenceInstance.class.php');
require_once('common/include/GroupFactory.class.php');
require_once('common/reference/CrossReference.class.php');
require_once('common/dao/CrossReferenceDao.class.php');
require_once('common/event/EventManager.class.php');
require_once('common/dao/ArtifactDao.class.php');

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
    var $activeReferencesByProject = array();

    /**
     * array of Reference objects arrays indexed by group_id
     * Example: $activeReferencesByProject[101][1] return the first reference object for project 101
     * @var array
     */
    var $referencesByProject = array();

    var $referenceDao;
    var $groupIdByName;
    var $groupIdByNameLower;

    /**
     * @var array
     */
    var $reservedKeywords = array(
        "art", "artifact", "doc", "file", "wiki", "cvs", "svn", "news", "forum", "msg", "cc", "tracker", "release",
        "tag", "thread", "im", "project", "folder", "plugin", "img", "commit", "rev", "revision", "patch", "bug",
        "sr", "task", "proj", "dossier"); //should be elsewhere?

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Hold an instance of the class
     */
    protected static $instance;

    const REFERENCE_NATURE_ARTIFACT = 'artifact';
    const REFERENCE_NATURE_DOCUMENT = 'document';
    const REFERENCE_NATURE_CVSCOMMIT = 'cvs_commit';
    const REFERENCE_NATURE_SVNREVISION = 'svn_revision';
    const REFERENCE_NATURE_FILE = 'file';
    const REFERENCE_NATURE_RELEASE = 'release';
    const REFERENCE_NATURE_FORUM = 'forum';
    const REFERENCE_NATURE_FORUMMESSAGE = 'forum_message';
    const REFERENCE_NATURE_NEWS = 'news';
    const REFERENCE_NATURE_WIKIPAGE = 'wiki_page';
    const REFERENCE_NATURE_SNIPPET = 'snippet';
    const REFERENCE_NATURE_OTHER = 'other';
    
    /**
     * Not possible to give extra params to the call back function (_insertRefCallback in this case)
     * so we use an class attribute to pass the value of the group_id
     */
    var $tmpGroupIdForCallbackFunction = null;

   
    public function __construct() {
        $this->eventManager = EventManager::instance();
        $this->loadReservedKeywords();
    }
    
    protected function loadReservedKeywords() {
        //retrieve additional reserved keywords from other part of the plateform
        $additional_reserved_keywords = array();
        $this->eventManager->processEvent( Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES, array('keywords' => &$additional_reserved_keywords));
        $this->reservedKeywords = array_merge($this->reservedKeywords, $additional_reserved_keywords);
    }

    /**
     * @return ReferenceManager
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    /**
     * Returns available reference natures in Codendi
     * Plugins that want to provide references natures must
     * listen and implement the hook get_available_reference_natures
     */
    function getAvailableNatures() {
        $core_natures = array(
            self::REFERENCE_NATURE_ARTIFACT => array('keyword' => 'art', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_ARTIFACT.'_nature_key')),
            self::REFERENCE_NATURE_DOCUMENT => array('keyword' => 'doc', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_DOCUMENT.'_nature_key')),
            self::REFERENCE_NATURE_CVSCOMMIT => array('keyword' => 'cvs', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_CVSCOMMIT.'_nature_key')),
            self::REFERENCE_NATURE_SVNREVISION => array('keyword' => 'svn', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_SVNREVISION.'_nature_key')),
            self::REFERENCE_NATURE_FILE => array('keyword' => 'file', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_FILE.'_nature_key')),
            self::REFERENCE_NATURE_RELEASE => array('keyword' => 'release', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_RELEASE.'_nature_key')),
            self::REFERENCE_NATURE_FORUM => array('keyword' => 'forum', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_FORUM.'_nature_key')),
            self::REFERENCE_NATURE_FORUMMESSAGE => array('keyword' => 'msg', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_FORUMMESSAGE.'_nature_key')),
            self::REFERENCE_NATURE_NEWS => array('keyword' => 'news', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_NEWS.'_nature_key')),
            self::REFERENCE_NATURE_WIKIPAGE => array('keyword' => 'wiki', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_WIKIPAGE.'_nature_key')),
            self::REFERENCE_NATURE_SNIPPET => array('keyword' => 'snippet', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_SNIPPET.'_nature_key'))
        );
    
        $plugins_natures = array();
        $this->eventManager->processEvent('get_available_reference_natures', array('natures' => &$plugins_natures));
        
        $natures = array_merge($core_natures, $plugins_natures);
        $natures[self::REFERENCE_NATURE_OTHER] = array('keyword' => 'other', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_'.self::REFERENCE_NATURE_OTHER.'_nature_key'));
        return $natures;
    }

    function getReferencesByGroupId($group_id) {
        $p = false;
        if (isset($this->referencesByProject[$group_id])) {
            $p = $this->referencesByProject[$group_id];
        } else {
            $p = array();
            $reference_dao = $this->_getReferenceDao();
            $dar = $reference_dao->searchByGroupID($group_id);
            while ($row = $dar->getRow()) {
                $p[] = $this->_buildReference($row);
            }
            $this->referencesByProject[$group_id] = $p;
        }
        return $p;
    }

    /**
     * Create a reference
     * 
     * First, check that keyword is valid, except if $force is true
     */
    function createReference(&$ref,$force=false) {
        $reference_dao = $this->_getReferenceDao();
        if (!$force) {
            // Check if keyword is valid [a-z0-9_]
            if (!$this->_isValidKeyword($ref->getKeyword())) return false;
            // Check that there is no system reference with the same keyword
            if ($this->_isSystemKeyword($ref->getKeyword())) return false;
            // Check list of reserved keywords 
            if ($this->_isReservedKeyword($ref->getKeyword())) return false;
            // Check list of existing keywords 
            $num_args=Reference::computeNumParam($ref->getLink());
            if ($this->_keywordAndNumArgsExists($ref->getKeyword(),$num_args,$ref->getGroupId())) return false;
        }
        // Create new reference
        $id = $reference_dao->create($ref->getKeyword(),
                                     $ref->getDescription(),
                                     $ref->getLink(),
                                     $ref->getScope(),
                                     $ref->getServiceShortName(),
                                     $ref->getNature()
                                     );
        $ref->setId($id);
        $rgid = $reference_dao->create_ref_group($id,
                                               $ref->isActive(),
                                               $ref->getGroupId());
        return $rgid;
    }

    /**
     * When creating a system reference, add occurence to all projects
     */
    function createSystemReference($ref,$force=false) {
        $reference_dao = $this->_getReferenceDao();

        // Check if keyword is valid [a-z0-9_]
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
        $reference_dao = $this->_getReferenceDao();
        // Check if keyword is valid [a-z0-9_]
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
                // Don't check keyword if the reference is the same
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

        // update reference
        $reference_dao->update_ref($ref->getId(),
                                   $ref->getKeyword(),
                                   $ref->getDescription(),
                                   $ref->getLink(),
                                   $ref->getScope(),
                                   $ref->getServiceShortName(),
                                   $ref->getNature());
        $rgid = $reference_dao->update_ref_group($ref->getId(),
                                                 $ref->isActive(),
                                                 $ref->getGroupId());
        
        return $rgid;
    }

    function deleteReference($ref) {
        $reference_dao = $this->_getReferenceDao();
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
        $reference_dao = $this->_getReferenceDao();
        if ($ref->isSystemReference()) {
            return $reference_dao->removeAllById($ref->getId());
        } else return false;
    }

    function loadReferenceFromKeywordAndNumArgs($keyword,$group_id=100,$num_args=1, $val = null) {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByKeywordAndGroupID($keyword,$group_id);
        $ref=null;
        while($row = $dar->getRow()) {
            $ref = $this->_buildReference($row, $val);
            if ($ref->getNumParam()==$num_args) return $ref;
        }
        return null;
    }

    function loadReference($refid,$group_id) {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByIdAndGroupID($refid,$group_id);
        $ref=null;
        if ($row = $dar->getRow()) {
            $ref = $this->_buildReference($row);
        }
        return $ref;
    }

    function updateIsActive($ref,$is_active) {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->update_ref_group($ref->getId(),$is_active,$ref->getGroupId());
    }

    /**
     * Add all system references associated to the given service
     */
    function addSystemReferencesForService($template_id,$group_id,$short_name) {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByScopeAndServiceShortName('S',$short_name);
        while ($row = $dar->getRow()) {
            $this->createSystemReferenceGroup($template_id,$group_id,$row['id']);
        }
        return true;
    }

    /**
     * Add all system references not associated to any service
     */
    function addSystemReferencesWithoutService($template_id, $group_id) {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByScopeAndServiceShortName('S',"");
        while ($row = $dar->getRow()) {
            $this->createSystemReferenceGroup($template_id,$group_id,$row['id']);
        }
        return true;
    }

    /**
     * Add project references which are not system references.
     * Make sure that references for trackers that have been added
     * separately in project/register.php script are not created twice
     */
    function addProjectReferences($template_id, $group_id) {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByScopeAndServiceShortNameAndGroupId('P',"",$template_id);
        while ($row = $dar->getRow()) {
            $dares = $reference_dao->searchByKeywordAndGroupIdAndDescriptionAndLinkAndScope($row['keyword'],$group_id,$row['description'],$row['link'],$row['scope']);
            if ($dares && $dares->rowCount() > 0) {
                continue;
            }
            // Create corresponding reference
            $ref= new Reference(0, // no ID yet
                                $row['keyword'],
                                $row['description'],
                                preg_replace('`group_id='. $template_id .'(&|$)`', 'group_id='. $group_id .'$1', $row['link']), // link
                                'P', // scope is 'project'
                                $row['service_short_name'],  // service ID - N/A
                                $row['nature'],
                                $row['is_active'], // is_used
                                $group_id);
            $this->createReference($ref,true); // Force reference creation because default trackers use reserved keywords
        }
        return true;
    }

    /**
     * update reference associated to the given service and group_id
     */
    function updateReferenceForService($group_id,$short_name,$is_active) {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByServiceShortName($short_name);
        while ($row = $dar->getRow()) {
            $reference_dao->update_ref_group($row['id'],$is_active,$group_id);
        }
        return true;
    }

    /**
     * This method updates (rename) reference short name and related cross references
     * @param Integer $group_id
     * @param String $old_short_name
     * @param Stirng $new_short_name
     */
    function updateProjectReferenceShortName($group_id, $old_short_name, $new_short_name) {
        $ref_dao  = $this->_getReferenceDao();
        if ( $ref_dao->updateProjectReferenceShortName($group_id, $old_short_name, $new_short_name) === false ) {
            return false;
        }
        $xref_dao = $this->_getCrossReferenceDao();
        $xref_dao->updateTargetKeyword($old_short_name, $new_short_name, $group_id);
        $xref_dao->updateSourceKeyword($old_short_name, $new_short_name, $group_id);
    }

     function createSystemReferenceGroup($template_id,$group_id,$refid) {
        $reference_dao = $this->_getReferenceDao();
        $proj_ref= $this->loadReference($refid, $template_id);// Is it active in template project ?
        $rgid = $reference_dao->create_ref_group($refid,
                                                 ($proj_ref==null?false:$proj_ref->isActive()),
                                                 $group_id);
    }

    /**
     * Return true if keyword is valid to reference artifacts
     *
     * @param String $keyword
     *
     * @return Boolean
     */
    private function isAnArtifactKeyword($keyword) {
        $natures = $this->getAvailableNatures();
        return $keyword == $natures[self::REFERENCE_NATURE_ARTIFACT]['keyword'];
    }

    protected function _buildReference($row, $val = null) {
        if (isset($row['reference_id'])) $refid=$row['reference_id'];
        else $refid=$row['id'];
        $ref = new Reference($refid,$row['keyword'],$row['description'],$row['link'],
                              $row['scope'],$row['service_short_name'],$row['nature'],$row['is_active'],$row['group_id']);
        
        if ($this->isAnArtifactKeyword($row['keyword']) && !$this->getGroupIdFromArtifactId($val)) {
            $this->eventManager->processEvent(Event::BUILD_REFERENCE, array('row' => $row, 'ref_id' => $refid, 'ref' => &$ref));
        }
        return $ref;
    }
    
    /**
     * the regexp used to find a reference
     * @return $exp the string which may the regexp 
     */
    function _getExpForRef() {
        $exp = "`(\w+)       #reference
                           \s          #separator
                           \#          #dash (2 en 1)
                           ([\w-_]+:)? #optional project name (followed by a colon)
                           ((?:&amp;|\w|/|&)+) #any combination of &, &amp;, a word or a slash
                          `xu";
        return $exp;
    }

    /**
     * insert html links in text
     * @param $html the string which may contain invalid 
     */
    function insertReferences(&$html,$group_id) {
        $this->tmpGroupIdForCallbackFunction = $group_id;
        $locale = setlocale(LC_CTYPE, null);
        setlocale(LC_CTYPE, 'fr_FR.ISO-8859-1');
        if (!preg_match('/[^\s]{5000,}/', $html)) {             
            $exp = $this->_getExpForRef();
            $html = preg_replace_callback($exp,
                                      array(&$this,"_insertRefCallback"), // method _insertRefCallback of this class
                                      $html);
        }
        setlocale(LC_CTYPE, $locale);
        $this->tmpGroupIdForCallbackFunction = null;
    }

    /**
     * Extract all possible references from input text 
     *
     * @param input text $html
     * @return array of matches
     */
    function _extractAllMatches($html) {
        $locale = setlocale(LC_CTYPE, null);
        setlocale(LC_CTYPE, 'fr_FR.ISO-8859-1');
        $exp = $this->_getExpForRef();
        $count=preg_match_all($exp, $html, $matches,PREG_SET_ORDER);
        setlocale(LC_CTYPE, $locale);
        return $matches;
    }
    
    /**
     * extract references from text $html
     * @param $html the text to be extracted
     * @param $group_id the group_id of the project
     * @return array of {ReferenceInstance} : an array of project references extracted in the text $html
     */
    function extractReferences($html,$group_id) {        
        $referencesInstances=array();
        $matches = $this->_extractAllMatches($html);
        foreach ($matches as $match) {
            $ref_instance=$this->_getReferenceInstanceFromMatch($match);
            if (!$ref_instance) continue;
            $ref = $ref_instance->getReference();

            // Replace description key with real description if needed
            if (strpos($ref->getDescription(),"_desc_key")!==false) {
                if (preg_match('/(.*):(.*)/', $ref->getDescription(), $matches)) {
                    if ($GLOBALS['Language']->hasText($matches[1], $matches[2])) {
                        $desc = $GLOBALS['Language']->getText($matches[1], $matches[2]);
                    }
                } else {
                    $desc=$GLOBALS['Language']->getText('project_reference',$ref->getDescription());
                }
            } else {
                $desc=$ref->getDescription();
            }
            $ref->setDescription($desc);

            $referencesInstances[]=$ref_instance;
        }
        return $referencesInstances;
    }
    
    /**
     * TODO : adapt it to the new tracker structure when ready
     */
    function getArtifactKeyword($artifact_id, $group_id) {
        $sql = "SELECT group_artifact_id FROM artifact WHERE artifact_id= ". db_ei($artifact_id);
        $result = db_query($sql);
        if (db_numrows($result) > 0) {
            $row = db_fetch_array($result);
            $tracker_id = $row['group_artifact_id'];
            $project = new Project($group_id);
            $tracker = new ArtifactType($project, $tracker_id);
            $tracker_short_name = $tracker->getItemName();
            $reference_dao =& $this->_getReferenceDao();
            $dar = $reference_dao->searchByKeywordAndGroupId($tracker_short_name, $group_id);
            if ($dar && $dar->rowCount() >= 1) {
                return $tracker_short_name;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
    
    /**
     * Extract References from a given text and insert extracted refs into the database
     *
     * @param String  $html        Text to parse
     * @param Integer $source_id   Id of the item where the text was added
     * @param String  $source_type Nature of the source 
     * @param Integer $source_gid  Project Id of the project the source item belongs to
     * @param Integer $user_id     User who owns the text to parse
     * @param String  $source_key  Keyword to use for the reference (if different from the one associated to the nature)
     * 
     * @retrun Boolean True if no error
     */
    function extractCrossRef($html,$source_id, $source_type, $source_gid, $user_id=0, $source_key=null) {
        if ($source_key == null) {
            $available_natures = $this->getAvailableNatures();
            if ($source_type == self::REFERENCE_NATURE_ARTIFACT) {
                $source_key = $this->getArtifactKeyword($source_id, $source_gid);
                if (! $source_key) {
                    $source_key = $available_natures[$source_type]['keyword'];
                }
            } else {
                $source_key = $available_natures[$source_type]['keyword'];
            }
        }
        
        $matches = $this->_extractAllMatches($html);
        foreach ($matches as $match) {
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
                    } else if(isset($this->groupIdByNameLower[$target_project])) {
                        $ref_gid = $this->groupIdByNameLower[$target_project];
                    }else {
                        return null;
                    }
                }
            } else {
                if ($this->tmpGroupIdForCallbackFunction) {
                    $ref_gid = $this->tmpGroupIdForCallbackFunction;
                } else {
                    if (array_key_exists('group_id', $GLOBALS)) {
                        $ref_gid=$GLOBALS['group_id']; // might not be set
                    } else {
                        $ref_gid = '';
                    }
                }
            }

            $value=$match[3];
            if ($ref_gid=="") $ref_gid=100; // use system references only
            $num_args=substr_count($value,'/')+1; // Count number of arguments in detected reference
            $ref = $this->_getReferenceFromKeywordAndNumArgs($key,$ref_gid,$num_args);

            if ($ref) {
                //Cross reference
                $sqlkey='SELECT link, nature from reference r,reference_group rg where keyword="'.$match[1].'" AND r.id = rg.reference_id AND rg.group_id='.$source_gid;
                $reskey = db_query($sqlkey);
                if ($reskey && db_numrows($reskey) > 0) {
                    $key_array = db_fetch_array($reskey);

                    $target_type = $key_array['nature'];
                    $target_id = $match[3];
                    $target_key = $match[1];    // keyword
                    $target_gid = $ref_gid;
                    if ($user_id==0){
                        $user_id=user_getid();
                    }

                    $this->insertCrossReference(new CrossReference(
                        $source_id,
                        $source_gid,
                        $source_type,
                        $source_key,
                        $target_id,
                        $target_gid,
                        $target_type,
                        $target_key,
                        $user_id
                    ));
                }
            }
        }
        return true;
    }

    public function insertCrossReference(CrossReference $cross_reference) {
        if(! $cross_reference->existInDb()) {
            $cross_reference->createDbCrossRef();
        }
    }

    public function removeCrossReference(CrossReference $cross_reference) {
        $cross_reference->deleteCrossReference();
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
            $reference = $referenceInstance->getReference();
            // description to group the references
            // match to remove duplicates entries
            $groupedReferencesInstances[$reference->getDescription()][$referenceInstance->getMatch()] = $referenceInstance;
        }
        return $groupedReferencesInstances;
    }

    // callback function
    function _insertRefCallback($match) {
        $desc='';
        $ref_instance=$this->_getReferenceInstanceFromMatch($match);
        if (!$ref_instance) return $match[1]." #".$match[2].$match[3];
        else {
            $ref = $ref_instance->getReference();
            if (strpos($ref->getDescription(),"_desc_key")!==false) {
                if (preg_match('/(.*):(.*)/', $ref->getDescription(), $ref_matches)) {
                    if ($GLOBALS['Language']->hasText($ref_matches[1], $ref_matches[2])) {
                        $desc = $GLOBALS['Language']->getText($ref_matches[1], $ref_matches[2]);
                    }
                } else {
                    $desc = $GLOBALS['Language']->getText('project_reference',$ref->getDescription());
                }
            } else {
                $desc=$ref->getDescription();
            }

            return '<a href="'.$ref_instance->getGotoLink().'" title="'.$desc.'" class="cross-reference">'.$ref_instance->getMatch()."</a>";
        }
    }    

    /**
     * Returns the group id of an artifact id
     *
     * @param Integer $artifact_id
     *
     * @return mixed False if no match, the group id otherwise
     */
    protected function getGroupIdFromArtifactId($artifact_id) {
        $dao    = $this->getArtifactDao();
        $result = $dao->searchArtifactId($artifact_id);
        if ($result && count($result)) {
            $row = $result->getRow();
            return $row['group_id'];
        }
        return false;
    }

    /**
     * Return the group_id of an artifact_id
     * 
     * @param Integer $artifact_id
     *
     * @return Integer
     */
    protected function getGroupIdFromArtifactIdForCallbackFunction($artifact_id) {
        $group_id = $this->getGroupIdFromArtifactId($artifact_id);
        if ($group_id === false) {
            $this->eventManager->processEvent(Event::GET_ARTIFACT_REFERENCE_GROUP_ID, array('artifact_id' => $artifact_id, 'group_id' => &$group_id));
        }
        return $group_id;
    }

    // Get a Reference object from a matching pattern
    // if it is not a reference (e.g. wrong keyword) return null;
    function _getReferenceInstanceFromMatch($match) {
        // Analyse match
        $key     = strtolower($match[1]);
        
        if ($this->isAnArtifactKeyword($key)) {
            $this->tmpGroupIdForCallbackFunction = $this->getGroupIdFromArtifactIdForCallbackFunction($match[3]);
        }
        
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
                } else if (isset($this->groupIdByNameLower[$target_project])) {
                    $ref_gid = $this->groupIdByNameLower[$target_project];
                }else {
                    return null;
                }
            }
        } else {
            if ($this->tmpGroupIdForCallbackFunction) {
                $ref_gid = $this->tmpGroupIdForCallbackFunction;
            } else {
                if (array_key_exists('group_id', $GLOBALS)) {
                    $ref_gid=$GLOBALS['group_id']; // might not be set
                } else {
                    $ref_gid = '';
                }
            }
        }

        $value=$match[3];
        if ($ref_gid=="") $ref_gid=100; // use system references only
        $num_args=substr_count($value,'/')+1; // Count number of arguments in detected reference
        $ref = $this->_getReferenceFromKeywordAndNumArgs($key,$ref_gid,$num_args);
        $refInstance = null;
        if ($ref) {
            $refInstance= new ReferenceInstance($match[1]." #".$match[2].$match[3],$ref,$match[3]);
            $refInstance->computeGotoLink($key,$match[3],$ref_gid);
          
        }
        return $refInstance;
    }

        

    function _getReferenceFromKeywordAndNumArgs($keyword,$group_id,$num_args) {
        $this->_initProjectReferences($group_id);
        $refs = $this->activeReferencesByProject[$group_id];
        // This part of the code prevent cross ref to wiki subpage to work
        // wiki #sub/page/2 should extract a link to the version 2 of the wikipage "sub/page"
        // References contains a "num_args" (args separated by '/') nevertheless
        // we don't know in advance the number of sub pages
        if (isset($refs["$keyword"]))
            if (isset($refs["$keyword"][$num_args]))
                return $refs["$keyword"][$num_args];
        $ref = null;
        return $ref;
    }

    /**
     */
    function _initProjectReferences($group_id) {   
        if (!isset($this->activeReferencesByProject[$group_id])) {
            $p = array();
            $reference_dao = $this->_getReferenceDao();
            $dar = $reference_dao->searchActiveByGroupID($group_id);
            while ($row = $dar->getRow()) {
                $ref = $this->_buildReference($row);
                $num_args=$ref->getNumParam();
                if (!isset($p[$ref->getKeyword()])) {
                    $p[$ref->getKeyword()] = array();
                }
                if (isset($p[$ref->getKeyword()][$num_args])) {
                    // Project reference overload system reference 
                    // (but you can't normally create such references, except in CX 2.6 to 2.8 migration)
                    if ($ref->isSystemReference()) continue;
                }
                $p[$ref->getKeyword()][$num_args] = $ref;
            }
            $this->activeReferencesByProject[$group_id] = $p;
        }
    }

    function _initGroupHash() {
        if (!isset($this->groupIdByName)) {
                $gf = new GroupFactory();
                $p=array();
                $pl=array();
                $results = $gf->getAllGroups();
                while ($groups_array = db_fetch_array($results)) {
                    $p[$groups_array["unix_group_name"]]=$groups_array["group_id"];
                    $pl[strtolower($groups_array["unix_group_name"])]=$groups_array["group_id"];
                }                
                $this->groupIdByName = $p;
                $this->groupIdByNameLower =& $pl;               
            }
    }


    function _referenceNotUsed($refid) {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchById($refid);
        if ($row = $dar->getRow())
            return false;
        else return true;
    }

    function _isReservedKeyword($keyword) {
        if (in_array($keyword,$this->reservedKeywords)) return true;
        else return false;
    }

    // Only allow lower case letters, digits and underscores
    function _isValidKeyword($keyword) {
        if (!preg_match('/^[a-z0-9_]+$/',$keyword)) {
            return false;
        } else return true;
    }

    function _isSystemKeyword($keyword) {
        // Not cached because the information is only used when creating a new reference
        $reference_dao = $this->_getReferenceDao();
        $dar=$reference_dao->searchByScope('S');
        while ($row = $dar->getRow()) {
            if ($keyword == $row['keyword']) {
                return true;
            }
        }
        return false;
    }
    
    function _isKeywordExists($keyword, $group_id) {
        $reference_dao = $this->_getReferenceDao();
        $dar=$reference_dao->searchByKeywordAndGroupId($keyword,$group_id);
        if ($dar->rowCount() > 0) {
            return true;
        }
    }
    
    public function checkKeyword($keyword) {            
            // Check that there is no system reference with the same keyword
            if ($this->_isSystemKeyword($keyword)) return false;
            // Check list of reserved keywords 
            if ($this->_isReservedKeyword($keyword)) return false;
            return true;
    }
    
    function _keywordAndNumArgsExists($keyword,$num_args,$group_id) {
        $reference_dao = $this->_getReferenceDao();
        $dar=$reference_dao->searchByKeywordAndGroupId($keyword,$group_id);
        $existing_refs=array();
        while($row = $dar->getRow()) {
            if (Reference::computeNumParam($row['link'])==$num_args)
                return $row['reference_id'];
        }
        return false;
    }

    function _getReferenceDao() {
        if (!is_a($this->referenceDao, 'ReferenceDao')) {
            $this->referenceDao = new ReferenceDao(CodendiDataAccess::instance());
        }
        return $this->referenceDao;
    }

    function _getCrossReferenceDao() {
        return new CrossReferenceDao();
    }

    /**
     * Wrapper
     *
     * @return ArtifactDao
     */
    private function getArtifactDao() {
        return new ArtifactDao();
    }
}
?>
