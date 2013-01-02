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

require_once(dirname(__FILE__).'/../../constants.php');
require_once TRACKER_BASE_DIR.'/Tracker/Artifact/Redirect.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/Tracker_History.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/TrackerFactory.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/FormElement/Tracker_FormElementFactory.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/Tracker_Dispatchable_Interface.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/NoChangeException.class.php';
require_once('Tracker_Artifact_Changeset.class.php');
require_once('Tracker_Artifact_Changeset_Null.class.php');
require_once('dao/Tracker_Artifact_ChangesetDao.class.php');
require_once('dao/PriorityDao.class.php');
require_once('common/reference/CrossReferenceFactory.class.php');
require_once('common/reference/CrossReferenceManager.class.php');
require_once('www/project/admin/permissions.php');
require_once('common/include/Recent_Element_Interface.class.php');

class Tracker_Artifact implements Recent_Element_Interface, Tracker_Dispatchable_Interface {
    const PERMISSION_ACCESS = 'PLUGIN_TRACKER_ARTIFACT_ACCESS';
    const REFERENCE_NATURE  = 'plugin_tracker_artifact';

    public $id;
    public $tracker_id;
    public $use_artifact_permissions;
    protected $submitted_by;
    protected $submitted_on;

    protected $changesets;

    /**
     * @var array of Tracker_Artifact
     */
    private $ancestors;

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var Tracker_FormElementFactory
     */
    private $formElementFactory;

    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;

    /**
     * @var string
     */
    private $title;

    /**
     * Constructor
     *
     * @param int     $id                       The Id of the artifact
     * @param int     $tracker_id               The tracker Id the artifact belongs to
     * @param int     $submitted_by             The id of the user who's submitted the artifact
     * @param int     $submitted_on             The timestamp of artifact submission
     *
     * @param boolean $use_artifact_permissions True if this artifact uses permission, false otherwise
     */
    public function __construct($id, $tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions) {
        $this->id                       = $id;
        $this->tracker_id               = $tracker_id;
        $this->submitted_by             = $submitted_by;
        $this->submitted_on             = $submitted_on;
        $this->use_artifact_permissions = $use_artifact_permissions;

    }

    /**
     * Return true if given given artifact refer to the same DB object (basically same id).
     *
     * @param Tracker_Artifact $artifact
     *
     * @return Boolean
     */
    public function equals(Tracker_Artifact $artifact = null) {
        return $artifact && $this->id == $artifact->getId();
    }

    /**
    * Set the value of use_artifact_permissions
    *
    * @param bool $use_artifact_permissions
    *
    * @return bool true if the artifact has individual permissions set
    */
    public function setUseArtifactPermissions($use_artifact_permissions) {
        $this->use_artifact_permissions = $use_artifact_permissions;
    }

    /**
     * useArtifactPermissions
     * @return bool true if the artifact has individual permissions set
     */
    public function useArtifactPermissions() {
        return $this->use_artifact_permissions;
    }

    /**
     * userCanView - determine if the user can view this artifact.
     *
     * @param User $user if not specified, use the current user
     *
     * @return boolean user can view the artifact
     */
    public function userCanView(User $user = null) {
        $um = $this->getUserManager();
        if (!$user) {
            $user = $um->getCurrentUser();
        }

        // Super-user has all rights...
        if ($user->isSuperUser()) {
            return true;
        }

        //Individual artifact permission
        $can_access = ! $this->useArtifactPermissions();
        if (!$can_access) {
            $rows = $this->permission_db_authorized_ugroups('PLUGIN_TRACKER_ARTIFACT_ACCESS');
            if ( $rows !== false ) {
                foreach ( $rows as $row ) {
                    if ($user->isMemberOfUGroup($row['ugroup_id'], $this->getTracker()->getGroupId())) {
                        $can_access = true;
                    }
                }
            }
        }
        if ($can_access) {
            $permissions = $this->getTracker()->getPermissionsAuthorizedUgroups();
            foreach ($permissions  as $permission => $ugroups) {
                switch($permission) {
                    // Full access
                    case 'PLUGIN_TRACKER_ACCESS_FULL':
                        foreach ($ugroups as $ugroup) {
                            if ($user->isMemberOfUGroup($ugroup, $this->getTracker()->getGroupId())) {
                                return true;
                            }
                        }
                        break;
                    // 'submitter' access
                    case 'PLUGIN_TRACKER_ACCESS_SUBMITTER':
                        foreach ($ugroups as $ugroup) {
                            if ($user->isMemberOfUGroup($ugroup, $this->getTracker()->getGroupId())) {
                                // check that submitter is also a member
                                $user_subby = $um->getUserById($this->getSubmittedBy());
                                if ($user_subby->isMemberOfUGroup($ugroup, $this->getTracker()->getGroupId())) {
                                    return true;
                                }
                            }
                        }
                    break;
                    // 'assignee' access
                    case 'PLUGIN_TRACKER_ACCESS_ASSIGNEE':
                        foreach ($ugroups as $ugroup) {
                            if ($user->isMemberOfUGroup($ugroup, $this->getTracker()->getGroupId())) {
                                $contributor_field = $this->getTracker()->getContributorField();
                                if ($contributor_field) {
                                    // check that one of the assignees is also a member
                                    $assignees = $this->getValue($contributor_field)->getValue();
                                    foreach ($assignees as $assignee) {
                                        $user_assignee = $um->getUserById($assignee);
                                        if ($user_assignee->isMemberOfUGroup( $ugroup, $this->getTracker()->getGroupId())) {
                                            return true;
                                        }
                                    }
                                }
                            }
                        }
                    break;
                }
            }
        }
        return false;
    }

    public function permission_db_authorized_ugroups( $permission_type ) {
        $result = array();
        $res    = permission_db_authorized_ugroups($permission_type, $this->getId());
        if ( db_numrows($res) > 0 ) {
            while ( $row = db_fetch_array($res) ) {
                $result[] = $row;
            }
            return $result;
        } else {
            return false;
        }
    }


    /**
     * This method returns the artifact mail rendering
     *
     * @param array  $recipient
     * @param string $format, the mail format text or html
     * @param bool   $ignore_perms, indicates if we ignore various permissions
     *
     * @return string
     */
    public function fetchMail($recipient, $format, $ignore_perms=false) {
        $output = '';
        switch($format) {
            case 'html':
                $content = $this->fetchMailFormElements($recipient, $format, $ignore_perms);
                if ($content) {
                    $output .= '<h2>'.$GLOBALS['Language']->getText('plugin_tracker_artifact_changeset', 'header_html_snapshot').'</h2>';
                    $output .= $content;
                }

                $output .= $this->fetchMailFollowUp($recipient, $format, $ignore_perms);
                break;
            default:
                $output .= PHP_EOL;
                //fields formelements
                $output .= $this->fetchMailFormElements($recipient, $format, $ignore_perms);
                $output .= $this->fetchMailFollowUp($recipient, $format, $ignore_perms);
                break;
        }
        return $output;
    }

