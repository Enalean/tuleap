<?php
/**
 * Copyright Enalean (c) 2011-Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once __DIR__ . '/../../constants.php';

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfig;
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfigDAO;
use Tuleap\Tracker\Admin\ArtifactsDeletion\UserDeletionRetriever;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalArtifactActionButtonsFetcher;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalArtifactActionButtonsPresenterBuilder;
use Tuleap\Tracker\Artifact\ActionButtons\ArtifactActionButtonPresenterBuilder;
use Tuleap\Tracker\Artifact\ActionButtons\ArtifactCopyButtonPresenterBuilder;
use Tuleap\Tracker\Artifact\ActionButtons\ArtifactIncomingEmailButtonPresenterBuilder;
use Tuleap\Tracker\Artifact\ActionButtons\ArtifactMoveButtonPresenterBuilder;
use Tuleap\Tracker\Artifact\ActionButtons\ArtifactNotificationActionButtonPresenterBuilder;
use Tuleap\Tracker\Artifact\ArtifactDescriptionProvider;
use Tuleap\Tracker\Artifact\ArtifactInstrumentation;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactDeletionLimitRetriever;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionDAO;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetFieldsWithoutRequiredValidationValidator;
use Tuleap\Tracker\Artifact\PermissionsCache;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationDetector;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SubmittedValueConvertor;
use Tuleap\Tracker\FormElement\Field\Burndown\BurndownCacheGenerationChecker;
use Tuleap\Tracker\FormElement\Field\Burndown\BurndownCacheGenerator;
use Tuleap\Tracker\FormElement\Field\Burndown\BurndownRemainingEffortAdderForREST;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;
use Tuleap\Tracker\Semantic\Status\StatusValueForChangesetProvider;
use Tuleap\Tracker\Semantic\Status\StatusValueProvider;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;

class Tracker_Artifact implements Recent_Element_Interface, Tracker_Dispatchable_Interface
{
    public const REST_ROUTE        = 'artifacts';
    public const NO_PARENT         = -1;
    public const PERMISSION_ACCESS = 'PLUGIN_TRACKER_ARTIFACT_ACCESS';
    public const REFERENCE_NATURE  = 'plugin_tracker_artifact';
    public const STATUS_OPEN       = 'open';
    public const STATUS_CLOSED     = 'closed';

    /**
     * Display the form to copy an artifact
     *
     * Parameters:
     *  — artifact     => (in) Tracker_Artifact
     *  — current_user => (in) PFUser
     */
    public const DISPLAY_COPY_OF_ARTIFACT = 'display_copy_of_artifact';

    public $id;
    public $tracker_id;
    public $use_artifact_permissions;
    protected $per_tracker_id;
    protected $submitted_by;
    protected $submitted_on;

    protected $changesets;
    /**
     * @var Tracker_Artifact_Changeset|null
     */
    private $last_changeset;

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
     * @var String
     */
    private $title;

    /**
     * @var String
     */
    private $status;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_Artifact[] */
    private $siblings_without_permission_checking;

    /** @var Tracker_Artifact */
    private $parent_without_permission_checking;

    /** @var PFUser*/
    private $submitted_by_user;

    /** @var array */
    private $authorized_ugroups;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    /**
     * Constructor
     *
     * @param int     $id                       The Id of the artifact
     * @param int     $tracker_id               The tracker Id the artifact belongs to
     * @param int     $submitted_by             The id of the user who's submitted the artifact
     * @param int     $submitted_on             The timestamp of artifact submission
     *
     * @param bool $use_artifact_permissions True if this artifact uses permission, false otherwise
     */
    public function __construct($id, $tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions)
    {
        $this->id                       = $id;
        $this->tracker_id               = $tracker_id;
        $this->submitted_by             = $submitted_by;
        $this->submitted_on             = $submitted_on;
        $this->use_artifact_permissions = $use_artifact_permissions;
        $this->per_tracker_id           = null;
    }

    /**
     * Obtain event manager instance
     *
     * @return EventManager
     */
    private function getEventManager()
    {
        return EventManager::instance();
    }

    /**
     * Return true if given given artifact refer to the same DB object (basically same id).
     *
     *
     * @return bool
     */
    public function equals(?Tracker_Artifact $artifact = null)
    {
        return $artifact && $this->id == $artifact->getId();
    }

    /**
    * Set the value of use_artifact_permissions
    *
    * @param bool $use_artifact_permissions
    *
    * @return bool true if the artifact has individual permissions set
    */
    public function setUseArtifactPermissions($use_artifact_permissions)
    {
        $this->use_artifact_permissions = $use_artifact_permissions;
    }

    /**
     * useArtifactPermissions
     * @return bool true if the artifact has individual permissions set
     */
    public function useArtifactPermissions()
    {
        return $this->use_artifact_permissions;
    }

    /**
     * userCanView - determine if the user can view this artifact.
     *
     * @param PFUser $user if not specified, use the current user
     *
     * @return bool user can view the artifact
     */
    public function userCanView(?PFUser $user = null)
    {
        $user_manager       = $this->getUserManager();
        $permission_checker = new Tracker_Permission_PermissionChecker(
            $user_manager,
            new ProjectAccessChecker(
                PermissionsOverrider_PermissionsOverriderManager::instance(),
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            )
        );

        if ($user === null) {
            $user = $user_manager->getCurrentUser();
        }

        return PermissionsCache::userCanView($this, $user, $permission_checker);
    }

    public function userCanUpdate(PFUser $user): bool
    {
        if ($user->isAnonymous() || !$this->userCanView($user)) {
            return false;
        }
        return true;
    }

    /**
     * @deprecated
     */
    public function permission_db_authorized_ugroups($permission_type)
    {
        include_once __DIR__ . '/../../../../../src/www/project/admin/permissions.php';
        $result = array();
        $res    = permission_db_authorized_ugroups($permission_type, $this->getId());
        if (db_numrows($res) > 0) {
            while ($row = db_fetch_array($res)) {
                $result[] = $row;
            }
            return $result;
        } else {
            return false;
        }
    }

    public function getAuthorizedUGroups()
    {
        if (! isset($this->authorized_ugroups)) {
            $this->authorized_ugroups = array();
            if ($this->useArtifactPermissions()) {
                $this->authorized_ugroups = PermissionsManager::instance()->getAuthorizedUgroupIds(
                    $this->id,
                    self::PERMISSION_ACCESS
                );
            }
        }

        return $this->authorized_ugroups;
    }

    public function setAuthorizedUGroups(array $ugroups)
    {
        $this->authorized_ugroups = $ugroups;
    }

    /**
     * This method returns the artifact mail rendering
     *
     * @param PFUser  $recipient
     * @param string $format, the mail format text or html
     * @param bool   $ignore_perms, indicates if we ignore various permissions
     *
     * @return string
     */
    public function fetchMail($recipient, $format, $ignore_perms = false)
    {
        $output = '';
        switch ($format) {
            case 'html':
                $content = $this->fetchMailFormElements($recipient, $format, $ignore_perms);
                if ($content) {
                    $output .=
                    '<table style="width:100%">
                        <tr>
                            <td colspan="3" align="left">
                                <h2>' .
                                    $GLOBALS['Language']->getText('plugin_tracker_artifact_changeset', 'header_html_snapshot') . '
                                </h2>
                            </td>
                        </tr>
                    </table>';
                    $output .= $content;
                }
                break;
            default:
                $output .= PHP_EOL;
                $output .= $this->fetchMailFormElements($recipient, $format, $ignore_perms);
                break;
        }
        return $output;
    }

    /**
     * Returns the artifact field for mail rendering
     *
     * @param PFUser  $recipient
     * @param string $format, the mail format text or html
     * @param bool   $ignore_perms, indicates if we ignore various permissions
     *
     * @return String
     */
    public function fetchMailFormElements($recipient, $format, $ignore_perms = false)
    {
        $output = '';
        $toplevel_form_elements = $this->getTracker()->getFormElements();
        $this->prepareElementsForDisplay($toplevel_form_elements);

        foreach ($toplevel_form_elements as $formElement) {
            $output .= $formElement->fetchMailArtifact($recipient, $this, $format, $ignore_perms);
            if ($format == 'text' && $output) {
                $output .= PHP_EOL;
            }
        }

        if ($format == 'html') {
            $output = '<table width="100%">' . $output . '</table>';
        }

        return $output;
    }

    /** @param Tracker_FormElement[] $toplevel_form_elements */
    private function prepareElementsForDisplay($toplevel_form_elements)
    {
        foreach ($toplevel_form_elements as $formElement) {
            $formElement->prepareForDisplay();
        }
    }

    /**
     * Fetch the tooltip displayed on an artifact reference
     *
     * @param PFUser $user The user who fetch the tooltip
     *
     * @return string html
     */
    public function fetchTooltip($user)
    {
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
     *
     * @return string html
     */
    public function fetchWidget($item_name)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $html .= ' <a class="direct-link-to-artifact tracker-widget-artifacts" href="' . TRACKER_BASE_URL . '/?aid=' . $this->id . '">';
        $html .= $hp->purify($item_name, CODENDI_PURIFIER_CONVERT_HTML);
        $html .= ' #';
        $html .= $this->id;
        $title = $this->getTitle();
        if ($title) {
            $html .= ' - ';
            $html .= $hp->purify($title, CODENDI_PURIFIER_CONVERT_HTML);
        }

        $html .= '</a>';
        return $html;
    }

    public function fetchTitleWithoutUnsubscribeButton($prefix)
    {
        return $this->fetchTitleContent($prefix, false);
    }

     /**
     * Returns HTML code to display the artifact title
     *
     * @param string $prefix The prefix to display before the title of the artifact. Default is blank.
     *
     * @return string The HTML code for artifact title
     */
    public function fetchTitle($prefix = '')
    {
        return $this->fetchTitleContent($prefix, true);
    }

    private function fetchTitleContent($prefix, $unsubscribe_button)
    {
        $html = '';
        $html .= $this->fetchHiddenTrackerId();
        $html .= '<div class="tracker_artifact_title">';
        $html .= $prefix;
        $html .= $this->getXRefAndTitle();
        if ($unsubscribe_button) {
            $html .= $this->fetchActionButtons();
        }

        $html .= '</div>';
        return $html;
    }

    public function fetchActionButtons()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(
            TRACKER_TEMPLATE_DIR
        );

        $event_manager = $this->getEventManager();

        $builder = new ArtifactActionButtonPresenterBuilder(
            new ArtifactNotificationActionButtonPresenterBuilder(
                $this->getUnsubscribersNotificationDao(),
                $this->getDao()
            ),
            new ArtifactIncomingEmailButtonPresenterBuilder(
                Tracker_Artifact_Changeset_IncomingMailGoldenRetriever::instance()
            ),
            new ArtifactCopyButtonPresenterBuilder(),
            new ArtifactMoveButtonPresenterBuilder(
                new ArtifactDeletionLimitRetriever(
                    new ArtifactsDeletionConfig(new ArtifactsDeletionConfigDAO()),
                    new UserDeletionRetriever(new ArtifactsDeletionDAO())
                ),
                $event_manager
            ),
            new AdditionalArtifactActionButtonsPresenterBuilder()
        );

        $user = $this->getCurrentUser();

        $action_buttons_fetcher = new AdditionalArtifactActionButtonsFetcher($this, $user);
        $event_manager->processEvent($action_buttons_fetcher);

        $action_buttons_presenters = $builder->build($this->getCurrentUser(), $this, $action_buttons_fetcher);

        $include_assets = new \Tuleap\Layout\IncludeAssets(
            __DIR__ . '/../../../../../src/www/assets/trackers',
            '/assets/trackers'
        );

        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('MoveArtifactModal.js'));
        foreach ($action_buttons_fetcher->getAdditionalActions() as $additional_action) {
            $GLOBALS['HTML']->includeFooterJavascriptFile($additional_action->getAssetLink());
        }

        return $renderer->renderToString(
            'action-buttons/action-buttons',
            $action_buttons_presenters
        );
    }

    private function doesUserHaveUnsubscribedFromArtifactNotification(PFUser $user)
    {
        return $this->getDao()->doesUserHaveUnsubscribedFromArtifactNotifications($this->id, $user->getId());
    }

    /**
     * @return bool
     */
    private function doesUserHaveUnsubscribedFromTrackerNotification(PFUser $user)
    {
        return $this->getUnsubscribersNotificationDao()->doesUserIDHaveUnsubscribedFromTrackerNotifications(
            $user->getId(),
            $this->getTrackerId()
        );
    }

    public function fetchHiddenTrackerId()
    {
        return '<input type="hidden" id="tracker_id" name="tracker_id" value="' . $this->getTrackerId() . '"/>';
    }

    public function getXRefAndTitle()
    {
        $hp = Codendi_HTMLPurifier::instance();
        return '<span class="' . $hp->purify($this->getTracker()->getColor()->getName()) . ' xref-in-title">' .
                $this->getXRef() . "\n" .
                '</span>' .
                $hp->purify($this->getTitle());
    }

    public function fetchColoredXRef()
    {
        $purifier = Codendi_HTMLPurifier::instance();
        return '<span class="colored-xref ' . $purifier->purify($this->getTracker()->getColor()->getName()) . '"><a class="cross-reference" href="' . $this->getUri() . '">' . $this->getXRef() . '</a></span>';
    }

    /**
     * Get the artifact title, or null if no title defined in semantics
     *
     * @return string|null the title of the artifact, or null if no title defined in semantics
     */
    public function getTitle()
    {
        if (! isset($this->title)) {
            $this->title = null;
            if ($title_field = Tracker_Semantic_Title::load($this->getTracker())->getField()) {
                if ($title_field->userCanRead()) {
                    if ($last_changeset = $this->getLastChangeset()) {
                        if ($title_field_value = $last_changeset->getValue($title_field)) {
                            $this->title = $title_field_value->getContentAsText();
                        }
                    }
                }
            }
        }
        return $this->title;
    }

    /**
     * @return string the description of the artifact
     */
    public function getDescription(): string
    {
        $provider = new ArtifactDescriptionProvider(Tracker_Semantic_Description::load($this->getTracker()));
        return $provider->getDescription($this);
    }

    public function getCachedTitle()
    {
        return $this->title;
    }

    /**
     * @return PFUser[]
     */
    public function getAssignedTo(PFUser $user)
    {
        $assigned_to_field = Tracker_Semantic_Contributor::load($this->getTracker())->getField();
        if ($assigned_to_field && $assigned_to_field->userCanRead($user) && $this->getLastChangeset()) {
            $field_value = $this->getLastChangeset()->getValue($assigned_to_field);
            if ($field_value) {
                $user_manager   = $this->getUserManager();
                $user_ids       = $field_value->getValue();
                $assigned_users = array();
                foreach ($user_ids as $user_id) {
                    if ($user_id != 100) {
                        $assigned_user    = $user_manager->getUserById($user_id);
                        $assigned_users[] = $assigned_user;
                    }
                }
                return $assigned_users;
            }
        }
        return array();
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get the artifact status, or null if no status defined in semantics
     *
     * @return string the status of the artifact, or null if no status defined in semantics
     */
    public function getStatus()
    {
        if (! isset($this->status)) {
            $this->status = '';
            $provider = new StatusValueProvider(new StatusValueForChangesetProvider());
            $status_value = $provider->getStatusValue($this, UserManager::instance()->getCurrentUser());
            if ($status_value) {
                $this->status = $status_value->getLabel();
            }
        }

        return $this->status;
    }

    /**
     * Get the artifact status, or null if no status defined in semantics
     *
     * @return string the status of the artifact, or null if no status defined in semantics
     */
    public function getStatusForChangeset(Tracker_Artifact_Changeset $changeset)
    {
        $provider = new StatusValueForChangesetProvider();
        $status_value = $provider->getStatusValueForChangeset($changeset, UserManager::instance()->getCurrentUser());
        if (! $status_value) {
            return null;
        }

        return $status_value->getLabel();
    }


    /**
     * @param String $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getSemanticStatusValue()
    {
        return $this->isOpen() ? self::STATUS_OPEN : self::STATUS_CLOSED;
    }

    public function isOpen()
    {
        return Tracker_Semantic_Status::load($this->getTracker())->isOpen($this);
    }

    public function isOpenAtGivenChangeset(Tracker_Artifact_Changeset $changeset)
    {
        return Tracker_Semantic_Status::load($this->getTracker())->isOpenAtGivenChangeset($changeset);
    }

    /**
     *
     * @param PFUser $recipient
     * @param bool $ignore_perms
     * @return string
     */
    public function fetchMailTitle($recipient, $format = 'text', $ignore_perms = false)
    {
        $output = '';
        if ($title_field = Tracker_Semantic_Title::load($this->getTracker())->getField()) {
            if ($ignore_perms || $title_field->userCanRead($recipient)) {
                if ($value = $this->getLastChangeset()->getValue($title_field)) {
                    if ($title = $value->getText()) {
                        $output .= $title;
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Returns HTML code to display the artifact history
     *
     * @param Codendi_Request $request The data from the user
     *
     * @return String The valid followup comment format
     */
    public function validateCommentFormat($request, $comment_format_field_name)
    {
        $comment_format = $request->get($comment_format_field_name);
        return Tracker_Artifact_Changeset_Comment::checkCommentFormat($comment_format);
    }

    /**
     * Process the artifact functions
     *
     * @param Tracker_IDisplayTrackerLayout  $layout          Displays the page header and footer
     * @param Codendi_Request                $request         The data from the user
     * @param PFUser                           $current_user    The current user
     *
     * @return void
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        switch ($request->get('func')) {
            case 'get-children':
                if ($this->getTracker()->isProjectAllowedToUseNature()) {
                    $children = $this->getChildNaturePresenterCollection($request->get('aid'));
                } else {
                    $children = $this->getChildPresenterCollection($current_user);
                }
                $GLOBALS['Response']->sendJSON($children);
                exit;
                break;
            case 'update-comment':
                if ((int) $request->get('changeset_id') && $request->exist('content')) {
                    if ($changeset = $this->getChangeset($request->get('changeset_id'))) {
                        $comment_format = $this->validateCommentFormat($request, 'comment_format');
                        $changeset->updateComment($request->get('content'), $current_user, $comment_format, $_SERVER['REQUEST_TIME']);
                        if ($request->isAjax()) {
                            //We assume that we can only change a comment from a followUp
                            echo $changeset->getComment()->fetchFollowUp();
                        }
                    }
                }
                break;
            case 'preview-attachment': // deprecated urls: /plugins/tracker/?aid=193&field=94&func=preview-attachment&attachment=39
            case 'show-attachment':    //                  /plugins/tracker/?aid=193&field=94&func=show-attachment&attachment=39
                if ((int) $request->get('field') && (int) $request->get('attachment')) {
                    $ff = Tracker_FormElementFactory::instance();
                    /**
                     * @var $field Tracker_FormElement_Field_File
                     */
                    $field = $ff->getFormElementById($request->get('field'));
                    if ($field === null || ! $field->userCanRead($current_user)) {
                        $GLOBALS['Response']->addFeedback(
                            Feedback::ERROR,
                            $GLOBALS['Language']->getText('plugin_tracker_formelement_exception', 'permission_denied')
                        );
                        $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . urlencode((string) $this->getTrackerId()));
                    }
                    if ($request->get('func') === 'show-attachment') {
                        $field->showAttachment($request->get('attachment'));
                    } elseif ($request->get('func') === 'preview-attachment') {
                        $field->previewAttachment($request->get('attachment'));
                    }
                }
                break;
            case 'artifact-delete-changeset':
                $GLOBALS['Response']->redirect('?aid=' . $this->id);
                break;
            case 'artifact-update':
                $action = new Tracker_Action_UpdateArtifact(
                    $this,
                    $this->getFormElementFactory(),
                    $this->getEventManager(),
                    $this->getNatureIsChildLinkRetriever(),
                    $this->getVisitRecorder(),
                    $this->getHiddenFieldsetsDetector()
                );
                $action->process($layout, $request, $current_user);
                break;
            case 'burndown-cache-generate':
                $ff = Tracker_FormElementFactory::instance();
                if ($field = $ff->getFormElementByid($request->get('field'))) {
                    if ($this->getBurndownCacheChecker()->isCacheBurndownAlreadyAsked($this) === false) {
                        $this->getBurndownCacheGenerator()->forceBurndownCacheGeneration($this->getId());
                    }
                }
                $GLOBALS['Response']->redirect('?aid=' . $this->id);
                break;
            case 'check-user-can-link-and-unlink':
                $source_artifact      = (int) $request->get('from-artifact');
                $destination_artifact = (int) $request->get('to-artifact');

                if (! ($this->userHasRankingPermissions($source_artifact) && $this->userHasRankingPermissions($destination_artifact))) {
                    $this->sendUserDoesNotHavePermissionsErrorCode();
                }
                break;
            case 'unassociate-artifact-to':
                $artlink_fields     = $this->getFormElementFactory()->getUsedArtifactLinkFields($this->getTracker());
                $linked_artifact_id = $request->get('linked-artifact-id');

                if (! $this->userHasRankingPermissions($this->getId())) {
                    $this->sendUserDoesNotHavePermissionsErrorCode();
                    break;
                }

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

                if (! $this->userHasRankingPermissions($this->getId())) {
                    $this->sendUserDoesNotHavePermissionsErrorCode();
                    break;
                }

                if (!$this->linkArtifact($linked_artifact_id, $current_user)) {
                    $GLOBALS['Response']->sendStatusCode(400);
                } else {
                    $this->summonArtifactAssociators($request, $current_user, $linked_artifact_id);
                }
                break;
            case 'show-in-overlay':
                $renderer = new Tracker_Artifact_EditOverlayRenderer($this, $this->getEventManager(), $this->getVisitRecorder());
                $renderer->display($request, $current_user);
                break;
            case 'get-new-changesets':
                $changeset_id = $request->getValidated('changeset_id', 'uint', 0);
                $changeset_factory = $this->getChangesetFactory();
                $GLOBALS['Response']->sendJSON($changeset_factory->getNewChangesetsFormattedForJson($this, $changeset_id));
                break;
            case 'edit':
                $GLOBALS['Response']->redirect($this->getUri());
                break;
            case 'get-edit-in-place':
                $renderer = new Tracker_Artifact_Renderer_EditInPlaceRenderer($this, $this->getMustacheRenderer(), $this->getHiddenFieldsetsDetector());
                $renderer->display($current_user, $request);
                break;
            case 'update-in-place':
                $renderer = new Tracker_Artifact_Renderer_EditInPlaceRenderer($this, $this->getMustacheRenderer(), $this->getHiddenFieldsetsDetector());
                $renderer->updateArtifact($request, $current_user);
                break;
            case 'copy-artifact':
                $art_link = $this->fetchDirectLinkToArtifact();
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_artifact', 'copy_mode_info', array($art_link)), CODENDI_PURIFIER_LIGHT);
                EventManager::instance()->processEvent(
                    self::DISPLAY_COPY_OF_ARTIFACT,
                    array(
                        'artifact'     => $this,
                        'current_user' => $current_user
                    )
                );

                $renderer = new Tracker_Artifact_CopyRenderer(
                    $this->getEventManager(),
                    $this,
                    $layout,
                    $this->getNatureIsChildLinkRetriever(),
                    $this->getVisitRecorder(),
                    $this->getHiddenFieldsetsDetector()
                );

                $renderer->display($request, $current_user);
                break;
            case 'manage-subscription':
                if ($this->doesUserHaveUnsubscribedFromTrackerNotification($this->getCurrentUser())) {
                    break;
                }

                $artifact_subscriber = new Tracker_ArtifactNotificationSubscriber($this, $this->getDao());

                if ($this->doesUserHaveUnsubscribedFromArtifactNotification($current_user)) {
                    $artifact_subscriber->subscribeUser($current_user, $request);
                    break;
                }

                $artifact_subscriber->unsubscribeUser($current_user, $request);
                break;

            default:
                ArtifactInstrumentation::increment(ArtifactInstrumentation::TYPE_VIEWED);
                if ($request->isAjax()) {
                    echo $this->fetchTooltip($current_user);
                } else {
                    header("Cache-Control: no-store, no-cache, must-revalidate");
                    $renderer = new Tracker_Artifact_ReadOnlyRenderer(
                        $this->getEventManager(),
                        $this,
                        $layout,
                        $this->getNatureIsChildLinkRetriever(),
                        $this->getVisitRecorder(),
                        $this->getHiddenFieldsetsDetector()
                    );
                    $renderer->display($request, $current_user);
                }
                break;
        }
    }

    private function getNatureIsChildLinkRetriever()
    {
        return new NatureIsChildLinkRetriever($this->getArtifactFactory(), $this->getArtifactlinkDao());
    }

    private function getArtifactlinkDao()
    {
        return new Tracker_FormElement_Field_Value_ArtifactLinkDao();
    }

    /**
     * @return VisitRecorder
     */
    private function getVisitRecorder()
    {
        return new VisitRecorder(new RecentlyVisitedDao());
    }

    private function sendUserDoesNotHavePermissionsErrorCode()
    {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker', 'unsufficient_permissions_for_ranking'));
        $GLOBALS['Response']->sendStatusCode(403);
    }

    private function userHasRankingPermissions($milestone_id)
    {
        $user_is_authorized = true;

        $this->getEventManager()->processEvent(
            ITEM_PRIORITY_CHANGE,
            array(
                'user_is_authorized' => &$user_is_authorized,
                'group_id'           => $this->getProjectId(),
                'milestone_id'       => $milestone_id,
                'user'               => $this->getCurrentUser()
            )
        );

        return $user_is_authorized;
    }

    private function getProjectId()
    {
        return $this->getTracker()->getGroupId();
    }

    /** @return Tracker_Artifact_PriorityManager */
    protected function getPriorityManager()
    {
        return new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            UserManager::instance(),
            Tracker_ArtifactFactory::instance()
        );
    }

    /** @return Tracker_Artifact[] */
    public function getChildrenForUser(PFUser $current_user)
    {
        $children = array();
        foreach ($this->getArtifactFactory()->getChildren($this) as $child) {
            if ($child->userCanView($current_user)) {
                $children[] = $child;
            }
        }
        return $children;
    }

    /** @return Tracker_ArtifactChildPresenter[] */
    private function getChildPresenterCollection(PFUser $current_user)
    {
        $presenters = array();
        foreach ($this->getChildrenForUser($current_user) as $child) {
            $tracker      = $child->getTracker();
            $semantics    = Tracker_Semantic_Status::load($tracker);

            $presenters[] = new Tracker_ArtifactChildPresenter($child, $this, $semantics, $this->getNatureIsChildLinkRetriever());
        }
        return $presenters;
    }

    private function getChildNaturePresenterCollection()
    {
        $presenters = array();
        $artifacts = $this->getNatureIsChildLinkRetriever()->getChildren($this);

        foreach ($artifacts as $artifact) {
            $tracker      = $artifact->getTracker();
            $semantics    = Tracker_Semantic_Status::load($tracker);

            $presenters[] = new Tracker_ArtifactChildPresenter($artifact, $this, $semantics, $this->getNatureIsChildLinkRetriever());
        }
        return $presenters;
    }

    public function hasChildren()
    {
        return $this->getArtifactFactory()->hasChildren($this);
    }

    /**
     * @return string html
     */
    public function fetchDirectLinkToArtifact()
    {
        return '<a class="direct-link-to-artifact"
            data-artifact-id="' . $this->getId() . '"
            href="' . $this->getUri() . '">' . $this->getXRef() . '</a>';
    }

    /**
     * @return string html
     */
    public function fetchDirectLinkToArtifactWithTitle()
    {
        return '<a class="direct-link-to-artifact" href="' . $this->getUri() . '">' . $this->getXRefAndTitle() . '</a>';
    }

    /**
     * @return string html
     */
    public function fetchDirectLinkToArtifactWithoutXRef()
    {
        $hp = Codendi_HTMLPurifier::instance();
        return '<a class="direct-link-to-artifact" href="' . $this->getUri() . '">' . $hp->purify($this->getTitle()) . '</a>';
    }

    public function getRestUri()
    {
        return self::REST_ROUTE . '/' . $this->getId();
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return TRACKER_BASE_URL . '/?aid=' . $this->getId();
    }

    /**
     * @return string the cross reference text: bug #42
     */
    public function getXRef()
    {
        return $this->getTracker()->getItemName() . ' #' . $this->getId();
    }

    /**
     * Fetch the html xref link to the artifact
     *
     * @return string html
     */
    public function fetchXRefLink()
    {
        return '<a class="cross-reference" href="/goto?' . http_build_query(array(
            'key'      => $this->getTracker()->getItemName(),
            'val'      => $this->getId(),
            'group_id' => $this->getTracker()->getGroupId(),
        )) . '">' . $this->getXRef() . '</a>';
    }

    /**
     * Returns a Tracker_FormElementFactory instance
     *
     * @return Tracker_FormElementFactory
     */
    protected function getFormElementFactory()
    {
        if (empty($this->formElementFactory)) {
            $this->formElementFactory = Tracker_FormElementFactory::instance();
        }
        return $this->formElementFactory;
    }

    public function setFormElementFactory(Tracker_FormElementFactory $factory)
    {
        $this->formElementFactory = $factory;
    }

    /**
     * Returns a Tracker_ArtifactFactory instance
     *
     * @return Tracker_ArtifactFactory
     */
    protected function getArtifactFactory()
    {
        if ($this->artifact_factory) {
            return $this->artifact_factory;
        }
        return Tracker_ArtifactFactory::instance();
    }

    public function setArtifactFactory(Tracker_ArtifactFactory $artifact_factory)
    {
        $this->artifact_factory = $artifact_factory;
    }

    public function getErrors()
    {
        $list_errors = array();
        $is_valid = true;
        $used_fields    = $this->getFormElementFactory()->getUsedFields($this->getTracker());
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
     * @param PFUser  $submitter         The user who is doing the update
     * @param bool $send_notification true if a notification must be sent, false otherwise
     * @param string  $comment_format    The comment (follow-up) type ("text" | "html")
     *
     * @throws Tracker_Exception In the validation
     * @throws Tracker_NoChangeException In the validation
     * @return Tracker_Artifact_Changeset|bool The new changeset if update is done without error, false otherwise
     */
    public function createNewChangeset($fields_data, $comment, PFUser $submitter, $send_notification = true, $comment_format = Tracker_Artifact_Changeset_Comment::TEXT_COMMENT)
    {
        $submitted_on = $_SERVER['REQUEST_TIME'];
        $validator    = new Tracker_Artifact_Changeset_NewChangesetFieldsValidator($this->getFormElementFactory(), $this->getWorkflowUpdateChecker());
        $creator      = $this->getNewChangesetCreator($validator);

        return $creator->create(
            $this,
            $fields_data,
            (string) $comment,
            $submitter,
            (int) $submitted_on,
            (bool) $send_notification,
            (string) $comment_format,
            new \Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping()
        );
    }

    public function createNewChangesetWhitoutRequiredValidation(
        $fields_data,
        $comment,
        PFUser $submitter,
        $send_notification,
        $comment_format
    ) {
        $submitted_on = $_SERVER['REQUEST_TIME'];
        $validator    = new NewChangesetFieldsWithoutRequiredValidationValidator($this->getFormElementFactory());
        $creator      = $this->getNewChangesetCreator($validator);

        return $creator->create(
            $this,
            $fields_data,
            (string) $comment,
            $submitter,
            (int) $submitted_on,
            (bool) $send_notification,
            (string) $comment_format,
            new \Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping()
        );
    }

    /**
     * @return ReferenceManager
     */
    public function getReferenceManager()
    {
        return ReferenceManager::instance();
    }

    /**
     * Returns the tracker Id this artifact belongs to
     */
    public function getTrackerId(): int
    {
        return (int) $this->tracker_id;
    }

    /**
     * Returns the tracker this artifact belongs to
     *
     * @return Tracker The tracker this artifact belongs to
     */
    public function getTracker()
    {
        if (!isset($this->tracker)) {
            $tracker = TrackerFactory::instance()->getTrackerById($this->tracker_id);
            if ($tracker === null) {
                throw new RuntimeException('Tracker does not exist');
            }
            $this->tracker = $tracker;
        }
        return $this->tracker;
    }

    public function setTracker(Tracker $tracker)
    {
        $this->tracker = $tracker;
        $this->tracker_id = $tracker->getId();
    }

    /**
     * Returns the last modified date of the artifact
     *
     * @return int The timestamp (-1 if no date)
     */
    public function getLastUpdateDate()
    {
        $last_changeset = $this->getLastChangeset();
        if ($last_changeset) {
            return $last_changeset->getSubmittedOn();
        }
        return -1;
    }

    public function wasLastModifiedByAnonymous()
    {
        $last_changeset = $this->getLastChangeset();
        if ($last_changeset) {
            if ($last_changeset->getSubmittedBy()) {
                return false;
            }
            return true;
        }
        return false;
    }

    public function getLastModifiedBy()
    {
        $last_changeset = $this->getLastChangeset();
        if ($last_changeset) {
            if ($last_changeset->getSubmittedBy()) {
                return $last_changeset->getSubmittedBy();
            }
            return $last_changeset->getEmail();
        }
        return $this->getSubmittedBy();
    }

    /**
     * @return int
     */
    public function getVersionIdentifier()
    {
        return $this->getLastUpdateDate();
    }

    /**
     * Returns the latest changeset of this artifact
     *
     * @return Tracker_Artifact_Changeset|null The latest changeset of this artifact, or null if no latest changeset
     */
    public function getLastChangeset()
    {
        if ($this->last_changeset !== null) {
            return $this->last_changeset;
        }
        if ($this->changesets === null) {
            $this->last_changeset = $this->getChangesetFactory()->getLastChangeset($this);
            return $this->last_changeset;
        }
        $changesets           = $this->getChangesets();
        $this->last_changeset = end($changesets);
        return $this->last_changeset;
    }

    /**
     * @return Tracker_Artifact_Changeset|null
     */
    public function getLastChangesetWithFieldValue(Tracker_FormElement_Field $field)
    {
        return $this->getChangesetFactory()->getLastChangesetWithFieldValue($this, $field);
    }

    /**
     * Returns the first changeset of this artifact
     *
     * @return Tracker_Artifact_Changeset The first changeset of this artifact
     */
    public function getFirstChangeset()
    {
        $changesets = $this->getChangesets();
        reset($changesets);
        return current($changesets);
    }

    public function hasMoreThanOneChangeset()
    {
        return count($this->getChangesets()) > 1;
    }

    /**
     * say if the changeset is the first one for this artifact
     *
     * @return bool
     */
    public function isFirstChangeset(Tracker_Artifact_Changeset $changeset)
    {
        $c = $this->getFirstChangeset();
        return $c->getId() == $changeset->getId();
    }

    private function getPriorityHistory()
    {
        return $this->getPriorityManager()->getArtifactPriorityHistory($this);
    }

    /**
     * @return Tracker_Artifact_Followup_Item[]
     */
    public function getFollowupsContent()
    {
        return $this->getSortedBySubmittedOn(
            array_merge(
                $this->getChangesetFactory()->getFullChangesetsForArtifact($this, $this->getCurrentUser()),
                $this->getPriorityHistory()
            )
        );
    }

    private function getSortedBySubmittedOn(array $followups_content)
    {
        $changeset_array = array();
        foreach ($followups_content as $changeset) {
            $timestamp = $changeset->getFollowUpDate();
            if (! isset($changeset_array[$timestamp])) {
                $changeset_array[$timestamp] = array($changeset);
            } else {
                $changeset_array[$timestamp][] = $changeset;
            }
        }
        ksort($changeset_array, SORT_NUMERIC);
        return $this->flattenChangesetArray($changeset_array);
    }

    private function flattenChangesetArray(array $changesets_per_timestamp)
    {
        $changesets = array();
        foreach ($changesets_per_timestamp as $changeset_per_timestamp) {
            foreach ($changeset_per_timestamp as $changeset) {
                $changesets[] = $changeset;
            }
        }
        return $changesets;
    }

    /**
     * Returns all the changesets of this artifact
     *
     * @return Tracker_Artifact_Changeset[] The changesets of this artifact
     */
    public function getChangesets()
    {
        if ($this->changesets === null) {
            $this->forceFetchAllChangesets();
        }
        return $this->changesets;
    }

    public function forceFetchAllChangesets()
    {
        $this->changesets     = $this->getChangesetFactory()->getChangesetsForArtifact($this);
        $this->last_changeset = null;
    }

    /**
     * @param array $changesets array of Tracker_Artifact_Changeset
     */
    public function setChangesets(array $changesets)
    {
        $this->changesets     = $changesets;
        $this->last_changeset = null;
    }

    public function clearChangesets()
    {
        $this->changesets     = null;
        $this->last_changeset = null;
    }

    public function addChangeset(Tracker_Artifact_Changeset $changeset)
    {
        $this->changesets[$changeset->getId()] = $changeset;
        $this->last_changeset                  = null;
    }

    /**
     * Get all commentators of this artifact
     *
     * @return array of strings (username or emails)
     */
    public function getCommentators()
    {
        $commentators = array();
        foreach ($this->getChangesets() as $c) {
            if ($submitted_by = $c->getSubmittedBy()) {
                if ($user = $this->getUserManager()->getUserById($submitted_by)) {
                    $commentators[] = $user->getUserName();
                }
            } elseif ($email = $c->getEmail()) {
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
    protected function getChangesetDao()
    {
        return new Tracker_Artifact_ChangesetDao();
    }

    /**
     * @return Tracker_Artifact_ChangesetFactory
     */
    protected function getChangesetFactory()
    {
        return new Tracker_Artifact_ChangesetFactory(
            $this->getChangesetDao(),
            new Tracker_Artifact_Changeset_ValueDao(),
            $this->getChangesetCommentDao(),
            new Tracker_Artifact_ChangesetJsonFormatter(
                $this->getMustacheRenderer()
            ),
            $this->getFormElementFactory()
        );
    }

    /**
     * @return MustacheRenderer
     */
    private function getMustacheRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(dirname(TRACKER_BASE_DIR) . '/templates');
    }

    /**
     * Return the ChangesetCommentDao
     *
     * @return Tracker_Artifact_Changeset_CommentDao The Dao
     */
    protected function getChangesetCommentDao()
    {
        return new Tracker_Artifact_Changeset_CommentDao();
    }

    /**
     * Returns the changeset of this artifact with Id $changeset_id, or null if not found
     *
     * @param int $changeset_id The Id of the changeset to retrieve
     *
     * @return Tracker_Artifact_Changeset|null The changeset, or null if not found
     */
    public function getChangeset($changeset_id)
    {
        if (! isset($this->changesets[$changeset_id])) {
            $this->changesets[$changeset_id] = $this->getChangesetFactory()->getChangeset($this, $changeset_id);
            $this->last_changeset            = null;
        }
        return $this->changesets[$changeset_id];
    }

    /**
     * Returns the previous changeset just before the changeset $changeset_id, or null if $changeset_id is the first one
     *
     * @param int $changeset_id The changeset reference
     *
     * @return Tracker_Artifact_Changeset The previous changeset, or null if not found
     */
    public function getPreviousChangeset($changeset_id)
    {
        $previous = null;
        $changesets = $this->getChangesets();
        foreach ($changesets as $changeset) {
            if ($changeset->getId() == $changeset_id) {
                break;
            }
            $previous = $changeset;
        }
        return $previous;
    }

    /**
     * Get the Id of this artifact
     *
     * @return int The Id of this artifact
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the Id of this artifact
     *
     * @param int $id the new id of the artifact
     *
     * @return Tracker_Artifact
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the value for this field in the changeset
     *
     * @param Tracker_FormElement_Field  $field     The field
     * @param Tracker_Artifact_Changeset $changeset The changeset. if null given take the last changeset of the artifact
     *
     */
    public function getValue(Tracker_FormElement_Field $field, ?Tracker_Artifact_Changeset $changeset = null): ?Tracker_Artifact_ChangesetValue
    {
        if (!$changeset) {
            $changeset = $this->getLastChangeset();
        }
        if ($changeset) {
            return $changeset->getValue($field);
        }
        return null;
    }

    /**
     * Returns the date (timestamp) the artifact ha been created
     *
     * @return int the timestamp for the date this aetifact was created
     */
    public function getSubmittedOn()
    {
        return $this->submitted_on;
    }

    /**
     * Returns the user who submitted the artifact
     *
     * @return int the user id
     */
    public function getSubmittedBy()
    {
        return $this->submitted_by;
    }

    /**
     * The user who created the artifact
     *
     * @return PFUser
     */
    public function getSubmittedByUser()
    {
        if (! isset($this->submitted_by_user)) {
            $this->submitted_by_user = $this->getUserManager()->getUserById($this->submitted_by);
        }
        if ($this->submitted_by_user === null) {
            $this->setSubmittedByUser($this->getUserManager()->getUserAnonymous());
        }
        return $this->submitted_by_user;
    }

    public function setSubmittedByUser(PFUser $user)
    {
        $this->submitted_by_user = $user;
        $this->submitted_by      = $user->getId();
    }

    /**
     * Returns the id of the artifact in this tracker
     *
     * @return int the artifact id
     */
    public function getPerTrackerArtifactId()
    {
        if ($this->per_tracker_id == null) {
            $this->per_tracker_id = $this->getDao()->getPerTrackerArtifactId($this->id);
        }
        return $this->per_tracker_id;
    }

    /**
     * Return Workflow the artifact should respect
     *
     * @return Workflow|null
     */
    public function getWorkflow()
    {
        $workflow = $this->getTracker()->getWorkflow();
        if ($workflow === null) {
            return null;
        }
        $workflow->setArtifact($this);
        return $workflow;
    }

    /**
     * Get the UserManager instance
     *
     * @return UserManager
     */
    public function getUserManager()
    {
        return UserManager::instance();
    }

    private function getCurrentUser()
    {
        return $this->getUserManager()->getCurrentUser();
    }

    /**
     * User want to link an artifact to the current one
     *
     * @param int  $linked_artifact_id The id of the artifact to link
     * @param PFUser $current_user       The user who made the link
     *
     * @return bool true if success false otherwise
     */
    public function linkArtifact($linked_artifact_id, PFUser $current_user)
    {
        $artlink_fields = $this->getFormElementFactory()->getUsedArtifactLinkFields($this->getTracker());
        if (count($artlink_fields)) {
            $comment       = '';
            $artlink_field = $artlink_fields[0];

            $linked_artifact_id = $this->filterArtifactIdsIAmAlreadyLinkedTo($artlink_field, $linked_artifact_id);
            if (! $linked_artifact_id) {
                return true;
            }

            $fields_data   = array();
            $fields_data[$artlink_field->getId()]['new_values'] = $linked_artifact_id;

            if ($this->getTracker()->isProjectAllowedToUseNature()) {
                $fields_data[$artlink_field->getId()]['natures'] = $this->getNoNatureForLink($linked_artifact_id);
            }

            try {
                $this->createNewChangeset($fields_data, $comment, $current_user);
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
     * User want to link an artifact to the current one
     *
     * @param array  $linked_artifact_ids The ids of the artifacts to link
     * @param PFUser $current_user       The user who made the link
     *
     * @return bool true if success false otherwise
     */
    public function linkArtifacts($linked_artifact_ids, PFUser $current_user)
    {
        $linked_artifact_ids = implode(',', $linked_artifact_ids);

        return $this->linkArtifact($linked_artifact_ids, $current_user);
    }

    private function unlinkArtifact($artlink_fields, $linked_artifact_id, PFUser $current_user)
    {
        $comment       = '';
        $artlink_field = $artlink_fields[0];
        $fields_data   = array();
        $fields_data[$artlink_field->getId()]['new_values'] = '';
        $fields_data[$artlink_field->getId()]['removed_values'] = array($linked_artifact_id => 1);

        try {
            $this->createNewChangeset($fields_data, $comment, $current_user);
        } catch (Tracker_NoChangeException $e) {
            $GLOBALS['Response']->addFeedback('info', $e->getMessage(), CODENDI_PURIFIER_LIGHT);
        } catch (Tracker_Exception $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
        }
    }

    private function filterArtifactIdsIAmAlreadyLinkedTo(Tracker_FormElement_Field_ArtifactLink $field, $linked_artifact_id)
    {
        $linked_artifact_id_as_array = explode(',', $linked_artifact_id);

        $last_changeset = $this->getLastChangeset();
        if (! $last_changeset) {
            return $linked_artifact_id;
        }

        $changeset_value = $last_changeset->getValue($field);
        \assert($changeset_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);
        if (! $changeset_value) {
            return $linked_artifact_id;
        }

        $existing_links              = $changeset_value->getArtifactIds();
        $linked_artifact_id_as_array = array_diff($linked_artifact_id_as_array, $existing_links);

        return implode(',', $linked_artifact_id_as_array);
    }

    /**
     * Get artifacts linked to the current artifact
     *
     * @param PFUser $user The user who should see the artifacts
     *
     * @return Tracker_Artifact[]
     */
    public function getLinkedArtifacts(PFUser $user)
    {
        $artifact_links      = array();
        $artifact_link_field = $this->getAnArtifactLinkField($user);
        $last_changeset      = $this->getLastChangeset();
        if ($artifact_link_field && $last_changeset) {
            $artifact_links = $artifact_link_field->getLinkedArtifacts($last_changeset, $user);
        }

        return $artifact_links;
    }

    /**
     * Get artifacts linked to the current artifact and reverse linked artifacts
     *
     * @return Tracker_Artifact[]
     */
    public function getLinkedAndReverseArtifacts(PFUser $user)
    {
        $linked_and_reverse_artifacts = [];
        $artifact_link_field          = $this->getAnArtifactLinkField($user);
        $last_changeset               = $this->getLastChangeset();

        if ($artifact_link_field && $last_changeset) {
            $linked_and_reverse_artifacts = $artifact_link_field->getLinkedAndReverseArtifacts($last_changeset, $user);
        }

        return $linked_and_reverse_artifacts;
    }

    /**
     * Get artifacts linked to the current artifact
     *
     * @see Tracker_FormElement_Field_ArtifactLink::getSlicedLinkedArtifacts()
     *
     * @param PFUser $user   The user who should see the artifacts
     * @param int    $limit  The number of artifact to fetch
     * @param int    $offset The offset
     *
     * @return Tracker_Artifact_PaginatedArtifacts
     */
    public function getSlicedLinkedArtifacts(PFUser $user, $limit, $offset)
    {
        $artifact_link_field = $this->getAnArtifactLinkField($user);
        if (! $artifact_link_field) {
            return new Tracker_Artifact_PaginatedArtifacts(array(), 0);
        }

        return $artifact_link_field->getSlicedLinkedArtifacts($this->getLastChangeset(), $user, $limit, $offset);
    }

    /**
     * Get artifacts linked to the current artifact and sub artifacts
     *
     * @param PFUser $user The user who should see the artifacts
     *
     * @return Array of Tracker_Artifact
     */
    public function getLinkedArtifactsOfHierarchy(PFUser $user)
    {
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
     * @return Tracker[]
     */
    public function getAllowedChildrenTypes()
    {
        return $this->getHierarchyFactory()->getChildren($this->getTrackerId());
    }

    /**
     * @return Tracker[]
     */
    public function getAllowedChildrenTypesForUser(PFUser $user)
    {
        $allowed_children = array();
        foreach ($this->getAllowedChildrenTypes() as $tracker) {
            if ($tracker->userCanSubmitArtifact($user)) {
                $allowed_children[] = $tracker;
            }
        }
        return $allowed_children;
    }

    /**
     * Get artifacts linked to the current artifact if
     * they are not in children.
     *
     * @param PFUser $user The user who should see the artifacts
     *
     * @return Array of Tracker_Artifact
     */
    public function getUniqueLinkedArtifacts(PFUser $user)
    {
        $sub_artifacts = $this->getLinkedArtifacts($user);
        $grandchild_artifacts = array();
        foreach ($sub_artifacts as $artifact) {
            $grandchild_artifacts = array_merge($grandchild_artifacts, $artifact->getLinkedArtifactsOfHierarchy($user));
        }
        array_filter($grandchild_artifacts);
        return array_diff($sub_artifacts, $grandchild_artifacts);
    }

    public function __toString()
    {
        return self::class . " #$this->id";
    }

    /**
     * Returns all ancestors of current artifact (from direct parent to oldest ancestor)
     *
     *
     * @return Tracker_Artifact[]
     */
    public function getAllAncestors(PFUser $user)
    {
        if (!isset($this->ancestors)) {
            $this->ancestors = $this->getHierarchyFactory()->getAllAncestors($user, $this);
        }
        return $this->ancestors;
    }

    public function setAllAncestors(array $ancestors)
    {
        $this->ancestors = $ancestors;
    }

    /**
     * Return the parent artifact of current artifact if any
     *
     *
     * @return Tracker_Artifact
     */
    public function getParent(PFUser $user)
    {
        return $this->getHierarchyFactory()->getParentArtifact($user, $this);
    }

    /**
     * Get parent artifact regartheless if user can access it
     *
     * Note: even if there are several parents, only the first one is returned
     */
    public function getParentWithoutPermissionChecking(): ?self
    {
        if ($this->parent_without_permission_checking !== self::NO_PARENT && ! isset($this->parent_without_permission_checking)) {
            $dar = $this->getDao()->getParents(array($this->getId()));
            if ($dar && count($dar) == 1) {
                $this->parent_without_permission_checking = $this->getArtifactFactory()->getInstanceFromRow($dar->current());
            } else {
                $this->parent_without_permission_checking = self::NO_PARENT;
            }
        }
        if ($this->parent_without_permission_checking === self::NO_PARENT) {
            return null;
        }
        return $this->parent_without_permission_checking;
    }

    public function setParentWithoutPermissionChecking($parent)
    {
        $this->parent_without_permission_checking = $parent;
    }

    /**
     * Get all sista & bro regartheless if user can access them
     *
     * @return Tracker_Artifact[]
     */
    public function getSiblingsWithoutPermissionChecking()
    {
        if (! isset($this->siblings_without_permission_checking)) {
            $this->siblings_without_permission_checking = $this->getDao()->getSiblings($this->getId())->instanciateWith(array($this->getArtifactFactory(), 'getInstanceFromRow'));
        }
        return $this->siblings_without_permission_checking;
    }

    public function setSiblingsWithoutPermissionChecking($siblings)
    {
        $this->siblings_without_permission_checking = $siblings;
    }

    /**
     * Returns the previously injected factory (e.g. in tests), or a new
     * instance (e.g. in production).
     *
     * @return Tracker_HierarchyFactory
     */
    public function getHierarchyFactory()
    {
        if ($this->hierarchy_factory == null) {
            $this->hierarchy_factory = Tracker_HierarchyFactory::instance();
        }
        return $this->hierarchy_factory;
    }


    public function setHierarchyFactory($hierarchy = null)
    {
        $this->hierarchy_factory = $hierarchy;
    }

    /**
     * Returns the ids of the children of the tracker.
     *
     * @return array of int
     */
    protected function getChildTrackersIds()
    {
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
    public function getAnArtifactLinkField(PFUser $user)
    {
        return $this->getFormElementFactory()->getAnArtifactLinkField($user, $this->getTracker());
    }

    /**
     * Return the first BurndownField (if any)
     *
     * @return Tracker_FormElement_Field_Burndown
     */
    public function getABurndownField(PFUser $user)
    {
        return $this->getFormElementFactory()->getABurndownField($user, $this->getTracker());
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
    public function summonArtifactRedirectors(Codendi_Request $request, Tracker_Artifact_Redirect $redirect)
    {
        $this->getEventManager()->processEvent(
            TRACKER_EVENT_REDIRECT_AFTER_ARTIFACT_CREATION_OR_UPDATE,
            array(
                'request'  => $request,
                'artifact' => $this,
                'redirect' => $redirect
            )
        );
    }

    private function summonArtifactAssociators(Codendi_Request $request, PFUser $current_user, $linked_artifact_id)
    {
        $this->getEventManager()->processEvent(
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

    /**
     * Return the authorised ugroups to see the artifact
     *
     * @return Array
     */
    private function getAuthorisedUgroups()
    {
        $ugroups = array();
        //Individual artifact permission
        if ($this->useArtifactPermissions()) {
            $rows = $this->permission_db_authorized_ugroups('PLUGIN_TRACKER_ARTIFACT_ACCESS');
            if ($rows !== false) {
                foreach ($rows as $row) {
                    $ugroups[] = $row['ugroup_id'];
                }
            }
        } else {
            $permissions = $this->getTracker()->getAuthorizedUgroupsByPermissionType();
            foreach ($permissions as $permission => $ugroups) {
                switch ($permission) {
                    case Tracker::PERMISSION_FULL:
                    case Tracker::PERMISSION_SUBMITTER:
                    case Tracker::PERMISSION_ASSIGNEE:
                    case Tracker::PERMISSION_SUBMITTER_ONLY:
                        foreach ($ugroups as $ugroup) {
                            $ugroups[] = $ugroup['ugroup_id'];
                        }
                        break;
                }
            }
        }
        return $ugroups;
    }

    /**
     * Returns ugroups of an artifact in a human readable format
     *
     * @return array
     */
    public function exportPermissions()
    {
        $project     = ProjectManager::instance()->getProject($this->getTracker()->getGroupId());
        $literalizer = new UGroupLiteralizer();
        $ugroupsId     = $this->getAuthorisedUgroups();
        return $literalizer->ugroupIdsToString($ugroupsId, $project);
    }

    protected function getDao()
    {
        return new Tracker_ArtifactDao();
    }

    /**
     * @return UnsubscribersNotificationDAO
     */
    private function getUnsubscribersNotificationDao()
    {
        return new UnsubscribersNotificationDAO();
    }

    protected function getCrossReferenceFactory()
    {
        return new CrossReferenceFactory($this->getId(), self::REFERENCE_NATURE, $this->getTracker()->getGroupId());
    }

    /**
     * Adds to $artifacts_node the xml export of the artifact.
     */
    public function exportToXML(
        SimpleXMLElement $artifacts_node,
        Tuleap\Project\XML\Export\ArchiveInterface $archive,
        Tracker_XML_Exporter_ArtifactXMLExporter $artifact_xml_exporter
    ) {
        if (count($this->getChangesets()) > 0) {
            $artifact_xml_exporter->exportFullHistory($artifacts_node, $this);

            $attachment_exporter = $this->getArtifactAttachmentExporter();
            $attachment_exporter->exportAttachmentsInArchive($this, $archive);
        }
    }

    /**
     * @return Tracker_XML_Exporter_ArtifactAttachmentExporter
     */
    private function getArtifactAttachmentExporter()
    {
        return new Tracker_XML_Exporter_ArtifactAttachmentExporter($this->getFormElementFactory());
    }

    /** @return string */
    public function getTokenBasedEmailAddress()
    {
        return trackerPlugin::EMAILGATEWAY_TOKEN_ARTIFACT_UPDATE . '@' . $this->getEmailDomain();
    }

    /** @return string */
    public function getInsecureEmailAddress()
    {
        return trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_UPDATE . '+' . $this->getId() . '@' . $this->getEmailDomain();
    }

    private function getEmailDomain()
    {
        $email_domain = ForgeConfig::get('sys_default_mail_domain');

        if (! $email_domain) {
            $email_domain = ForgeConfig::get('sys_default_domain');
        }

        return $email_domain;
    }

    private function getNewChangesetCreator(Tracker_Artifact_Changeset_FieldsValidator $fields_validator)
    {
        $creator = new Tracker_Artifact_Changeset_NewChangesetCreator(
            $fields_validator,
            new FieldsToBeSavedInSpecificOrderRetriever($this->getFormElementFactory()),
            $this->getChangesetDao(),
            $this->getChangesetCommentDao(),
            $this->getArtifactFactory(),
            $this->getEventManager(),
            $this->getReferenceManager(),
            $this->getSourceOfAssociationCollectionBuilder(),
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->getFormElementFactory()),
            $this->getTransactionExecutor(),
        );

        return $creator;
    }

    private function getSourceOfAssociationCollectionBuilder()
    {
        return new SourceOfAssociationCollectionBuilder(
            new SubmittedValueConvertor(
                $this->getArtifactFactory(),
                new SourceOfAssociationDetector(
                    $this->getHierarchyFactory()
                )
            ),
            $this->getFormElementFactory()
        );
    }

    private function getTransactionExecutor(): DBTransactionExecutor
    {
        if ($this->transaction_executor) {
            return $this->transaction_executor;
        }
        return new \Tuleap\DB\DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection());
    }

    public function setTransactionExecutorForTests(DBTransactionExecutor $transaction_executor)
    {
        $this->transaction_executor = $transaction_executor;
    }

    private function getNoNatureForLink($linked_artifact_id)
    {
        $types = array();
        $linked_artifact_ids_array = explode(',', $linked_artifact_id);
        foreach ($linked_artifact_ids_array as $linked_artifact_id) {
            $types[$linked_artifact_id] = Tracker_FormElement_Field_ArtifactLink::NO_NATURE;
        }
        return $types;
    }

    /**
     * @return BurndownCacheGenerationChecker
     */
    private function getBurndownCacheChecker()
    {
        $event_manager              = SystemEventManager::instance();
        $logger                     = \BackendLogger::getDefaultLogger(Tracker_FormElement_Field_Burndown::LOG_IDENTIFIER);
        $computed_dao               = new Tracker_FormElement_Field_ComputedDao();
        $form_element_factory       = Tracker_FormElementFactory::instance();
        $semantic_timeframe_builder = new SemanticTimeframeBuilder(new SemanticTimeframeDao(), $form_element_factory);
        $field_retriever            = new ChartConfigurationFieldRetriever(
            $this->getFormElementFactory(),
            $semantic_timeframe_builder,
            $logger
        );

        return new BurndownCacheGenerationChecker(
            $logger,
            new BurndownCacheGenerator($event_manager),
            $event_manager,
            $field_retriever,
            new ChartConfigurationValueChecker(
                $field_retriever,
                new ChartConfigurationValueRetriever(
                    $field_retriever,
                    new TimeframeBuilder(
                        $semantic_timeframe_builder,
                        $logger
                    ),
                    $logger
                )
            ),
            $computed_dao,
            new ChartCachedDaysComparator($logger),
            new BurndownRemainingEffortAdderForREST($field_retriever, $computed_dao)
        );
    }

    /**
     * @return BurndownCacheGenerator
     */
    private function getBurndownCacheGenerator()
    {
        return new BurndownCacheGenerator(SystemEventManager::instance());
    }

    /**
     * Protected for test purposes
     * @return WorkflowUpdateChecker
     */
    protected function getWorkflowUpdateChecker()
    {
        $frozen_field_detector = new FrozenFieldDetector(
            new TransitionRetriever(
                new StateFactory(
                    new TransitionFactory(
                        Workflow_Transition_ConditionFactory::build(),
                        EventManager::instance(),
                        new DBTransactionExecutorWithConnection(
                            DBFactory::getMainTuleapDBConnection()
                        )
                    ),
                    new SimpleWorkflowDao()
                ),
                new TransitionExtractor()
            ),
            new FrozenFieldsRetriever(
                new FrozenFieldsDao(),
                $this->getFormElementFactory()
            )
        );
        return new WorkflowUpdateChecker($frozen_field_detector);
    }

    private function getHiddenFieldsetsDetector(): HiddenFieldsetsDetector
    {
        return new HiddenFieldsetsDetector(
            new TransitionRetriever(
                new StateFactory(
                    new TransitionFactory(
                        Workflow_Transition_ConditionFactory::build(),
                        EventManager::instance(),
                        new DBTransactionExecutorWithConnection(
                            DBFactory::getMainTuleapDBConnection()
                        )
                    ),
                    new SimpleWorkflowDao()
                ),
                new TransitionExtractor()
            ),
            new HiddenFieldsetsRetriever(
                new HiddenFieldsetsDao(),
                Tracker_FormElementFactory::instance()
            ),
            Tracker_FormElementFactory::instance()
        );
    }
}
