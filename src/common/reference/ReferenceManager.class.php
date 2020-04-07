<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Reference\ReferenceDescriptionTranslation;
use Tuleap\reference\ReferenceValidator;
use Tuleap\reference\ReservedKeywordsRetriever;

/**
 * Reference Manager
 * Performs all operations on references, including DB access (through ReferenceDAO)
 */
class ReferenceManager
{

    /**
     * array of active Reference objects arrays of arrays, indexed by group_id, keyword, and num args.
     * Example: $activeReferencesByProject[101]['art'][1] return the reference object for project 101, keyword 'art' and one argument.
     * @var array
     */
    public $activeReferencesByProject = array();

    /**
     * array of Reference objects arrays indexed by group_id
     * Example: $activeReferencesByProject[101][1] return the first reference object for project 101
     * @var array
     */
    public $referencesByProject = array();

    public $referenceDao;

    /**
     * @var CrossReferenceDao
     */
    public $cross_reference_dao;

    private $groupIdByName = array();

    /**
     * @var array containing additional regexp to match more reference formats.
     * it is a list matching: array(
     *   array(
     *      'regexp' => PCRE compatible regular expression matching references,
     *      'cb'     => PHP callback of a function taking a regexp match
     *                  followed by the project group_id  and returning a
     *                  ReferenceInstance (or null if this is not a reference)
     *   )
     *   ...
     * )
     */
    private $additional_references = array();

    private $php_supported_encoding_types = array(
        'UTF-8',
        'ISO-8859-15',
        'ISO-8859-5',
        'ISO-8859-1',
    );
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Hold an instance of the class
     */
    protected static $instance;

    public const KEYWORD_ARTIFACT_SHORT = 'art';
    public const KEYWORD_ARTIFACT_LONG  = 'artifact';

    public const REFERENCE_NATURE_ARTIFACT = 'artifact';
    public const REFERENCE_NATURE_DOCUMENT = 'document';
    public const REFERENCE_NATURE_CVSCOMMIT = 'cvs_commit';
    public const REFERENCE_NATURE_SVNREVISION = 'svn_revision';
    public const REFERENCE_NATURE_FILE = 'file';
    public const REFERENCE_NATURE_RELEASE = 'release';
    public const REFERENCE_NATURE_FORUM = 'forum';
    public const REFERENCE_NATURE_FORUMMESSAGE = 'forum_message';
    public const REFERENCE_NATURE_NEWS = 'news';
    public const REFERENCE_NATURE_WIKIPAGE = 'wiki_page';
    public const REFERENCE_NATURE_OTHER = 'other';

    /**
     * Not possible to give extra params to the call back function (_insertRefCallback in this case)
     * so we use an class attribute to pass the value of the group_id
     */
    public $tmpGroupIdForCallbackFunction = null;


    public function __construct()
    {
        $this->eventManager = EventManager::instance();
        $this->loadExtraFormats();
    }

    protected function loadExtraFormats()
    {
        $this->additional_references = array();
        $this->eventManager->processEvent(Event::GET_PLUGINS_EXTRA_REFERENCES, array(
            'reference_manager' => $this,
            'refs'              => &$this->additional_references
        ));
    }