    /**
     * Returns the artifact field for mail rendering
     *
     * @param array  $recipient
     * @param string $format, the mail format text or html
     * @param bool   $ignore_perms, indicates if we ignore various permissions
     *
     * @return String
     */
    public function fetchMailFormElements($recipient, $format, $ignore_perms=false) {
        $text = '';
        foreach ($this->getTracker()->getFormElements() as $formElement) {
            $formElement->prepareForDisplay();
        }
        foreach ($this->getTracker()->getFormElements() as $formElement) {
            $output = $formElement->fetchMailArtifact($recipient, $this, $format, $ignore_perms);
            $text .= $output;
            if ($format == 'text' && $output) {
                $text .= PHP_EOL;
            }
        }
        return $text;
    }

    /**
     * Returns the artifact followup for mail rendering
     *
     * @param array  $recipient
     * @param string $format, the mail format text or html
     * @param bool   $ignore_perms, indicates if we ignore various permissions
     *
     * @return String
     */
    public function fetchMailFollowUp($recipient, $format, $ignore_perms=false) {
        $uh = UserHelper::instance();
        $um = UserManager::instance();
        $cs = $this->getChangesets();
        $hp = Codendi_HTMLPurifier::instance();
        $output = '';
        foreach ( $cs as $changeset ) {
            $comment = $changeset->getComment();
            $changes = $changeset->diffToPrevious($format, $recipient, $ignore_perms);
            if (empty($comment)) {
                //do not display empty comment
                continue;
            }
            switch ($format) {
                case 'html':
                    $followup = $comment->fetchFollowUp($format, true);
                    if(!empty($followup)) {
                        if(!isset($output)) {
                            $output = '<h2>'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','follow_ups').'</h2>';
                        }
                        $output .= '<div class="tracker_artifact_followup_header">';
                        $output .= $followup;
                        $output .= '</div>';
                    }
                    break;
                case 'text':
                    $user = $um->getUserById($comment->submitted_by);
                    $output .= PHP_EOL;
                    $output .= '----------------------------- ';
                    $output .= PHP_EOL;
                    $output .= $GLOBALS['Language']->getText('plugin_tracker_artifact','mail_followup_date') . util_timestamp_to_userdateformat($comment->submitted_on);
                    $output .= "\t" . $GLOBALS['Language']->getText('plugin_tracker_artifact','mail_followup_by') . $uh->getDisplayNameFromUser($user);
                    $output .= PHP_EOL;
                    $output .= $comment->getPurifiedBodyForText();
                    $output .= PHP_EOL;
                    $output .= PHP_EOL;
                    break;
                default:
                    $output .= '<!-- TODO -->';
                    break;
            }
        }
        return $output;
    }
    /**
     * Fetch the tooltip displayed on an artifact reference
     *
     * @param User $user The user who fetch the tooltip
     *
     * @return string html
     */
    public function fetchTooltip($user) {
        $tooltip = $this->getTracker()->getTooltip();
        $html = '';
        if ($this->userCanView($user)) {
            $fields = $tooltip->getFields();
            if (!empty($fields)) {
                $html .= '<table>';
                foreach ($fields as $f) {
                    //TODO: check field permissions
                    $html .= $f->fetchTooltip($this);
                }
                $html .= '</table>';
            }
        }
        return $html;
    }

    /**
     * Fetch the artifact for the MyArtifact widget
     *
     * @param string $item_name The short name of the tracker this artifact belongs to
     * @param string $title     The title of this artifact
     *
     * @return string html
     */
    public function fetchWidget($item_name, $title) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $html .= '<a class="direct-link-to-artifact" href="'.TRACKER_BASE_URL.'/?aid='. $this->id .'" title="Display artifact #'. $this->id .'">'. $GLOBALS['HTML']->getImage('ic/artifact-arrow.png', array('alt' => '#'.$this->id)) .'</a> ';
        $html .= '<a class="direct-link-to-artifact" href="'.TRACKER_BASE_URL.'/?aid=' . $this->id . '">';
        $html .= $hp->purify($item_name, CODENDI_PURIFIER_CONVERT_HTML);
        $html .= ' #';
        $html .= $this->id;
        if ($title) {
            $html .= ' - ';
            $html .= $title;
        }

