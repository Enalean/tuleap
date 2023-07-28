<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

use Tuleap\Reference\CrossReference;
use Tuleap\Reference\CrossReferencesDao;
use Tuleap\Reference\ExtractReferences;
use Tuleap\Reference\GetReferenceEvent;
use Tuleap\Reference\Nature;
use Tuleap\Reference\ReferenceDescriptionTranslation;
use Tuleap\Reference\ReferenceInstance;
use Tuleap\Reference\ReferenceValidator;
use Tuleap\Reference\ReservedKeywordsRetriever;
use Tuleap\Reference\NatureCollection;

/**
 * Reference Manager
 * Performs all operations on references, including DB access (through ReferenceDAO)
 */
class ReferenceManager implements ExtractReferences // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * array of active Reference objects arrays of arrays, indexed by group_id, keyword, and num args.
     * Example: $activeReferencesByProject[101]['art'][1] return the reference object for project 101, keyword 'art' and one argument.
     * @var array
     */
    public $activeReferencesByProject = [];

    /**
     * array of Reference objects arrays indexed by group_id
     * Example: $activeReferencesByProject[101][1] return the first reference object for project 101
     * @var array
     */
    public $referencesByProject = [];

    public $referenceDao;
    private ?CrossReferencesDao $cross_reference_dao = null;

    private $groupIdByName = [];

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * Hold an instance of the class
     *
     * @var self
     */
    protected static $instance;

    public const KEYWORD_ARTIFACT_SHORT = 'art';
    public const KEYWORD_ARTIFACT_LONG  = 'artifact';

    public const REFERENCE_NATURE_ARTIFACT     = 'artifact';
    public const REFERENCE_NATURE_DOCUMENT     = 'document';
    public const REFERENCE_NATURE_CVSCOMMIT    = 'cvs_commit';
    public const REFERENCE_NATURE_SVNREVISION  = 'svn_revision';
    public const REFERENCE_NATURE_FILE         = 'file';
    public const REFERENCE_NATURE_RELEASE      = 'release';
    public const REFERENCE_NATURE_FORUM        = 'forum';
    public const REFERENCE_NATURE_FORUMMESSAGE = 'forum_message';
    public const REFERENCE_NATURE_NEWS         = 'news';
    public const REFERENCE_NATURE_WIKIPAGE     = 'wiki_page';
    public const REFERENCE_NATURE_OTHER        = 'other';

    /**
     * Not possible to give extra params to the call back function (_insertRefCallback in this case)
     * so we use an class attribute to pass the value of the group_id
     */
    public $tmpGroupIdForCallbackFunction = null;


    public function __construct()
    {
        $this->event_manager = EventManager::instance();
    }

    /**
     * @return ReferenceManager
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getAvailableNatures(): NatureCollection
    {
        $natures_collection = $this->event_manager->dispatch(new NatureCollection());
        assert($natures_collection instanceof NatureCollection);

        $natures_collection->addNature(
            self::REFERENCE_NATURE_ARTIFACT,
            new Nature(
                'art',
                Nature::NO_ICON,
                $GLOBALS['Language']->getText('project_reference', 'reference_artifact_nature_key'),
                true
            )
        );

        $natures_collection->addNature(
            self::REFERENCE_NATURE_DOCUMENT,
            new Nature(
                'doc',
                'fas fa-folder-open',
                $GLOBALS['Language']->getText('project_reference', 'reference_document_nature_key'),
                true
            )
        );

        $natures_collection->addNature(
            self::REFERENCE_NATURE_CVSCOMMIT,
            new Nature(
                'cvs',
                'fas ' . Service::ICONS[Service::CVS],
                $GLOBALS['Language']->getText('project_reference', 'reference_cvs_commit_nature_key'),
                true
            )
        );

        $natures_collection->addNature(
            self::REFERENCE_NATURE_SVNREVISION,
            new Nature(
                'svn',
                'fas fa-tlp-versioning-svn',
                $GLOBALS['Language']->getText('project_reference', 'reference_svn_revision_nature_key'),
                true
            )
        );

        $natures_collection->addNature(
            self::REFERENCE_NATURE_FILE,
            new Nature(
                'file',
                'far fa-copy',
                $GLOBALS['Language']->getText('project_reference', 'reference_file_nature_key'),
                true
            )
        );

        $natures_collection->addNature(
            self::REFERENCE_NATURE_RELEASE,
            new Nature(
                'release',
                'far fa-copy',
                $GLOBALS['Language']->getText('project_reference', 'reference_release_nature_key'),
                true
            )
        );

        $natures_collection->addNature(
            self::REFERENCE_NATURE_FORUM,
            new Nature(
                'forum',
                'fas ' . Service::ICONS[Service::FORUM],
                $GLOBALS['Language']->getText('project_reference', 'reference_forum_nature_key'),
                true
            )
        );

        $natures_collection->addNature(
            self::REFERENCE_NATURE_FORUMMESSAGE,
            new Nature(
                'msg',
                'fas ' . Service::ICONS[Service::FORUM],
                $GLOBALS['Language']->getText('project_reference', 'reference_forum_message_nature_key'),
                true
            )
        );

        $natures_collection->addNature(
            self::REFERENCE_NATURE_NEWS,
            new Nature(
                'news',
                'fas ' . Service::ICONS[Service::NEWS],
                $GLOBALS['Language']->getText('project_reference', 'reference_news_nature_key'),
                true
            )
        );

        $natures_collection->addNature(
            self::REFERENCE_NATURE_WIKIPAGE,
            new Nature(
                'wiki',
                'fas ' . Service::ICONS[Service::WIKI],
                $GLOBALS['Language']->getText('project_reference', 'reference_wiki_page_nature_key'),
                true
            )
        );

        $natures_collection->addNature(
            self::REFERENCE_NATURE_OTHER,
            new Nature(
                'other',
                Nature::NO_ICON,
                $GLOBALS['Language']->getText('project_reference', 'reference_other_nature_key'),
                true
            )
        );

        return $natures_collection;
    }

    /**
     * @return Reference[]
     */
    public function getReferencesByProject(\Project $project): array
    {
        return $this->getReferencesByGroupId((int) $project->getID());
    }

    /**
     * @return Reference[]
     */
    public function getReferencesByGroupId(int $group_id): array
    {
        if (isset($this->referencesByProject[$group_id])) {
            $p = $this->referencesByProject[$group_id];
        } else {
            $p             = [];
            $reference_dao = $this->_getReferenceDao();
            $dar           = $reference_dao->searchByGroupID($group_id);
            while ($row = $dar->getRow()) {
                $p[] = $this->buildReference($row);
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
        if (! $force) {
            // Check if keyword is valid [a-z0-9_]
            if (! $this->getReferenceValidator()->isValidKeyword($ref->getKeyword())) {
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
        if (! $this->getReferenceValidator()->isValidKeyword($ref->getKeyword())) {
            return false;
        }
        // Check that it is a system reference
        if (! $ref->isSystemReference()) {
            return false;
        }
        if ($ref->getGroupId() != 100) {
            return false;
        }

        // Create reference
        $rgid = $this->createReference($ref, $force);

        // Create reference for all groups
        // Ugly SQL, needed until we have a proper Group/GroupManager class
        $sql    = "SELECT group_id FROM `groups` WHERE group_id!=100";
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
        if (! $this->getReferenceValidator()->isValidKeyword($ref->getKeyword())) {
            return false;
        }

        // Check list of existing keywords
        $num_args = Reference::computeNumParam($ref->getLink());
        $refid    = $this->_keywordAndNumArgsExists($ref->getKeyword(), $num_args, $ref->getGroupId());
        if (! $force) {
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
        $dar           = $reference_dao->searchByKeywordAndGroupID($keyword, $group_id);
        $ref           = null;
        while ($row = $dar->getRow()) {
            $ref = $this->buildReference($row, $val);
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

        return $this->buildReference($dar, $reference_id);
    }

    public function loadReference($refid, $group_id)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar           = $reference_dao->searchByIdAndGroupID($refid, $group_id);
        $ref           = null;
        if ($row = $dar->getRow()) {
            $ref = $this->buildReference($row);
        }
        return $ref;
    }

    public function updateIsActive($ref, $is_active)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar           = $reference_dao->update_ref_group($ref->getId(), $is_active, $ref->getGroupId());
    }

    /**
     * Add all system references associated to the given service
     */
    public function addSystemReferencesForService($template_id, $group_id, $short_name)
    {
        $reference_dao = $this->_getReferenceDao();
        $dar           = $reference_dao->searchByScopeAndServiceShortName('S', $short_name);
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
        $dar           = $reference_dao->searchByScopeAndServiceShortName('S', "");
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
        $dar           = $reference_dao->searchByScopeAndServiceShortNameAndGroupId('P', "", $template_id);
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
    public function updateReferenceForService($group_id, $short_name, $is_active): void
    {
        $reference_dao = $this->_getReferenceDao();
        $dar           = $reference_dao->searchByServiceShortName((int) $group_id, $short_name);
        if (! $dar || $dar->isError()) {
            return;
        }
        while ($row = $dar->getRow()) {
            $reference_dao->update_ref_group($row['id'], $is_active, $group_id);
        }
    }

    /**
     * This method updates (rename) reference short name and related cross references
     */
    public function updateProjectReferenceShortName(int $group_id, string $old_short_name, string $new_short_name): void
    {
        $ref_dao = $this->_getReferenceDao();
        if ($ref_dao->updateProjectReferenceShortName($group_id, $old_short_name, $new_short_name) === false) {
            return;
        }
        $xref_dao = $this->_getCrossReferenceDao();
        $xref_dao->updateTargetKeyword($old_short_name, $new_short_name, $group_id);
        $xref_dao->updateSourceKeyword($old_short_name, $new_short_name, $group_id);
    }

    public function createSystemReferenceGroup($template_id, $group_id, $refid)
    {
        $reference_dao = $this->_getReferenceDao();
        $proj_ref      = $this->loadReference($refid, $template_id);// Is it active in template project ?
        $rgid          = $reference_dao->create_ref_group(
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

    public function buildReference($row, $val = null): Reference
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
                $this->event_manager->processEvent(
                    Event::BUILD_REFERENCE,
                    ['row' => $row, 'ref_id' => $refid, 'ref' => &$reference]
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
     */
    public function _getExpForRef(): string // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return "`
            (?(DEFINE)
                (?<final_value_sequence>\w|&amp;|&)                         # Any word, & or &amp;
                (?<extended_value_sequence>(?&final_value_sequence)|-|_|\.) # <final_value_sequence>, -, _ or .
            )
            (?:(?P<context_word>\w+)\s)?
            (?P<key>\w+)
            \s          #blank separator
            \#          #dash (2 en 1)
            (?P<project_name>[\w\-]+:)? #optional project name (followed by a colon)
            (?P<value>(?:(?:(?&extended_value_sequence)+/)*)?(?&final_value_sequence)+?) # Sequence of multiple '<extended_value_sequence>/' ending with <final_value_sequence>
            (?P<after_reference>&(?:\#(?:\d+|[xX][[:xdigit:]]+)|quot);|(?=[^\w&/])|$) # Exclude HTML dec, hex and some (quot) named entities from the end of the reference
        `x";
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
    private function buildLinkForReference(ReferenceInstance $ref_instance): string
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $ref      = $ref_instance->getReference();

        $reference_description_translation = new ReferenceDescriptionTranslation($ref);

        $context_word            = $ref_instance->getContextWord();
        $context_word_with_space = $context_word !== '' ? $context_word . ' ' : '';

        return sprintf(
            '%s<a href="%s" title="%s" class="cross-reference">%s</a>',
            $purifier->purify($context_word_with_space),
            $ref_instance->getFullGotoLink(),
            $purifier->purify($reference_description_translation->getTranslatedDescription()),
            $purifier->purify($ref_instance->getMatch())
        );
    }

    /**
     * insert html links in text
     * @param $html the string which may contain invalid
     */
    public function insertReferences(&$html, $group_id)
    {
        $this->tmpGroupIdForCallbackFunction = $group_id;

        if (! preg_match('/[^\s]{5000,}/', $html)) {
            $exp = $this->_getExpForRef();

            $html = preg_replace_callback(
                $exp,
                function ($match) {
                    $ref_instance = $this->_getReferenceInstanceFromMatch($match);
                    if (! $ref_instance) {
                        $context_word_with_space = $match['context_word'] !== '' ? $match['context_word'] . ' ' : '';
                        return $context_word_with_space . $match['key'] . " #" . $match['project_name'] . $match['value'] . $match['after_reference'];
                    }
                    return $this->buildLinkForReference($ref_instance) . $match['after_reference'];
                },
                $html
            );

            $this->insertLinksForMentions($html);
        }

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
            return $char_before . '<a href="/users/' . $username . '" class="direct-link-to-user">@' . $username . '</a>';
        }

        return $original_string;
    }

    /**
     * Extract all possible references from input text
     *
     * @param input text $html
     * @return array of matches
     */
    public function _extractAllMatches($html) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_extractMatches($html, $this->_getExpForRef());
    }

    /**
     * Extract matches from input text according to the regexp
     */
    private function _extractMatches($html, $regexp) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        preg_match_all($regexp, $html, $matches, PREG_SET_ORDER);
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
        return count($this->extractReferences($string, (int) $project->getId())) > 0;
    }

    public function extractReferences(string $html, int $group_id): array
    {
        $this->tmpGroupIdForCallbackFunction = $group_id;
        $referencesInstances                 = [];
        $matches                             = $this->_extractAllMatches($html);
        foreach ($matches as $match) {
            $ref_instance = $this->_getReferenceInstanceFromMatch($match);
            if (! $ref_instance) {
                continue;
            }

            $ref = $ref_instance->getReference();

            // Replace description key with real description if needed
            $ref->setDescription($ref->getResolvedDescription());

            $referencesInstances[] = $ref_instance;
        }

        $this->tmpGroupIdForCallbackFunction = null;
        return $referencesInstances;
    }

    /**
     * TODO : adapt it to the new tracker structure when ready
     */
    public function getArtifactKeyword($artifact_id, $group_id)
    {
        $sql    = "SELECT group_artifact_id FROM artifact WHERE artifact_id= " . db_ei($artifact_id);
        $result = db_query($sql);
        if (db_numrows($result) > 0) {
            $row                = db_fetch_array($result);
            $tracker_id         = $row['group_artifact_id'];
            $project            = new Project($group_id);
            $tracker            = new ArtifactType($project, $tracker_id);
            $tracker_short_name = $tracker->getItemName();
            $reference_dao      = $this->_getReferenceDao();
            $dar                = $reference_dao->searchByKeywordAndGroupId($tracker_short_name, $group_id);
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
                    $nature = $available_natures->getNatureFromIdentifier($source_type);
                    if ($nature) {
                        $source_key = $nature->keyword;
                    }
                }
            } else {
                $nature = $available_natures->getNatureFromIdentifier($source_type);
                if ($nature) {
                    $source_key = $nature->keyword;
                }
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
                (int) $source_id,
                $source_gid,
                $source_type,
                (string) $source_key,
                $target_id,
                $target_gid,
                $target_type,
                $target_key,
                $user_id
            );

            EventManager::instance()->processEvent(
                Event::POST_REFERENCE_EXTRACTED,
                [
                    'cross_reference' => $cross_reference,
                ]
            );

            $res = $dao->searchByKeywordAndGroupId($target_key, $source_gid);
            if (count($res)) {
                $this->insertCrossReference($cross_reference);
            }
        }

        return true;
    }

    public function insertCrossReference(CrossReference $cross_reference): bool
    {
        $dao = $this->_getCrossReferenceDao();
        if (! $dao->existInDb($cross_reference)) {
            return $dao->createDbCrossRef($cross_reference);
        }

        return true;
    }

    public function removeCrossReference(CrossReference $cross_reference): bool
    {
        $is_reference_removed = false;
        EventManager::instance()->processEvent(
            Event::REMOVE_CROSS_REFERENCE,
            [
                'cross_reference' => $cross_reference,
                'is_reference_removed' => &$is_reference_removed,
            ]
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
     * @return array referenceinstance with the following structure: array[$description][$match] = {Tuleap\Reference\ReferenceInstance}
     */
    public function extractReferencesGrouped($html, $group_id)
    {
        $referencesInstances        = $this->extractReferences($html, (int) $group_id);
        $groupedReferencesInstances = [];
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
            $this->event_manager->processEvent(Event::GET_ARTIFACT_REFERENCE_GROUP_ID, ['artifact_id' => $artifact_id, 'group_id' => &$group_id]);
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
        $ref_gid = null;
        $nature  = $this->getSystemReferenceNatureByKeyword($keyword);

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
                $forum_dao          = new ForumDao();
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
    private function _getReferenceInstanceFromMatch($match): ?ReferenceInstance // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // Analyse match
        $key   = strtolower($match['key']);
        $value = $match['value'];

        $ref = $this->getReferenceFromMatch($match);

        if (! $ref) {
            return null;
        }
        return new ReferenceInstance(
            $key . " #" . $match['project_name'] . $value,
            $ref,
            $value,
            $key,
            $ref->getGroupId(),
            $match['context_word'],
        );
    }

    private function getReference($key, $value, $ref_gid)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($ref_gid);

        $event = new GetReferenceEvent(
            $this,
            $project,
            $key,
            $value
        );

        $this->event_manager->dispatch($event);

        $reference = $event->getReference();
        if ($reference === null) {
            $num_args  = substr_count($value, '/') + 1;
            $reference = $this->_getReferenceFromKeywordAndNumArgs($key, $ref_gid, $num_args);
        }

        return $reference;
    }

    /**
     * @return Reference
     */
    public function _getReferenceFromKeywordAndNumArgs($keyword, $group_id, $num_args) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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

    public function _initProjectReferences($group_id) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (! isset($this->activeReferencesByProject[$group_id])) {
            $p             = [];
            $reference_dao = $this->_getReferenceDao();
            $dar           = $reference_dao->searchActiveByGroupID($group_id);
            while ($row = $dar->getRow()) {
                $ref      = $this->buildReference($row);
                $num_args = $ref->getNumParam();
                if (! isset($p[$ref->getKeyword()])) {
                    $p[$ref->getKeyword()] = [];
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
            if ($project && ! $project->isError()) {
                $this->groupIdByName[$lowercase_name] = $project->getID();
            } else {
                $this->groupIdByName[$lowercase_name] = '';
            }
        }
        return $this->groupIdByName[$lowercase_name];
    }

    public function _referenceNotUsed($refid) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $reference_dao = $this->_getReferenceDao();
        $dar           = $reference_dao->searchById($refid);
        if ($row = $dar->getRow()) {
            return false;
        } else {
            return true;
        }
    }

    public function _isKeywordExists($keyword, $group_id) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $reference_dao = $this->_getReferenceDao();
        $dar           = $reference_dao->searchByKeywordAndGroupId($keyword, $group_id);
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

    public function _keywordAndNumArgsExists($keyword, $num_args, $group_id) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $reference_dao = $this->_getReferenceDao();
        $dar           = $reference_dao->searchByKeywordAndGroupId($keyword, $group_id);
        $existing_refs = [];
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
    public function _getReferenceDao() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (! $this->referenceDao instanceof \ReferenceDao) {
            $this->referenceDao = new ReferenceDao(CodendiDataAccess::instance());
        }
        return $this->referenceDao;
    }

    public function _getCrossReferenceDao() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (! $this->cross_reference_dao instanceof CrossReferencesDao) {
            $this->cross_reference_dao = new CrossReferencesDao();
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
        return new ReservedKeywordsRetriever($this->event_manager);
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