    /**
     * @return ReferenceManager
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            $c = self::class;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    /**
     * Returns available reference natures in Codendi
     * Plugins that want to provide references natures must
     * listen and implement the hook get_available_reference_natures
     */
    public function getAvailableNatures()
    {
        $core_natures = array(
            self::REFERENCE_NATURE_ARTIFACT => array('keyword' => 'art', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_artifact_nature_key')),
            self::REFERENCE_NATURE_DOCUMENT => array('keyword' => 'doc', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_document_nature_key')),
            self::REFERENCE_NATURE_CVSCOMMIT => array('keyword' => 'cvs', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_cvs_commit_nature_key')),
            self::REFERENCE_NATURE_SVNREVISION => array('keyword' => 'svn', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_svn_revision_nature_key')),
            self::REFERENCE_NATURE_FILE => array('keyword' => 'file', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_file_nature_key')),
            self::REFERENCE_NATURE_RELEASE => array('keyword' => 'release', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_release_nature_key')),
            self::REFERENCE_NATURE_FORUM => array('keyword' => 'forum', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_forum_nature_key')),
            self::REFERENCE_NATURE_FORUMMESSAGE => array('keyword' => 'msg', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_forum_message_nature_key')),
            self::REFERENCE_NATURE_NEWS => array('keyword' => 'news', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_news_nature_key')),
            self::REFERENCE_NATURE_WIKIPAGE => array('keyword' => 'wiki', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_wiki_page_nature_key')),
        );

        $plugins_natures = array();
        $this->eventManager->processEvent('get_available_reference_natures', array('natures' => &$plugins_natures));

        $natures = array_merge($core_natures, $plugins_natures);
        $natures[self::REFERENCE_NATURE_OTHER] = array('keyword' => 'other', 'label' => $GLOBALS['Language']->getText('project_reference', 'reference_other_nature_key'));
        return $natures;
    }

    public function getReferencesByGroupId($group_id)
    {
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
    public function createReference(&$ref, $force = false)
    {
        $reference_dao = $this->_getReferenceDao();
        if (!$force) {
            // Check if keyword is valid [a-z0-9_]
            if (!$this->getReferenceValidator()->isValidKeyword($ref->getKeyword())) {
                return false;
            }
            // Check that there is no system reference with the same keyword
            if ($this->getReferenceValidator()->isSystemKeyword($ref->getKeyword())) {
                return false;
            }
            // Check list of reserved keywords
            if ($this->getReferenceValidator()->isReservedKeyword($ref->getKeyword())) {
                return false;
            }
            // Check list of existing keywords
            $num_args = Reference::computeNumParam($ref->getLink());
            if ($this->_keywordAndNumArgsExists($ref->getKeyword(), $num_args, $ref->getGroupId())) {
                return false;
            }
        }
        // Create new reference
        $id = $reference_dao->create(
            $ref->getKeyword(),
            $ref->getDescription(),
            $ref->getLink(),
            $ref->getScope(),
            $ref->getServiceShortName(),
            $ref->getNature()
        );
        $ref->setId($id);
        $rgid = $reference_dao->create_ref_group(
            $id,
            $ref->isActive(),
            $ref->getGroupId()
        );
        return $rgid;
    }

    /**
     * When creating a system reference, add occurence to all projects
     */
    public function createSystemReference($ref, $force = false)
    {
        $reference_dao = $this->_getReferenceDao();

        // Check if keyword is valid [a-z0-9_]
        if (!$this->getReferenceValidator()->isValidKeyword($ref->getKeyword())) {
            return false;
        }
        // Check that it is a system reference
        if (!$ref->isSystemReference()) {
            return false;
        }
        if ($ref->getGroupId() != 100) {
            return false;
        }

        // Create reference
        $rgid = $this->createReference($ref, $force);

        // Create reference for all groups
        // Ugly SQL, needed until we have a proper Group/GroupManager class
        $sql = "SELECT group_id FROM groups WHERE group_id!=100";
        $result = db_query($sql);
        while ($arr = db_fetch_array($result)) {
            $my_group_id = $arr['group_id'];
            // Create new reference
            $new_rgid = $reference_dao->create_ref_group(
                $ref->getId(),
                $ref->isActive(),
                $my_group_id
            );
        }
        return $rgid;
    }

    public function updateReference($ref, $force = false)
    {
        $reference_dao = $this->_getReferenceDao();
        // Check if keyword is valid [a-z0-9_]
        if (!$this->getReferenceValidator()->isValidKeyword($ref->getKeyword())) {
            return false;
        }

        // Check list of existing keywords
        $num_args = Reference::computeNumParam($ref->getLink());
        $refid = $this->_keywordAndNumArgsExists($ref->getKeyword(), $num_args, $ref->getGroupId());
        if (!$force) {
            if ($refid) {
                if ($refid != $ref->getId()) {
                    // The same keyword exists for another reference
                    return false;
                }
                // Don't check keyword if the reference is the same
            } else {
                // Check that there is no system reference with the same keyword
                if ($this->getReferenceValidator()->isSystemKeyword($ref->getKeyword())) {
                    if ($ref->getGroupId() != 100) {
                        return false;
                    }
                } else {
                    // Check list of reserved keywords
                    if ($this->getReferenceValidator()->isReservedKeyword($ref->getKeyword())) {
                        return false;
                    }
                }
            }
        }

        // update reference
        $reference_dao->update_ref(
            $ref->getId(),
            $ref->getKeyword(),
            $ref->getDescription(),
            $ref->getLink(),
            $ref->getScope(),
            $ref->getServiceShortName(),
            $ref->getNature()
        );
        $rgid = $reference_dao->update_ref_group(
            $ref->getId(),
            $ref->isActive(),
            $ref->getGroupId()
        );

        return $rgid;
    }

    public function deleteReference($ref)
    {
        $reference_dao = $this->_getReferenceDao();
        // delete reference for this group_id
        $status = $reference_dao->removeRefGroup($ref->getId(), $ref->getGroupId());
        // delete reference itself if it is not used
        if ($this->_referenceNotUsed($ref->getId())) {
            $status = $status & $reference_dao->removeById($ref->getId());
        }
        return $status;
    }

    // When deleting a system reference, delete all occurences for all projects
    public function deleteSystemReference($ref)
    {
        $reference_dao = $this->_getReferenceDao();
        if ($ref->isSystemReference()) {
            return $reference_dao->removeAllById($ref->getId());
        } else {
            return false;
        }
    }

    public function loadReferenceFromKeywordAndNumArgs($keyword, $group_id = 100, $num_args = 1, $val = null)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByKeywordAndGroupID($keyword, $group_id);
        $ref = null;
        while ($row = $dar->getRow()) {
            $ref = $this->_buildReference($row, $val);
            if ($ref->getNumParam() == $num_args) {
                return $ref;
            }
        }
        return null;
    }

    public function loadReferenceFromKeyword($keyword, $reference_id)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar           = $reference_dao->searchByKeyword($keyword);

        if (! $dar) {
            return null;
        }

        return $this->_buildReference($dar, $reference_id);
    }

    public function loadReference($refid, $group_id)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByIdAndGroupID($refid, $group_id);
        $ref = null;
        if ($row = $dar->getRow()) {
            $ref = $this->_buildReference($row);
        }
        return $ref;
    }

    public function updateIsActive($ref, $is_active)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->update_ref_group($ref->getId(), $is_active, $ref->getGroupId());
    }

    /**
     * Add all system references associated to the given service
     */
    public function addSystemReferencesForService($template_id, $group_id, $short_name)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByScopeAndServiceShortName('S', $short_name);
        while ($row = $dar->getRow()) {
            $this->createSystemReferenceGroup($template_id, $group_id, $row['id']);
        }
        return true;
    }

    /**
     * Add all system references not associated to any service
     */
    public function addSystemReferencesWithoutService($template_id, $group_id)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByScopeAndServiceShortName('S', "");
        while ($row = $dar->getRow()) {
            $this->createSystemReferenceGroup($template_id, $group_id, $row['id']);
        }
        return true;
    }

    /**
     * Add project references which are not system references.
     * Make sure that references for trackers that have been added
     * separately in project/register.php script are not created twice
     */
    public function addProjectReferences($template_id, $group_id)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByScopeAndServiceShortNameAndGroupId('P', "", $template_id);
        while ($row = $dar->getRow()) {
            $dares = $reference_dao->searchByKeywordAndGroupIdAndDescriptionAndLinkAndScope($row['keyword'], $group_id, $row['description'], $row['link'], $row['scope']);
            if ($dares && $dares->rowCount() > 0) {
                continue;
            }
            // Create corresponding reference
            $ref = new Reference(
                0, // no ID yet
                $row['keyword'],
                $row['description'],
                preg_replace('`group_id=' . preg_quote($template_id, '`') . '(&|$)`', 'group_id=' . $group_id . '$1', $row['link']), // link
                'P', // scope is 'project'
                $row['service_short_name'],  // service ID - N/A
                $row['nature'],
                $row['is_active'], // is_used
                $group_id
            );
            $this->createReference($ref, true); // Force reference creation because default trackers use reserved keywords
        }
        return true;
    }

    /**
     * update reference associated to the given service and group_id
     */
    public function updateReferenceForService($group_id, $short_name, $is_active)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByServiceShortName($short_name);
        while ($row = $dar->getRow()) {
            $reference_dao->update_ref_group($row['id'], $is_active, $group_id);
        }
        return true;
    }

    /**
     * This method updates (rename) reference short name and related cross references
     * @param int $group_id
     * @param string $old_short_name
     * @param string $new_short_name
     */
    public function updateProjectReferenceShortName($group_id, $old_short_name, $new_short_name)
    {
        $ref_dao  = $this->_getReferenceDao();
        if ($ref_dao->updateProjectReferenceShortName($group_id, $old_short_name, $new_short_name) === false) {
            return false;
        }
        $xref_dao = $this->_getCrossReferenceDao();
        $xref_dao->updateTargetKeyword($old_short_name, $new_short_name, $group_id);
        $xref_dao->updateSourceKeyword($old_short_name, $new_short_name, $group_id);
    }

    public function createSystemReferenceGroup($template_id, $group_id, $refid)
    {
        $reference_dao = $this->_getReferenceDao();
        $proj_ref = $this->loadReference($refid, $template_id);// Is it active in template project ?
        $rgid = $reference_dao->create_ref_group(
            $refid,
            ($proj_ref == null ? false : $proj_ref->isActive()),
            $group_id
        );
    }

    /**
     * Return true if keyword is valid to reference artifacts
     *
     * @param String $keyword
     *
     * @return bool
     */
    private function isAnArtifactKeyword($keyword)
    {
        return $keyword == self::KEYWORD_ARTIFACT_LONG
            || $keyword == self::KEYWORD_ARTIFACT_SHORT;
    }

    protected function _buildReference($row, $val = null)
    {
        if (isset($row['reference_id'])) {
            $refid = $row['reference_id'];
        } else {
            $refid = $row['id'];
        }

        if ($row['nature'] === 'file') {
            $reference = new \Tuleap\FRS\FileReference(
                $refid,
                $row['keyword'],
                $row['description'],
                $row['link'],
                $row['scope'],
                $row['service_short_name'],
                $row['nature'],
                $row['is_active'],
                $row['group_id'],
                $val
            );
        } else {
            $reference = new Reference(
                $refid,
                $row['keyword'],
                $row['description'],
                $row['link'],
                $row['scope'],
                $row['service_short_name'],
                $row['nature'],
                $row['is_active'],
                $row['group_id']
            );
        }

        if ($this->isAnArtifactKeyword($row['keyword'])) {
            if (! $this->getGroupIdFromArtifactId($val)) {
                $this->eventManager->processEvent(
                    Event::BUILD_REFERENCE,
                    array('row' => $row, 'ref_id' => $refid, 'ref' => &$reference)
                );
            } else {
                $this->ensureArtifactDataIsCorrect($reference, $val);
            }
        }

        return $reference;
    }


    private function ensureArtifactDataIsCorrect(Reference $ref, $val)
    {
        $group_id = $this->getGroupIdFromArtifactId($val);

        $ref->setGroupId($this->getGroupIdFromArtifactId($group_id));
        $ref->setLink("/tracker/?func=detail&aid=$val&group_id=$group_id");
    }

    /**
     * the regexp used to find a reference
     * @return string $exp the string which may the regexp
     */
    public function _getExpForRef()
    {
        $exp = "`
            (?P<key>\w+)
            \s          #blank separator
            \#          #dash (2 en 1)
            (?P<project_name>[\w\-]+:)? #optional project name (followed by a colon)
            (?P<value>(?:&amp;|\w|/|&)+?) #any combination of &, &amp;, a word or a slash
            (?P<after_reference>&(?:\#(?:\d+|[xX][[:xdigit:]]+)|quot);|(?=[^\w&/])|$) # Exclude HTML dec, hex and some (quot) named entities from the end of the reference
        `x";
        return $exp;
    }

    /*
     * Callback function that returns a link in place of a custom reference
     * Must be public because it is called from an anonymous function
     */
    public function insertCustomRefCallback($match, $group_id, $reftypecb)
    {
        $ref = call_user_func($reftypecb, $match, $group_id);
        if (empty($ref)) {
            return $match[0];
        }

        return $this->buildLinkForReference($ref);
    }

    /**
     * @return string html link tag to the reference instance
     */
    private function buildLinkForReference(ReferenceInstance $ref_instance)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $ref      = $ref_instance->getReference();

        $reference_description_translation = new ReferenceDescriptionTranslation($ref);

        return '<a href="' . $ref_instance->getFullGotoLink() . '" title="' . $purifier->purify($reference_description_translation->getTranslatedDescription()) .
            '" class="cross-reference">' . $purifier->purify($ref_instance->getMatch()) . '</a>';
    }

    /**
     * insert html links in text
     * @param $html the string which may contain invalid
     */
    public function insertReferences(&$html, $group_id)
    {
        $this->tmpGroupIdForCallbackFunction = $group_id;
        $locale = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, 'fr_FR.ISO-8859-1');

        if (!preg_match('/[^\s]{5000,}/', $html)) {
            $exp = $this->_getExpForRef();

            $html = $this->convertToUTF8($html);
            $html = preg_replace_callback(
                $exp,
                function ($match) {
                    $ref_instance = $this->_getReferenceInstanceFromMatch($match);
                    if (!$ref_instance) {
                        return $match['key'] . " #" . $match['project_name'] . $match['value'] . $match['after_reference'];
                    }
                    return $this->buildLinkForReference($ref_instance) . $match['after_reference'];
                },
                $html
            );

            foreach ($this->additional_references as $reftype) {
                $self = $this;
                $html = preg_replace_callback(
                    $reftype['regexp'],
                    function ($m) use ($self, $group_id, $reftype) {
                        return $self->insertCustomRefCallback($m, $group_id, $reftype['cb']);
                    },
                    $html
                );
            }

            $this->insertLinksForMentions($html);
        }

        setlocale(LC_CTYPE, $locale);
        $this->tmpGroupIdForCallbackFunction = null;
    }

    private function insertLinksForMentions(&$html)
    {
        $html = preg_replace_callback(
            '/(^|\W)@([a-zA-Z][a-zA-Z0-9\-_\.]{2,})/',
            function ($match) {
                return $this->insertMentionCallback($match);
            },
            $html
        );
    }

    private function insertMentionCallback($match)
    {
        $original_string = $match['0'];
        $char_before     = $match['1'];
        $username        = $match['2'];

        if (UserManager::instance()->getUserByUserName($username)) {
            return $char_before . '<a href="/users/' . $username . '">@' . $username . '</a>';
        }

        return $original_string;
    }

    /**
     * Takes a string and tries to convert all special characters to UTF-8.
     * Any characters that are not recognised will be removed from the string.
     *
     * This is done since for php >= 5.2 the method preg_replace_callback() cannot process non-utf-8 strings.
     *
     * Note: We need to know if the version is greater than 5.3.0 since the htmlentities()
     * parameter ENT_IGNORE only exists for php > 5.3.0
     */
    private function convertToUTF8($string)
    {
        $encoding = mb_detect_encoding($string, implode(',', $this->php_supported_encoding_types));
        $string   = htmlentities($string, ENT_IGNORE, $encoding);

        return html_entity_decode($string, ENT_IGNORE, 'UTF-8');
    }

    /**
     * Extract all possible references from input text
     *
     * @param input text $html
     * @return array of matches
     */
    public function _extractAllMatches($html)
    {
        return $this->_extractMatches($html, $this->_getExpForRef());
    }

    /**
     * Extract matches from input text according to the regexp
     */
    private function _extractMatches($html, $regexp)
    {
        $locale = setlocale(LC_CTYPE, "0");
        setlocale(LC_CTYPE, 'fr_FR.ISO-8859-1');
        $count = preg_match_all($regexp, $html, $matches, PREG_SET_ORDER);
        setlocale(LC_CTYPE, $locale);
        return $matches;
    }

    /**
     * Return true if given text contains references
     *
     * @param String  $string
     *
     * @return bool
     */
    public function stringContainsReferences($string, Project $project)
    {
        return count($this->extractReferences($string, $project->getId())) > 0;
    }

    /**
     * extract references from text $html
     * @param $html the text to be extracted
     * @param $group_id the group_id of the project
     * @return array of {ReferenceInstance} : an array of project references extracted in the text $html
     */
    public function extractReferences($html, $group_id)
    {
        $this->tmpGroupIdForCallbackFunction = $group_id;
        $referencesInstances                 = array();
        $matches                             = $this->_extractAllMatches($html);
        foreach ($matches as $match) {
            $ref_instance = $this->_getReferenceInstanceFromMatch($match);
            if (! $ref_instance) {
                continue;
            }

            $ref = $ref_instance->getReference();

            // Replace description key with real description if needed
            $desc = '';
            if (strpos($ref->getDescription(), "_desc_key") !== false) {
                if (preg_match('/(.*):(.*)/', $ref->getDescription(), $matches)) {
                    if ($GLOBALS['Language']->hasText($matches[1], $matches[2])) {
                        $desc = $GLOBALS['Language']->getText($matches[1], $matches[2]);
                    }
                } else {
                    $desc = $GLOBALS['Language']->getText('project_reference', $ref->getDescription());
                }
            } else {
                $desc = $ref->getDescription();
            }
            $ref->setDescription($desc);

            $referencesInstances[] = $ref_instance;
        }

        foreach ($this->additional_references as $reftype) {
            $match = $this->_extractMatches($html, $reftype['regexp']);
            if (! empty($match)) {
                $ref = call_user_func($reftype['cb'], $match[0], $group_id);
                if (! empty($ref)) {
                    $referencesInstances[] = $ref;
                }
            }
        }

        $this->tmpGroupIdForCallbackFunction = null;
        return $referencesInstances;
    }

    /**
     * TODO : adapt it to the new tracker structure when ready
     */
    public function getArtifactKeyword($artifact_id, $group_id)
    {
        $sql = "SELECT group_artifact_id FROM artifact WHERE artifact_id= " . db_ei($artifact_id);
        $result = db_query($sql);
        if (db_numrows($result) > 0) {
            $row = db_fetch_array($result);
            $tracker_id = $row['group_artifact_id'];
            $project = new Project($group_id);
            $tracker = new ArtifactType($project, $tracker_id);
            $tracker_short_name = $tracker->getItemName();
            $reference_dao      = $this->_getReferenceDao();
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
     * @param int|string $source_id Id of the item where the text was added
     * @param String  $source_type Nature of the source
     * @param int $source_gid Project Id of the project the source item belongs to
     * @param int $user_id User who owns the text to parse
     * @param String  $source_key  Keyword to use for the reference (if different from the one associated to the nature)
     *
     * @retrun Boolean True if no error
     */
    public function extractCrossRef($html, $source_id, $source_type, $source_gid, $user_id = 0, $source_key = null)
    {
        $this->setProjectIdForProjectReferences($source_gid);

        $dao = $this->_getReferenceDao();

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

        if (! $user_id) {
            $user_id = UserManager::instance()->getCurrentUser()->getId();
        }
        $matches = $this->_extractAllMatches($html);
        foreach ($matches as $match) {
            $reference = $this->getReferenceFromMatch($match);
            if (! $reference) {
                continue;
            }

            $target_key  = strtolower($match['key']);
            $target_id   = $match['value'];
            $target_type = $reference->getNature();
            $target_gid  = $reference->getGroupId();

            $cross_reference = new CrossReference(
                $source_id,
                $source_gid,
                $source_type,
                $source_key,
                $target_id,
                $target_gid,
                $target_type,
                $target_key,
                $user_id
            );

            EventManager::instance()->processEvent(
                Event::POST_REFERENCE_EXTRACTED,
                array(
                    'cross_reference' => $cross_reference
                )
            );

            $res = $dao->searchByKeywordAndGroupId($target_key, $source_gid);
            if (count($res)) {
                $this->insertCrossReference($cross_reference);
            }
        }

        return true;
    }

    public function insertCrossReference(CrossReference $cross_reference)
    {
        $dao = $this->_getCrossReferenceDao();
        if (! $dao->existInDb($cross_reference)) {
            return $dao->createDbCrossRef($cross_reference);
        }

        return true;
    }

    public function removeCrossReference(CrossReference $cross_reference)
    {
        $is_reference_removed = false;
        EventManager::instance()->processEvent(
            Event::REMOVE_CROSS_REFERENCE,
            array(
                'cross_reference'      => $cross_reference,
                'is_reference_removed' => &$is_reference_removed
            )
        );

        if ($is_reference_removed) {
            return true;
        }

        return $this->_getCrossReferenceDao()->deleteCrossReference($cross_reference);
    }

    /**
     * extract references from text $html (same as extractReferences) but returns them grouped by Description, and removes the duplicates references
     * @param $html the text to be extracted
     * @param $group_id the group_id of the project
     * @return array referenceinstance with the following structure: array[$description][$match] = {ReferenceInstance}
     */
    public function extractReferencesGrouped($html, $group_id)
    {
        $referencesInstances = $this->extractReferences($html, $group_id);
        $groupedReferencesInstances = array();
        foreach ($referencesInstances as $idx => $referenceInstance) {
            $reference = $referenceInstance->getReference();
            // description to group the references
            // match to remove duplicates entries
            $groupedReferencesInstances[$reference->getDescription()][$referenceInstance->getMatch()] = $referenceInstance;
        }
        return $groupedReferencesInstances;
    }

    /**
     * Returns the group id of an artifact id
     *
     * @param int $artifact_id
     *
     * @return mixed False if no match, the group id otherwise
     */
    protected function getGroupIdFromArtifactId($artifact_id)
    {
        if (! TrackerV3::instance()->available()) {
            return false;
        }
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
     * @param int $artifact_id
     *
     * @return int
     */
    protected function getGroupIdFromArtifactIdForCallbackFunction($artifact_id)
    {
        $group_id = $this->getGroupIdFromArtifactId($artifact_id);
        if ($group_id === false) {
            $this->eventManager->processEvent(Event::GET_ARTIFACT_REFERENCE_GROUP_ID, array('artifact_id' => $artifact_id, 'group_id' => &$group_id));
        }
        return $group_id;
    }

    private function getReferenceFromMatch($match)
    {
        // Analyse match
        $keyword = strtolower($match['key']);
        $value   = $match['value'];

        if ($this->isAnArtifactKeyword($keyword)) {
            $ref_gid = $this->getGroupIdFromArtifactIdForCallbackFunction($value);
        } else {
            $ref_gid = $this->getProjectIdForReference($match, $keyword, $value);
        }

        return $this->getReference($keyword, $value, $ref_gid);
    }

    private function getProjectIdForReference($match, $keyword, $value)
    {
        $ref_gid = $this->getProjectIdFromMatch($match);

        if (! $ref_gid) {
            $ref_gid = $this->getProjectIdForSystemReference($keyword, $value);
        }

        if (! $ref_gid) {
            $ref_gid = $this->getCurrentProjectId();
        }

        if (! $ref_gid) {
            $ref_gid = Project::ADMIN_PROJECT_ID;
        }

        return $ref_gid;
    }

    private function getProjectIdFromMatch($match)
    {
        $ref_gid = null;

        if ($match['project_name']) {
            // A target project name or ID was specified
            // remove trailing colon
            $target_project = substr($match['project_name'], 0, strlen($match['project_name']) - 1);
            // id or name?
            if (is_numeric($target_project)) {
                $ref_gid = $target_project;
            } else {
                $ref_gid = $this->getProjectIdFromName($target_project);
            }
        }

        return $ref_gid;
    }

    private function getProjectIdForSystemReference($keyword, $value)
    {
        $ref_gid  = null;
        $nature   = $this->getSystemReferenceNatureByKeyword($keyword);

        switch ($nature) {
            case self::REFERENCE_NATURE_RELEASE:
                $release_factory = new FRSReleaseFactory();
                $release         = $release_factory->getFRSReleaseFromDb($value);

                if ($release) {
                    $ref_gid = $release->getProject()->getID();
                }

                break;
            case self::REFERENCE_NATURE_FILE:
                $file_factory = new FRSFileFactory();
                $file         = $file_factory->getFRSFileFromDb($value);

                if ($file) {
                    $ref_gid = $file->getGroup()->getID();
                }

                break;
            case self::REFERENCE_NATURE_FORUM:
                $forum_dao = new ForumDao();
                $forum_group_id_row = $forum_dao->searchByGroupForumId($value)->getRow();

                if ($forum_group_id_row) {
                    $ref_gid = $forum_group_id_row['group_id'];
                }

                break;
            case self::REFERENCE_NATURE_FORUMMESSAGE:
                $forum_dao            = new ForumDao();
                $message_group_id_row = $forum_dao->getMessageProjectIdAndForumId($value);

                if ($message_group_id_row) {
                    $ref_gid = $message_group_id_row['group_id'];
                }

                break;
            case self::REFERENCE_NATURE_NEWS:
                $news_dao          = new NewsBytesDao();
                $news_group_id_row = $news_dao->searchByForumId($value)->getRow();

                if ($news_group_id_row) {
                    $ref_gid = $news_group_id_row['group_id'];
                }

                break;
        }

        return $ref_gid;
    }

    /**
     * @return string
     */
    private function getSystemReferenceNatureByKeyword($keyword)
    {
        $dao                         = $this->_getReferenceDao();
        $system_reference_nature_row = $dao->getSystemReferenceNatureByKeyword($keyword);

        if (! $system_reference_nature_row) {
            return null;
        }

        return $system_reference_nature_row['nature'];
    }

    private function getCurrentProjectId()
    {
        $ref_gid = null;

        if ($this->tmpGroupIdForCallbackFunction) {
            $ref_gid = $this->tmpGroupIdForCallbackFunction;
        } elseif (array_key_exists('group_id', $GLOBALS)) {
            $ref_gid = $GLOBALS['group_id'];
        }

        return $ref_gid;
    }

    // Get a Reference object from a matching pattern
    // if it is not a reference (e.g. wrong keyword) return null;
    private function _getReferenceInstanceFromMatch($match)
    {
        // Analyse match
        $key   = strtolower($match['key']);
        $value = $match['value'];

        $ref = $this->getReferenceFromMatch($match);

        $refInstance = null;
        if ($ref) {
            $refInstance = new ReferenceInstance($key . " #" . $match['project_name'] . $value, $ref, $value);
            $refInstance->computeGotoLink($key, $value, $ref->getGroupId());
        }
        return $refInstance;
    }

    private function getReference($key, $value, $ref_gid)
    {
        $reference       = null;
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($ref_gid);

        $this->eventManager->processEvent(
            Event::GET_REFERENCE,
            array(
                'reference_manager' => $this,
                'project'           => $project,
                'keyword'           => $key,
                'value'             => $value,
                'group_id'          => $ref_gid,
                'reference'         => &$reference,
            )
        );

        if (! $reference) {
            $num_args  = substr_count($value, '/') + 1;
            $reference = $this->_getReferenceFromKeywordAndNumArgs($key, $ref_gid, $num_args);
        }

        return $reference;
    }

    /**
     * @return Reference
     */
    public function _getReferenceFromKeywordAndNumArgs($keyword, $group_id, $num_args)
    {
        $this->_initProjectReferences($group_id);
        $refs = $this->activeReferencesByProject[$group_id];
        // This part of the code prevent cross ref to wiki subpage to work
        // wiki #sub/page/2 should extract a link to the version 2 of the wikipage "sub/page"
        // References contains a "num_args" (args separated by '/') nevertheless
        // we don't know in advance the number of sub pages
        if (isset($refs["$keyword"])) {
            if (isset($refs["$keyword"][$num_args])) {
                return $refs["$keyword"][$num_args];
            }
        }

        return null;
    }

    public function _initProjectReferences($group_id)
    {
        if (!isset($this->activeReferencesByProject[$group_id])) {
            $p = array();
            $reference_dao = $this->_getReferenceDao();
            $dar = $reference_dao->searchActiveByGroupID($group_id);
            while ($row = $dar->getRow()) {
                $ref = $this->_buildReference($row);
                $num_args = $ref->getNumParam();
                if (!isset($p[$ref->getKeyword()])) {
                    $p[$ref->getKeyword()] = array();
                }
                if (isset($p[$ref->getKeyword()][$num_args])) {
                    // Project reference overload system reference
                    // (but you can't normally create such references, except in CX 2.6 to 2.8 migration)
                    if ($ref->isSystemReference()) {
                        continue;
                    }
                }
                $p[$ref->getKeyword()][$num_args] = $ref;
            }
            $this->activeReferencesByProject[$group_id] = $p;
        }
    }

    private function getProjectIdFromName($name)
    {
        $lowercase_name = strtolower($name);
        if (! isset($this->groupIdByName[$lowercase_name])) {
            $project = ProjectManager::instance()->getProjectByCaseInsensitiveUnixName($name);
            if ($project && !$project->isError()) {
                $this->groupIdByName[$lowercase_name] = $project->getID();
            } else {
                $this->groupIdByName[$lowercase_name] = '';
            }
        }
        return $this->groupIdByName[$lowercase_name];
    }

    public function _referenceNotUsed($refid)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchById($refid);
        if ($row = $dar->getRow()) {
            return false;
        } else {
            return true;
        }
    }

    public function _isKeywordExists($keyword, $group_id)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByKeywordAndGroupId($keyword, $group_id);
        if ($dar->rowCount() > 0) {
            return true;
        }

        return $this->getReferenceValidator()->isReservedKeyword($keyword);
    }

    public function checkKeyword($keyword)
    {
            // Check that there is no system reference with the same keyword
        if ($this->getReferenceValidator()->isSystemKeyword($keyword)) {
            return false;
        }
            // Check list of reserved keywords
        if ($this->getReferenceValidator()->isReservedKeyword($keyword)) {
            return false;
        }
            return true;
    }

    public function _keywordAndNumArgsExists($keyword, $num_args, $group_id)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar = $reference_dao->searchByKeywordAndGroupId($keyword, $group_id);
        $existing_refs = array();
        while ($row = $dar->getRow()) {
            if (Reference::computeNumParam($row['link']) == $num_args) {
                return $row['reference_id'];
            }
        }
        return false;
    }

    /**
     * @return ReferenceDao
     */
    public function _getReferenceDao()
    {
        if (!is_a($this->referenceDao, 'ReferenceDao')) {
            $this->referenceDao = new ReferenceDao(CodendiDataAccess::instance());
        }
        return $this->referenceDao;
    }

    public function _getCrossReferenceDao()
    {
        if (!is_a($this->cross_reference_dao, 'CrossReferenceDao')) {
            $this->cross_reference_dao = new CrossReferenceDao();
        }
        return $this->cross_reference_dao;
    }

    /**
     * Wrapper
     *
     * @return ArtifactDao
     */
    private function getArtifactDao()
    {
        return new ArtifactDao();
    }

    private function getReferenceValidator()
    {
        return new ReferenceValidator($this->_getReferenceDao(), $this->getReservedKeywordsRetriever());
    }

    private function setProjectIdForProjectReferences($project_id)
    {
        $GLOBALS['group_id'] = $project_id;
    }

    private function getReservedKeywordsRetriever()
    {
        return new ReservedKeywordsRetriever($this->eventManager);
    }

    public function getCrossReferenceByKeyword(string $keyword): array
    {
        $result = $this->_getCrossReferenceDao()->getReferenceByKeyword($keyword);

        if (! $result) {
            return [];
        }

        return $result;
    }
}