        $html .= '</a>';
        return $html;
    }

    /**
     * Display the artifact
     *
     * @param Tracker_IDisplayTrackerLayout  $layout          Displays the page header and footer
     * @param Codendi_Request                $request         The data coming from the user
     * @param User                           $current_user    The current user
     *
     * @return void
     */
    public function display(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        // the following statement needs to be called before displayHeader
        // in order to get the feedback, if any
        $hierarchy = $this->getAllAncestors($current_user);

        $hp          = Codendi_HTMLPurifier::instance();
        $tracker     = $this->getTracker();
        $title       = $hp->purify($tracker->item_name, CODENDI_PURIFIER_CONVERT_HTML)  .' #'. $this->id;
        $breadcrumbs = array(
            array('title' => $title,
                  'url'   => TRACKER_BASE_URL.'/?aid='. $this->id)
        );
        $tracker->displayHeader($layout, $title, $breadcrumbs);

        $current_user->addRecentElement($this);
        $from_aid = $request->get('from_aid');

        $html = '';

        $redirect = new Tracker_Artifact_Redirect();
        $redirect->base_url = TRACKER_BASE_URL;
        $redirect->query_parameters = array(
            'aid'       => $this->id,
            'func'      => 'artifact-update',
        );

        if ($from_aid != null) {
            $redirect->query_parameters['from_aid'] = $from_aid;
        }
        EventManager::instance()->processEvent(
            TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION,
            array(
                'request'  => $request,
                'redirect' => $redirect,
            )
        );

        $html .= '<form action="'. $redirect->toUrl() .'" method="POST" enctype="multipart/form-data">';


        $html .= '<input type="hidden" value="67108864" name="max_file_size" />';

        $html .= $this->fetchTitleInHierarchy($hierarchy);

        $html .= $this->fetchFields($request->get('artifact'));

        $html .= $this->fetchFollowUps($current_user, $request->get('artifact_followup_comment'));

        // We don't need History since we have changesets
        //$html .= $this->_fetchHistory();

        $html .= '</form>';
        $trm = new Tracker_RulesManager($tracker, $this->getFormElementFactory());
        $html .= $trm->displayRulesAsJavascript();

        echo $html;

        $tracker->displayFooter($layout);
        exit();
    }

    private function fetchTitleInHierarchy(array $hierarchy) {
        $hp    = Codendi_HTMLPurifier::instance();
        $html  = '';
        $html .= $this->fetchHiddenTrackerId();
        if ($hierarchy) {
            array_unshift($hierarchy, $this);
            $html .= $this->fetchParentsTitle($hp, $hierarchy);
        } else {
            $html .= $this->fetchTitle();
        }
        return $html;
    }

    private function fetchParentsTitle(Codendi_HTMLPurifier $hp, array $parents, $padding_prefix = '') {
        $html   = '';
        $parent = array_pop($parents);
        if ($parent) {
            $html .= '<ul class="tracker-hierarchy">';
            $html .= '<li>';
            $html .= $padding_prefix;
            $html .= '<div class="tree-last">&nbsp;</div> ';
            if ($parents) {
                $html .= $parent->fetchDirectLinkToArtifactWithTitle();
            } else {
                $html .= $hp->purify($parent->getXRefAndTitle());
            }
            if ($parents) {
                $html .= '</a>';
                $div_prefix = '';
                $div_suffix = '';
                if (count($parents) == 1) {
                    $div_prefix = '<div class="tracker_artifact_title">';
                    $div_suffix = '</div>';
                }
                $html .= $div_prefix;
                $html .= $this->fetchParentsTitle($hp, $parents, $padding_prefix . '<div class="tree-blank">&nbsp;</div>');
                $html .= $div_suffix;
            }
            $html .= '</li>';
            $html .= '</ul>';
        }
        return $html;
    }

    /**
     * Returns HTML code to display the artifact title
     *
     * @param string $prefix The prefix to display before the title of the artifact. Default is blank.
     *
     * @return string The HTML code for artifact title
     */
    public function fetchTitle($prefix = '') {
        $html = '';
        $hp = Codendi_HTMLPurifier::instance();
        $html .= $this->fetchHiddenTrackerId();
        $html .= '<div class="tracker_artifact_title">';
        $html .= $prefix;
        $html .= $hp->purify($this->getXRefAndTitle(), CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</div>';
        return $html;
    }

    public function fetchHiddenTrackerId() {
        return '<input type="hidden" id="tracker_id" name="tracker_id" value="'.$this->getTrackerId().'"/>';
    }

    public function getXRefAndTitle() {
        return $this->getXRef() .' - '. $this->getTitle();
    }
    /**
     * Get the artifact title, or null if no title defined in semantics
     *
     * @return string the title of the artifact, or null if no title defined in semantics
     */
    public function getTitle() {
        if ( ! isset($this->title)) {
            $this->title = null;
            if ($title_field = Tracker_Semantic_Title::load($this->getTracker())->getField()) {
                if ($title_field->userCanRead()) {
                    if ($last_changeset = $this->getLastChangeset()) {
                        if ($title_field_value = $last_changeset->getValue($title_field)) {
                            $this->title = $title_field_value->getText();
                        }
                    }
                }
            }
        }
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Get the artifact status, or null if no status defined in semantics
     *
     * @return string the status of the artifact, or null if no status defined in semantics
     */
    public function getStatus() {
        if ($status_field = Tracker_Semantic_Status::load($this->getTracker())->getField()) {
            return $status_field->getFirstValueFor($this->getLastChangeset());
        }
        return null;
    }

    /**
     *
     * @param <type> $recipient
     * @param <type> $ignore_perms
     * @return <type>
     */
    public function fetchMailTitle($recipient, $format = 'text', $ignore_perms = false) {
        $output = '';
        if ( $title_field = Tracker_Semantic_Title::load($this->getTracker())->getField() ) {
            if ( $ignore_perms || $title_field->userCanRead($recipient) ) {
                if ($value = $this->getLastChangeset()->getValue($title_field)) {
                    if ($title = $value->getText() ) {
                        $output .= $title;
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Returns HTML code to display the artifact fields
     *
     * @param array $submitted_values array of submitted values
     *
     * @return string The HTML code for artifact fields
     */
    protected function fetchFields($submitted_values=array()) {
        $html = '';
        $html .= '<table cellspacing="0" cellpadding="0" border="0"><tr valign="top"><td style="padding-right:1em;">';
        $html .= $this->getTracker()->fetchFormElements($this, array($submitted_values));

        return $html;
    }

    protected function fetchAnonymousEmailForm() {
        $html = '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_artifact', 'not_logged_in', array('/account/login.php?return_to='.urlencode($_SERVER['REQUEST_URI'])));
        $html .= '<br />';
        $html .= '<input type="text" name="email" id="email" size="50" maxsize="100" />';
        $html .= '</p>';
        return $html;
    }

    /**
     * Returns HTML code to display the artifact follow-up comments
     *
     * @param User $current_user the current user
     *
     * @return string The HTML code for artifact follow-up comments
     */
    protected function fetchFollowUps($current_user, $submitted_comment = '') {
        $html = '';

        $html_submit_button = '<p style="text-align:center;">';
        $html_submit_button .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $html_submit_button .= ' ';
        $html_submit_button .= '<input type="submit" name="submit_and_stay" value="'. $GLOBALS['Language']->getText('global', 'btn_submit_and_stay') .'" />';
        $html_submit_button .= '</p>';

        $html .= $html_submit_button;

        $html .= '<fieldset id="tracker_artifact_followup_comments"><legend
                          class="'. Toggler::getClassName('tracker_artifact_followups', true, true) .'"
                          id="tracker_artifact_followups">'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','follow_ups').'</legend>';
        $html .= '<ul class="tracker_artifact_followups">';
        $previous_changeset = null;
        $i = 0;
        foreach ($this->getChangesets() as $changeset) {
            if ($previous_changeset) {
                $html .= '<li id="followup_'. $changeset->id .'" class="'. html_get_alt_row_color($i++) .' tracker_artifact_followup">';
                $html .= $changeset->fetchFollowUp($previous_changeset);
                $html .= '</li>';
            }
            $previous_changeset = $changeset;
        }

        $html .= '<li>';
        $html .= '<div class="'. html_get_alt_row_color($i++) .'">';
        $hp = Codendi_HTMLPurifier::instance();

        if (count($responses = $this->getTracker()->getCannedResponseFactory()->getCannedResponses($this->getTracker()))) {
            $html .= '<p><b>' . $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'use_canned') . '</b>&nbsp;';
            $html .= '<select id="tracker_artifact_canned_response_sb">';
            $html .= '<option selected="selected" value="">--</option>';
            foreach ($responses as $r) {
                $html .= '<option value="'.  $hp->purify($r->body, CODENDI_PURIFIER_CONVERT_HTML) .'">'.  $hp->purify($r->title, CODENDI_PURIFIER_CONVERT_HTML) .'</option>';
            }
            $html .= '</select>';
            $html .= '<noscript> javascript must be enabled to use this feature! </noscript>';
            $html .= '</p>';
        }
        $html .= '<b>'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'add_comment') .'</b><br />';
        $html .= '<textarea id="tracker_followup_comment_new" wrap="soft" rows="12" cols="80" style="width:99%;" name="artifact_followup_comment" id="artifact_followup_comment">'. $hp->purify($submitted_comment, CODENDI_PURIFIER_CONVERT_HTML).'</textarea>';
        $html .= '</div>';

        if ($current_user->isAnonymous()) {
            $html .= $this->fetchAnonymousEmailForm();
        }
        $html .= '</li>';

        $html .= '</ul>';
        $html .= '</fieldset>';

        $html .= $html_submit_button;

        $html .= '</td></tr></table>'; //see fetchFields

        return $html;
    }

    /**
     * Returns HTML code to display the artifact history
     *
     * @return string The HTML code for artifact history
     */
    protected function fetchHistory() {
        $html = '';
        $html .= '<h4 class="tracker_artifact_tab">History</h4>';
        $h = new Tracker_History($this);
        $html .= $h->fetch();
        return $html;
    }

    /**
     * Returns HTML code to display the artifact history
     *
     * @param Codendi_Request $request The data from the user
     *
     * @return String The valid followup comment format
     */
    private function validateCommentFormat($request, $comment_format_field_name) {
        $comment_format = $request->get($comment_format_field_name);
        return Tracker_Artifact_Changeset_Comment::checkCommentFormat($comment_format);
    }

    /**
     * Process the artifact functions
     *
     * @param Tracker_IDisplayTrackerLayout  $layout          Displays the page header and footer
     * @param Codendi_Request                $request         The data from the user
     * @param User                           $current_user    The current user
     *
     * @return void
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        switch ($request->get('func')) {
            case 'update-comment':
                if ((int)$request->get('changeset_id') && $request->get('content')) {
                    if ($changeset = $this->getChangeset($request->get('changeset_id'))) {
                        $comment_format = $this->validateCommentFormat($request, 'comment_format');
                        $changeset->updateComment($request->get('content'), $current_user, $comment_format);
                        if ($request->isAjax()) {
                            //We assume that we can only change a comment from a followUp
                            echo $changeset->getComment()->fetchFollowUp();
                        }
                    }
                }
                break;
            case 'preview-attachment':
            case 'show-attachment':
                if ((int)$request->get('field') && (int)$request->get('attachment')) {
                    $ff = Tracker_FormElementFactory::instance();
                    //TODO: check that the user can read the field
                    if ($field = $ff->getFormElementByid($request->get('field'))) {
                        $method = explode('-', $request->get('func'));
                        $method = $method[0];
                        $method .= 'Attachment';
                        if (method_exists($field, $method)) {
                            $field->$method($request->get('attachment'));
                        }
                    }
                }
                break;
            case 'artifact-delete-changeset':
                // @see comment in Tracker_Artifact_Changeset::fetchFollowUp()
                //if ($changeset = $this->getChangeset($request->get('changeset'))) {
                //    $changeset->delete($current_user);
                //}
                $GLOBALS['Response']->redirect('?aid='. $this->id);
                break;
            case 'artifact-update':
                //TODO : check permissions on this action?
                $fields_data   = $request->get('artifact');
                $comment_format = $this->validateCommentFormat($request, 'comment_formatnew');
                $this->setUseArtifactPermissions( $request->get('use_artifact_permissions') ? 1 : 0 );
                $this->getTracker()->augmentDataFromRequest($fields_data);
                try {
                    $this->createNewChangeset($fields_data, $request->get('artifact_followup_comment'), $current_user, $request->get('email'), true, $comment_format);
                    
                    $art_link = $this->fetchDirectLinkToArtifact();
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_index', 'update_success', array($art_link)), CODENDI_PURIFIER_LIGHT);

                    $redirect = $this->getRedirectUrlAfterArtifactUpdate($request, $this->tracker_id, $this->getId());
                    $this->summonArtifactRedirectors($request, $redirect);
                    if ($request->isAjax()) {
                        $this->sendAjaxCardsUpdateInfo($current_user);
                    } else {
                        $GLOBALS['Response']->redirect($redirect->toUrl());
                    }
                } catch (Tracker_NoChangeException $e) {
                    $GLOBALS['Response']->addFeedback('info', $e->getMessage(), CODENDI_PURIFIER_LIGHT);
                    $this->display($layout, $request, $current_user);
                } catch (Tracker_Exception $e) {
                    $GLOBALS['Response']->addFeedback('error', $e->getMessage());
                    $this->display($layout, $request, $current_user);
                }
                break;
            case 'unassociate-artifact-to':
                $artlink_fields     = $this->getFormElementFactory()->getUsedArtifactLinkFields($this->getTracker());
                $linked_artifact_id = $request->get('linked-artifact-id');
                if (count($artlink_fields)) {
                    $this->unlinkArtifact($artlink_fields, $linked_artifact_id, $current_user);
                    $this->summonArtifactAssociators($request, $current_user, $linked_artifact_id);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker', 'must_have_artifact_link_field'));
                    $GLOBALS['Response']->sendStatusCode(400);
                }
                break;
            case 'associate-artifact-to':
                $linked_artifact_id = $request->get('linked-artifact-id');
                if (!$this->linkArtifact($linked_artifact_id, $current_user)) {
                    $GLOBALS['Response']->sendStatusCode(400);
                } else {
                    $this->summonArtifactAssociators($request, $current_user, $linked_artifact_id);
                }
                break;
            case 'higher-priority-than':
                $dao = new Tracker_Artifact_PriorityDao();
                $dao->moveArtifactBefore($this->getId(), (int)$request->get('target-id'));
                break;
            case 'lesser-priority-than':
                $dao = new Tracker_Artifact_PriorityDao();
                $dao->moveArtifactAfter($this->getId(), (int)$request->get('target-id'));
                break;
            default:
                if ($request->isAjax()) {
                    echo $this->fetchTooltip($current_user);
                } else {
                    $this->display($layout, $request, $current_user);
                }
                break;
        }
    }

    private function sendAjaxCardsUpdateInfo(User $current_user) {
        $cards_info = $this->getCardUpdateInfo($this, $current_user);
        $parent = $this->getParent($current_user);
        if ($parent) {
            $cards_info = $cards_info + $this->getCardUpdateInfo($parent, $current_user);
        }

        $GLOBALS['Response']->sendJSON($cards_info);
    }

    private function getCardUpdateInfo(Tracker_Artifact $artifact, User $current_user) {
        $card_info               = array();
        $tracker_id              = $artifact->getTracker()->getId();
        $form_element_factory    = $this->getFormElementFactory();
        $remaining_effort_field  = $form_element_factory->getComputableFieldByNameForUser(
            $tracker_id,
            Tracker::REMAINING_EFFORT_FIELD_NAME,
            $current_user
        );

        if ($remaining_effort_field) {
            $remaining_effort = $remaining_effort_field->fetchCardValue($artifact);
            $card_info[$artifact->getId()] = array(
                Tracker::REMAINING_EFFORT_FIELD_NAME => $remaining_effort
            );
        }
        return $card_info;
    }


    /**
     * @return string html
     */
    public function fetchDirectLinkToArtifact() {
        return '<a class="direct-link-to-artifact" href="'. $this->getUri() . '">' . $this->getXRef() . '</a>';
    }

    /**
     * @return string html
     */
    public function fetchDirectLinkToArtifactWithTitle() {
        $hp = Codendi_HTMLPurifier::instance();
        return '<a class="direct-link-to-artifact" href="'. $this->getUri() . '">' . $hp->purify($this->getXRefAndTitle()) . '</a>';
    }

    /**
     * @return string html
     */
    public function fetchDirectLinkToArtifactWithoutXRef() {
        $hp = Codendi_HTMLPurifier::instance();
        return '<a class="direct-link-to-artifact" href="'. $this->getUri() . '">' . $hp->purify($this->getTitle()) . '</a>';
    }

    /**
     * @return string
     */
    public function getUri() {
        return TRACKER_BASE_URL .'/?aid=' . $this->getId();
    }

    /**
     * @return string the cross reference text: bug #42
     */
    public function getXRef() {
        return $this->getTracker()->getItemName() . ' #' . $this->getId();
    }

    /**
     * Fetch the html xref link to the artifact
     *
     * @return string html
     */
    public function fetchXRefLink() {
        return '<a class="cross-reference" href="/goto?'. http_build_query(array(
            'key'      => $this->getTracker()->getItemName(),
            'val'      => $this->getId(),
            'group_id' => $this->getTracker()->getGroupId(),
        )) .'">'. $this->getXRef() .'</a>';
    }

    /**
     * Returns a Tracker_FormElementFactory instance
     *
     * @return Tracker_FormElementFactory
     */
    protected function getFormElementFactory() {
        if (empty($this->formElementFactory)) {
            $this->formElementFactory = Tracker_FormElementFactory::instance();
        }
        return $this->formElementFactory;
    }

    public function setFormElementFactory(Tracker_FormElementFactory $factory) {
        $this->formElementFactory = $factory;
    }

    /**
     * Returns a Tracker_ArtifactFactory instance
     *
     * @return Tracker_ArtifactFactory
     */
    protected function getArtifactFactory() {
        return Tracker_ArtifactFactory::instance();
    }

    /**
     * Create the initial changeset of this artifact
     *
     * @param array  $fields_data The artifact fields values
     * @param User   $submitter   The user who did the artifact submission
     * @param string $email       The email of the person who subvmitted the artifact if submission is done in anonymous mode
     *
     * @return int The Id of the initial changeset, or null if fields were not valid
     */
    public function createInitialChangeset($fields_data, $submitter, $email) {
        $changeset_id = null;
        $is_submission = true;

        if ( ! $submitter->isAnonymous() || $email != null) {
            if ($this->validateFields($fields_data, true)) {

                // Initialize a fake Changeset to ensure List & Workflow works with an "initial" thus empty state
                $this->changesets = array(new Tracker_Artifact_Changeset_Null());

                $workflow = $this->getWorkflow();
                if ($workflow) {
                    $workflow->before($fields_data, $submitter, $this);
                    $augmented_data = $this->addDatesToRequestData($fields_data);
                    if (! $workflow->validateGlobalRules($augmented_data, $this->getFormElementFactory())) {
                        return false;
                    }
                }
                if ($changeset_id = $this->getChangesetDao()->create($this->getId(), $submitter->getId(), $email)) {

                    //Store the value(s) of the fields
                    $used_fields = $this->getFormElementFactory()->getUsedFields($this->getTracker());
                    foreach ($used_fields as $field) {
                        if (isset($fields_data[$field->getId()]) && $field->userCanSubmit()) {
                            $field->saveNewChangeset($this, null, $changeset_id, $fields_data[$field->getId()], $submitter, $is_submission);
                        } else if ($workflow && isset($fields_data[$field->getId()]) && !$field->userCanSubmit() && $workflow->bypassPermissions($field)) {
                            $bypass_perms  = true;
                            $field->saveNewChangeset($this, null, $changeset_id, $fields_data[$field->getId()], $submitter, $is_submission, $bypass_perms);
                        } else if (!isset($fields_data[$field->getId()]) && !$field->userCanSubmit() && $field->isRequired()) {
                            $fields_data[$field->getId()] = $field->getDefaultValue();
                            $field->saveNewChangeset($this, null, $changeset_id, $fields_data[$field->getId()], $submitter, $is_submission);
                        }
                    }
                    //Save the artifact
                    $this->getArtifactFactory()->save($this);

                    // Clear fake changeset so subsequent call to getChangesets will load a fresh & complete one from the DB
                    $this->changesets = null;
                }
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_artifact', 'email_required'));
        }
        return $changeset_id;
    }

    /**
     * Validate the fields contained in $fields_data, and update $fields_data for invalid data
     * $fields_data is an array of [field_id] => field_data
     *
     * @param array &$fields_data The field data (IN/OUT)
     * @param boolean $is_submission true if it is a submission, false otherwise
     *
     * @return boolean true if all fields are valid, false otherwise. This function update $field_data (set values to null if not valid)
     */
    public function validateFields($fields_data, $is_submission = null) {

        $is_valid = true;
        $used_fields    = $this->getFormElementFactory()->getUsedFields($this->getTracker());
        $last_changeset = $this->getLastChangeset();
        foreach ($used_fields as $field) {
            $submitted_value = null;
            if (isset($fields_data[$field->getId()])) {
                $submitted_value = $fields_data[$field->getId()];
            }

            $last_changeset_value = null;
            if ($last_changeset) {
                // artifact already has value for this field
                $last_changeset_value = $last_changeset->getValue($field);
            }
            //we do not validate if we are in submission mode, the field is required and we can't submit the field
            if (!(!$last_changeset && $field->isRequired() && !$field->userCanSubmit())) {
                $is_valid = $field->validateField($this, $submitted_value, $last_changeset_value, $is_submission) && $is_valid;
            }
        }

        if($is_valid) {
            //validate workflow
             $workflow = $this->getWorkflow();
             if ($workflow) {
                 $is_valid = $workflow->validate($fields_data, $this);
             }
        }

        return $is_valid;
    }

    public function getErrors() {
        $list_errors = array();
        $is_valid = true;
        $used_fields    = $this->getFormElementFactory()->getUsedFields($this->getTracker());
        $last_changeset = $this->getLastChangeset();
        foreach ($used_fields as $field) {
            if ($field->hasErrors()) {
                $list_errors[] = $field->getId();
            }
        }
        return $list_errors;
    }

    /**
     * Update an artifact (means create a new changeset)
     *
     * @param array   $fields_data       Artifact fields values
     * @param string  $comment           The comment (follow-up) associated with the artifact update
     * @param User    $submitter         The user who is doing the update
     * @param string  $email             The email of the person who updates the artifact if modification is done in anonymous mode
     * @param boolean $send_notification true if a notification must be sent, false otherwise
     * @param string  $comment_format     The comment (follow-up) type ("text" | "html")
     *
     * @throws Tracker_Exception In the validation
     * @throws Tracker_NoChangeException In the validation
     * @return boolean True if update is done without error, false otherwise
     */
    public function createNewChangeset($fields_data, $comment, $submitter, $email, $send_notification = true, $comment_format = Tracker_Artifact_Changeset_Comment::TEXT_COMMENT) {
        $this->validateNewChangeset($fields_data, $comment, $submitter);
        
        /*
         * Post actions were run by validateNewChangeset but they modified a 
         * different set of $fields_data in the case of massChange or soap requests;
         * we run them again for the current $fields_data
         * 
         */
        $this->getWorkflow()->before($fields_data, $submitter, $this);
        $changeset_id = $this->getChangesetDao()->create($this->getId(), $submitter->getId(), $email);
        if(! $changeset_id) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_artifact', 'unable_update'));
            return false;
        }
        
        $comment = trim($comment);
        $last_changeset = $this->getLastChangeset();
        $comment_format = Tracker_Artifact_Changeset_Comment::checkCommentFormat($comment_format);
        $workflow = $this->getWorkflow();
       
        $is_submission = false;
        //Store the comment
        $this->getChangesetCommentDao()->createNewVersion($changeset_id, $comment, $submitter->getId(), 0, $comment_format);

        //extract references from the comment
        $this->getReferenceManager()->extractCrossRef($comment, $this->getId(), self::REFERENCE_NATURE, $this->getTracker()->getGroupID(), $submitter->getId(), $this->getTracker()->getItemName());

        //Store the value(s) of the fields
        $used_fields = $this->getFormElementFactory()->getUsedFields($this->getTracker());
        foreach ($used_fields as $field) {
            if (isset($fields_data[$field->getId()]) && $field->userCanUpdate()) {

                $field->saveNewChangeset($this, $last_changeset, $changeset_id, $fields_data[$field->getId()], $submitter, $is_submission);
            } else if ($workflow && isset($fields_data[$field->getId()]) && !$field->userCanUpdate() && $workflow->bypassPermissions($field)) {
                $bypass_perms  = true;
                $field->saveNewChangeset($this, $last_changeset, $changeset_id, $fields_data[$field->getId()], $submitter, $is_submission, $bypass_perms);
            } else {
                $field->saveNewChangeset($this, $last_changeset, $changeset_id, null, $submitter, $is_submission);
            }
        }

        //Save the artifact
        $this->getArtifactFactory()->save($this);

        if ($send_notification) {
            // Send notifications
            $this->getChangeset($changeset_id)->notify();
        }

        return true;
    }

    /**
     * 
     * @param array $fields_data
     * @param string $comment
     * @param User $submitter
     * @param string $email
     * @return boolean
     * @throws Tracker_Exception
     * @throws Tracker_NoChangeException
     */
    private function validateNewChangeset($fields_data, $comment, $submitter, $email = null) {
        if ($submitter->isAnonymous() && ($email == null || $email == '')) {
            $message = $GLOBALS['Language']->getText('plugin_tracker_artifact', 'email_required');
            throw new Tracker_Exception($message);
        }

        if (! $this->validateFields($fields_data, false)) {
            $message = $GLOBALS['Language']->getText('plugin_tracker_artifact', 'fields_not_valid');
            throw new Tracker_Exception($message);
        }

        $comment = trim($comment);
        $last_changeset = $this->getLastChangeset();

        if (! $comment && ! $last_changeset->hasChanges($fields_data)) {
            throw new Tracker_NoChangeException($this->getId(), $this->getXRef());
        }

        $workflow = $this->getWorkflow();
        $fields_data = $this->addDatesToRequestData($fields_data);
        if ($workflow) {
            /*
             * We need to run the post actions to validate the data
             */
            $workflow->before($fields_data, $submitter, $this);
            if (! $workflow->validateGlobalRules($fields_data, $this->getFormElementFactory())) {
                throw new Tracker_Exception();
            }
        }
        
        return true;
    }

            

    /**
     * @return ReferenceManager
     */
    public function getReferenceManager() {
        return ReferenceManager::instance();
    }

    /**
     * Returns the tracker Id this artifact belongs to
     *
     * @return int The tracker Id this artifact belongs to
     */
    public function getTrackerId() {
        return $this->tracker_id;
    }

    /**
     * Returns the tracker this artifact belongs to
     *
     * @return Tracker The tracker this artifact belongs to
     */
    public function getTracker() {
        if (!isset($this->tracker)) {
            $this->tracker = TrackerFactory::instance()->getTrackerByid($this->tracker_id);
        }
        return $this->tracker;
    }

    public function setTracker(Tracker $tracker) {
        $this->tracker = $tracker;
        $this->tracker_id = $tracker->getId();
    }

    /**
     * Returns the latest changeset of this artifact
     *
     * @return Tracker_Artifact_Changeset The latest changeset of this artifact, or false if no latest changeset
     */
    public function getLastChangeset() {
        $changesets = $this->getChangesets();
        return end($changesets);
    }

    /**
     * Returns the first changeset of this artifact
     *
     * @return Tracker_Artifact_Changeset The first changeset of this artifact
     */
    public function getFirstChangeset() {
        $changesets = $this->getChangesets();
        reset($changesets);
        list(,$c) = each($changesets);
        return $c;
    }

    /**
     * say if the changeset is the first one for this artifact
     *
     * @return bool
     */
    public function isFirstChangeset(Tracker_Artifact_Changeset $changeset) {
        $c = $this->getFirstChangeset();
        return $c->getId() == $changeset->getId();
    }

    /**
     * Returns all the changesets of this artifact
     *
     * @return array of Tracker_Artifact_Changeset The changesets of this artifact
     */
    public function getChangesets() {
        if (!$this->changesets) {
            $this->changesets = array();
            foreach ($this->getChangesetDao()->searchByArtifactId($this->id) as $row) {
                $this->changesets[$row['id']] = new Tracker_Artifact_Changeset($row['id'],
                                                            $this,
                                                            $row['submitted_by'],
                                                            $row['submitted_on'],
                                                            $row['email']);
            }
        }
        return $this->changesets;
    }

    /**
     * @param array $changesets array of Tracker_Artifact_Changeset
     */
    public function setChangesets(array $changesets) {
        $this->changesets = $changesets;
    }

    /**
     * Get all commentators of this artifact
     *
     * @return array of strings (username or emails)
     */
    public function getCommentators() {
        $commentators = array();
        foreach ($this->getChangesets() as $c) {
            if ($submitted_by = $c->getSubmittedBy()) {
                if ($user = $this->getUserManager()->getUserById($submitted_by)) {
                    $commentators[] = $user->getUserName();
                }
            } else if ($email = $c->getEmail()) {
                $commentators[] = $email;
            }
        }
        return $commentators;
    }

    /**
     * Return the ChangesetDao
     *
     * @return Tracker_Artifact_ChangesetDao The Dao
     */
    protected function getChangesetDao() {
        return new Tracker_Artifact_ChangesetDao();
    }

    /**
     * Return the ChangesetCommentDao
     *
     * @return Tracker_Artifact_Changeset_CommentDao The Dao
     */
    protected function getChangesetCommentDao() {
        return new Tracker_Artifact_Changeset_CommentDao();
    }

    /**
     * Returns the changeset of this artifact with Id $changeset_id, or null if not found
     *
     * @param int $changeset_id The Id of the changeset to retrieve
     *
     * @return Tracker_Artifact_Changeset The changeset, or null if not found
     */
    public function getChangeset($changeset_id) {
        $c = null;
        if ($this->changesets && isset($this->changesets[$changeset_id])) {
            $c = $this->changesets[$changeset_id];
        } else {
            if ($row = $this->getChangesetDao()->searchByArtifactIdAndChangesetId($this->id, $changeset_id)->getRow()) {
                $c = new Tracker_Artifact_Changeset($row['id'],
                                           $this,
                                           $row['submitted_by'],
                                           $row['submitted_on'],
                                           $row['email']);
                $this->changesets[$changeset_id] = $c;
            }
        }
        return $c;
    }

    /**
     * Returns the previous changeset just before the changeset $changeset_id, or null if $changeset_id is the first one
     *
     * @param int $changeset_id The changeset reference
     *
     * @return Tracker_Artifact_Changeset The previous changeset, or null if not found
     */
    public function getPreviousChangeset($changeset_id) {
        $previous = null;
        $changesets = $this->getChangesets();
        reset($changesets);
        while ((list(,$changeset) = each($changesets)) && $changeset->id != $changeset_id) {
            $previous = $changeset;
        }
        return $previous;
    }

    public function exportCommentsToSOAP() {
        $soap_comments = array();
        foreach ($this->getChangesets() as $changeset) {
            $changeset_comment = $changeset->exportCommentToSOAP();
            if ($changeset_comment) {
                $soap_comments[] = $changeset_comment;
            }
        }
        return $soap_comments;
    }

    /**
     * Get the Id of this artifact
     *
     * @return int The Id of this artifact
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set the Id of this artifact
     *
     * @param int $id the new id of the artifact
     *
     * @return Tracker_Artifact
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the value for this field in the changeset
     *
     * @param Tracker_FormElement_Field  $field     The field
     * @param Tracker_Artifact_Changeset $changeset The changeset. if null given take the last changeset of the artifact
     *
     * @return Tracker_Artifact_ChangesetValue
     */
    function getValue(Tracker_FormElement_Field $field, Tracker_Artifact_Changeset $changeset = null) {
        if (!$changeset) {
            $changeset = $this->getLastChangeset();
        }
        return $changeset->getValue($field);
    }

    /**
     * Returns the date (timestamp) the artifact ha been created
     *
     * @return int the timestamp for the date this aetifact was created
     */
    function getSubmittedOn() {
        return $this->submitted_on;
    }

    /**
     * Returns the user who submitted the artifact
     *
     * @return int the user id
     */
    function getSubmittedBy() {
        return $this->submitted_by;
    }

    /**
     * Return Workflow the artifact should respect
     *
     * @return Workflow
     */
    public function getWorkflow() {
        $workflow = $this->getTracker()->getWorkflow();
        $workflow->setArtifact($this);
        return $workflow;
    }

    /**
     * Get the UserManager instance
     *
     * @return UserManager
     */
    public function getUserManager() {
        return UserManager::instance();
    }

    /**
     * User want to link an artifact to the current one
     *
     * @param int  $linked_artifact_id The id of the artifact to link
     * @param User $current_user       The user who made the link
     *
     * @return bool true if success false otherwise
     */
    public function linkArtifact($linked_artifact_id, User $current_user) {
        $artlink_fields = $this->getFormElementFactory()->getUsedArtifactLinkFields($this->getTracker());  
        if (count($artlink_fields)) {
            $comment       = '';
            $email         = '';
            $artlink_field = $artlink_fields[0];
            $fields_data   = array();
            $fields_data[$artlink_field->getId()]['new_values'] = $linked_artifact_id;

            try {
                $this->createNewChangeset($fields_data, $comment, $current_user, $email);
                return true;
            } catch (Tracker_NoChangeException $e) {
                $GLOBALS['Response']->addFeedback('info', $e->getMessage(), CODENDI_PURIFIER_LIGHT);
                return false;
            } catch (Tracker_Exception $e) {
                $GLOBALS['Response']->addFeedback('error', $e->getMessage());
                return false;
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker', 'must_have_artifact_link_field'));
        }
    }

    /**
     * Get artifacts linked to the current artifact
     *
     * @param User $user The user who should see the artifacts
     *
     * @return Array of Tracker_Artifact
     */
    public function getLinkedArtifacts(User $user) {
        $artifact_links      = array();
        $artifact_link_field = $this->getAnArtifactLinkField($user);
        if ($artifact_link_field) {
            $artifact_links = $artifact_link_field->getLinkedArtifacts($this->getLastChangeset(), $user);
        }
        return $artifact_links;
    }

    /**
     * Get artifacts linked to the current artifact and sub artifacts
     *
     * @param User $user The user who should see the artifacts
     *
     * @return Array of Tracker_Artifact
     */
    public function getLinkedArtifactsOfHierarchy(User $user) {
        $artifact_links = $this->getLinkedArtifacts($user);
        $allowed_trackers = $this->getAllowedChildrenTypes();
        foreach ($artifact_links as $artifact_link) {
            $tracker = $artifact_link->getTracker();
            if (in_array($tracker, $allowed_trackers)) {
                $sub_linked_artifacts = $artifact_link->getLinkedArtifactsOfHierarchy($user);
                $artifact_links       = array_merge($artifact_links, $sub_linked_artifacts);
            }
        }
        return $artifact_links;
    }

    /**
     * Get artifacts linked to the current artifact if they belongs to the hierarchy
     *
     * @param User $user The user who should see the artifacts
     *
     * @return Array of Tracker_Artifact
     */
    public function getHierarchyLinkedArtifacts(User $user) {
        $allowed_trackers = $this->getAllowedChildrenTypes();
        $artifact_links   = $this->getLinkedArtifacts($user);
        foreach ($artifact_links as $key => $artifact) {
            if ( ! in_array($artifact->getTracker(), $allowed_trackers)) {
                unset($artifact_links[$key]);
            }
        }
        return $artifact_links;
    }

    /**
     * @return array of Tracker
     */
    public function getAllowedChildrenTypes() {
        return $this->getHierarchyFactory()->getChildren($this->getTrackerId());
    }

    /**
     * Get artifacts linked to the current artifact if
     * they are not in children.
     *
     * @param User $user The user who should see the artifacts
     *
     * @return Array of Tracker_Artifact
     */
    public function getUniqueLinkedArtifacts(User $user) {
        $sub_artifacts = $this->getLinkedArtifacts($user);
        $grandchild_artifacts = array();
        foreach ($sub_artifacts as $artifact) {
            $grandchild_artifacts = array_merge($grandchild_artifacts, $artifact->getLinkedArtifactsOfHierarchy($user));
        }
        array_filter($grandchild_artifacts);
        return array_diff($sub_artifacts, $grandchild_artifacts);
    }

    public function __toString() {
        return __CLASS__." #$this->id";
    }

    /**
     * Returns all ancestors of current artifact (from direct parent to oldest ancestor)
     *
     * @param User $user
     *
     * @return Array of Tracker_Artifact
     */
    public function getAllAncestors(User $user) {
        if (!isset($this->ancestors)) {
            $this->ancestors = $this->getHierarchyFactory()->getAllAncestors($user, $this);
        }
        return $this->ancestors;
    }

    public function setAllAncestors(array $ancestors) {
        $this->ancestors = $ancestors;
    }

    /**
     * Return the parent artifact of current artifact if any
     *
     * @param User $user
     *
     * @return Tracker_Artifact
     */
    public function getParent(User $user) {
        return array_shift($this->getAllAncestors($user));
    }

    /**
     * Get artifacts
     *
     * @param User $user
     *
     * @return Array of Tracker_Artifact
     */
    public function getSiblings(User $user) {
        return $this->getHierarchyFactory()->getSiblings($user, $this);
    }

    /**
     * Returns the previously injected factory (e.g. in tests), or a new
     * instance (e.g. in production).
     *
     * @return Tracker_HierarchyFactory
     */
    public function getHierarchyFactory() {
        if ($this->hierarchy_factory == null) {
            $this->hierarchy_factory = Tracker_HierarchyFactory::instance();
        }
        return $this->hierarchy_factory;
    }


    public function setHierarchyFactory($hierarchy = null) {
        $this->hierarchy_factory = $hierarchy;
    }

    /**
     * Returns the ids of the children of the tracker.
     *
     * @return array of int
     */
    protected function getChildTrackersIds() {
        $children_trackers_ids = array();
        $children_hierarchy_tracker = $this->getHierarchyFactory()->getChildren($this->getTrackerId());
        foreach ($children_hierarchy_tracker as $tracker) {
            $children_trackers_ids[] = $tracker->getId();
        }
        return $children_trackers_ids;
    }

    /**
     * Return the first (and only one) ArtifactLink field (if any)
     *
     * @return Tracker_FormElement_Field_ArtifactLink
     */
    public function getAnArtifactLinkField(User $user) {
        return $this->getFormElementFactory()->getAnArtifactLinkField($user, $this->getTracker());
    }

    /**
     * Return the first BurndownField (if any)
     *
     * @return Tracker_FormElement_Field_Burndown
     */
    public function getABurndownField(User $user) {
        return $this->getFormElementFactory()->getABurndownField($user, $this->getTracker());
    }

    private function unlinkArtifact($artlink_fields, $linked_artifact_id, User $current_user) {
        $comment       = '';
        $email         = '';
        $artlink_field = $artlink_fields[0];
        $fields_data   = array();
        $fields_data[$artlink_field->getId()]['new_values'] = '';
        $fields_data[$artlink_field->getId()]['removed_values'] = array($linked_artifact_id => 1);
        
        try {
            $this->createNewChangeset($fields_data, $comment, $current_user, $email);
        } catch (Tracker_NoChangeException $e) {
            $GLOBALS['Response']->addFeedback('info', $e->getMessage(), CODENDI_PURIFIER_LIGHT);
        } catch (Tracker_Exception $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
        }
    }

    protected function getRedirectUrlAfterArtifactUpdate(Codendi_Request $request) {
        $stay     = $request->get('submit_and_stay') ;
        $from_aid = $request->get('from_aid');

        $redirect = new Tracker_Artifact_Redirect();
        $redirect->mode             = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $redirect->base_url         = TRACKER_BASE_URL;
        $redirect->query_parameters = $this->calculateRedirectParams($stay, $from_aid);
        if ($stay) {
            $redirect->mode = Tracker_Artifact_Redirect::STATE_STAY_OR_CONTINUE;
        }
        return $redirect;
    }

    private function calculateRedirectParams($stay, $from_aid) {
        $redirect_params = array();
        if ($stay) {
            $redirect_params['aid']       = $this->getId();
            $redirect_params['from_aid']  = $from_aid;
        } else if ($from_aid) {
            $redirect_params['aid']       = $from_aid;
        } else {
            $redirect_params['tracker']   = $this->tracker_id;
        }
        return array_filter($redirect_params);
    }

    /**
     * Invoke those we don't speak of which may want to redirect to a
     * specific page after an update/creation of this artifact.
     * If the summoning is not strong enough (or there is no listener) then
     * nothing is done. Else the client is redirected and
     * the script will die in agony!
     *
     * @param Codendi_Request $request The request
     */
    public function summonArtifactRedirectors(Codendi_Request $request, Tracker_Artifact_Redirect $redirect) {
        EventManager::instance()->processEvent(
            TRACKER_EVENT_REDIRECT_AFTER_ARTIFACT_CREATION_OR_UPDATE,
            array(
                'request'  => $request,
                'artifact' => $this,
                'redirect' => $redirect
            )
        );
    }

    private function summonArtifactAssociators(Codendi_Request $request, User $current_user, $linked_artifact_id) {
        EventManager::instance()->processEvent(
            TRACKER_EVENT_ARTIFACT_ASSOCIATION_EDITED,
            array(
                'artifact'             => $this,
                'linked-artifact-id'   => $linked_artifact_id,
                'request'              => $request,
                'user'                 => $current_user,
                'form_element_factory' => $this->getFormElementFactory(),
            )
        );
    }

    public function delete(User $user) {
        $this->getDao()->startTransaction();
        foreach($this->getChangesets() as $changeset) {
            $changeset->delete($user);
        }
        $this->getPermissionsManager()->clearPermission(self::PERMISSION_ACCESS, $this->getId());
        $this->getCrossReferenceManager()->deleteEntity($this->getId(), self::REFERENCE_NATURE, $this->getTracker()->getGroupId());
        $this->getDao()->deleteArtifactLinkReference($this->getId());
        $this->getDao()->deletePriority($this->getId());
        $this->getDao()->delete($this->getId());
        $this->getDao()->commit();
    }

    protected function getDao() {
        return new Tracker_ArtifactDao();
    }

    protected function getPermissionsManager() {
        return PermissionsManager::instance();
    }

    protected function getCrossReferenceManager() {
        return new CrossReferenceManager();
    }

    protected function getCrossReferenceFactory() {
        return new CrossReferenceFactory($this->getId(), self::REFERENCE_NATURE, $this->getTracker()->getGroupId());
    }

    public function getCrossReferencesSOAPValues() {
         $soap_value = array();
         $cross_reference_factory = $this->getCrossReferenceFactory();
         $cross_reference_factory->fetchDatas();

         $cross_references = $cross_reference_factory->getCrossReferencesByDirection();
         foreach ($cross_references as $array_of_references_by_direction) {
             foreach ($array_of_references_by_direction as $reference) {
                $soap_value[] = array(
                    'ref' => $reference['ref'],
                    'url' => $reference['url'],
                );
             }
         }

         error_log("saop value : ". var_export($soap_value,1));
         return $soap_value;
    }

    /**
     * Used when validating the rules of a new/ initial changset creating.
     * 
     * @param array $fields_data
     * @return array
     */
    private function addDatesToRequestData(array $fields_data) {
        $tracker_data = array();

        //only when a previous changeset exists
        if(! $this->getLastChangeset() instanceof Tracker_Artifact_Changeset_Null) {
            foreach ($this->getLastChangeset()->getValues() as $key => $field) {
                if($field instanceof Tracker_Artifact_ChangesetValue_Date){
                    $tracker_data[$key] = $field->getValue();
                }
            }
        }
        
        //replace where appropriate with submitted values
        foreach ($fields_data as $key => $value) {
            $tracker_data[$key] = $value;
        }

        $elements = $this->getFormElementFactory()->getAllFormElementsForTracker($this->getTracker());
        
        //addlastUpdateDate and submitted on if available 
        foreach ($elements as $elm ) {      
            if($elm instanceof Tracker_FormElement_Field_LastUpdateDate ) {
                 $tracker_data[$elm->getId()] = date("Y-m-d");
            }
            if($elm instanceof Tracker_FormElement_Field_SubmittedOn ) {
                 $tracker_data[$elm->getId()] = $this->getSubmittedOn();
            } 
            
            if($elm instanceof Tracker_FormElement_Field_Date &&
                    ! array_key_exists($elm->getId(), $tracker_data)) {
                //user doesn't have access to field
                $tracker_data[$elm->getId()] = $elm->getValue($elm->getId());
            }
        }

        return $tracker_data;
    }
}

?>
