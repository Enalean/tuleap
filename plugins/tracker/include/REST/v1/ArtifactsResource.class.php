<?php
/**
 * Copyright (c) Enalean, 2013-present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use EventManager;
use FeedbackDao;
use Luracast\Restler\RestException;
use PFUser;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use Psr\Log\NullLogger;
use Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException;
use Tracker_Artifact_Attachment_FileNotFoundException;
use Tracker_Artifact_Changeset as Changeset;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_Artifact_PriorityManager;
use Tracker_Artifact_XMLImportBuilder;
use Tracker_ArtifactFactory;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use Tracker_FormElementFactory;
use Tracker_URLVerification;
use Tracker_XML_Exporter_ArtifactXMLExporterBuilder;
use Tracker_XML_Exporter_LocalAbsoluteFilePathXMLExporter;
use Tracker_XML_Exporter_NullChildrenCollector;
use TrackerFactory;
use TransitionFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Action\AreTherePermissionsToMigrateVerifier;
use Tuleap\Tracker\Action\AreUserGroupFieldsCompatibleVerifier;
use Tuleap\Tracker\Action\BeforeMoveArtifact;
use Tuleap\Tracker\Action\CanPermissionsBeFullyMovedVerifier;
use Tuleap\Tracker\Action\CanStaticFieldValuesBeFullyMovedVerifier;
use Tuleap\Tracker\Action\CanUserGroupValuesBeFullyMovedVerifier;
use Tuleap\Tracker\Action\DryRunDuckTypingFieldCollector;
use Tuleap\Tracker\Action\FieldCanBeEasilyMigratedVerifier;
use Tuleap\Tracker\Action\IsPermissionsOnArtifactFieldVerifier;
use Tuleap\Tracker\Action\IsUserGroupListFieldVerifier;
use Tuleap\Tracker\Action\MegaMoverArtifact;
use Tuleap\Tracker\Action\MegaMoverArtifactByDuckTyping;
use Tuleap\Tracker\Action\AreStaticListFieldsCompatibleVerifier;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollector;
use Tuleap\Tracker\Action\Move\NoFeedbackFieldCollector;
use Tuleap\Tracker\Action\MoveContributorSemanticChecker;
use Tuleap\Tracker\Action\MoveDescriptionSemanticChecker;
use Tuleap\Tracker\Action\MoveStatusSemanticChecker;
use Tuleap\Tracker\Action\MoveTitleSemanticChecker;
use Tuleap\Tracker\Action\OpenListFieldsCompatibilityVerifier;
use Tuleap\Tracker\Action\OpenListFieldVerifier;
use Tuleap\Tracker\Action\StaticListFieldVerifier;
use Tuleap\Tracker\Action\CanUserFieldValuesBeFullyMovedVerifier;
use Tuleap\Tracker\Action\AreUserFieldsCompatibleVerifier;
use Tuleap\Tracker\Action\UserGroupOpenListFieldVerifier;
use Tuleap\Tracker\Action\UserListFieldVerifier;
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfig;
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfigDAO;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactsDeletion\UserDeletionRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactDeletionLimitRetriever;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactDeletorBuilder;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionDAO;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionLimitReachedException;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionManager;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\DeletionOfArtifactsIsNotAllowedException;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\CachingTrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsRunner;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactForwardLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactLinksByChangesetCache;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\Link\ArtifactUpdateHandler;
use Tuleap\Tracker\Exception\SemanticTitleNotDefinedException;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUgroupsValueDao;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionDuckTypingMatcher;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\Permission\SubmissionPermissionVerifier;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\REST\Artifact\ArtifactCreator;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\ArtifactRestUpdateConditionsChecker;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataFromValuesByFieldBuilder;
use Tuleap\Tracker\REST\Artifact\MovedArtifactValueBuilder;
use Tuleap\Tracker\REST\Artifact\PUTHandler;
use Tuleap\Tracker\REST\Artifact\StatusValueRepresentation;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\PermissionsExporter;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\REST\v1\Move\BeforeMoveChecker;
use Tuleap\Tracker\REST\v1\Move\DryRunMover;
use Tuleap\Tracker\REST\v1\Move\HeaderForMoveSender;
use Tuleap\Tracker\REST\v1\Move\MovePatchAction;
use Tuleap\Tracker\REST\v1\Move\PostMoveArtifactRESTAddFeedback;
use Tuleap\Tracker\REST\v1\Move\RestArtifactMover;
use Tuleap\Tracker\REST\WorkflowRestBuilder;
use Tuleap\Tracker\Tracker\XML\Updater\BindValueForDuckTypingUpdater;
use Tuleap\Tracker\Tracker\XML\Updater\BindValueForSemanticUpdater;
use Tuleap\Tracker\Tracker\XML\Updater\MoveChangesetXMLDuckTypingUpdater;
use Tuleap\Tracker\Tracker\XML\Updater\MoveChangesetXMLSemanticUpdater;
use Tuleap\Tracker\Tracker\XML\Updater\OpenListUserGroupsByDuckTypingUpdater;
use Tuleap\Tracker\Tracker\XML\Updater\PermissionsByDuckTypingUpdater;
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
use Tuleap\Tracker\XML\Updater\MoveChangesetXMLUpdater;
use UGroupManager;
use UserManager;
use UserXMLExportedCollection;
use UserXMLExporter;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;
use XMLImportHelper;

class ArtifactsResource extends AuthenticatedResource
{
    public const MAX_LIMIT          = 50;
    public const DEFAULT_LIMIT      = 10;
    public const DEFAULT_OFFSET     = 0;
    public const MAX_ARTIFACT_BATCH = 100;
    public const ORDER_ASC          = 'asc';
    public const ORDER_DESC         = 'desc';

    public const VALUES_FORMAT_COLLECTION = 'collection';
    public const VALUES_FORMAT_BY_FIELD   = 'by_field';
    public const VALUES_FORMAT_ALL        = 'all';
    public const VALUES_DEFAULT           = null;

    public const DEFAULT_TRACKER_STRUCTURE  = self::MINIMAL_TRACKER_STRUCTURE;
    public const MINIMAL_TRACKER_STRUCTURE  = 'minimal';
    public const COMPLETE_TRACKER_STRUCTURE = 'complete';

    public const EMPTY_TYPE = '';

    private PostMoveArtifactRESTAddFeedback $post_move_action;
    private UserManager $user_manager;
    private UserDeletionRetriever $user_deletion_retriever;
    private Tracker_ArtifactFactory $artifact_factory;
    private ArtifactRepresentationBuilder $builder;
    private Tracker_FormElementFactory $formelement_factory;
    private TrackerFactory $tracker_factory;
    private MovedArtifactValueBuilder $moved_value_builder;
    private ArtifactsDeletionManager $artifacts_deletion_manager;
    private ArtifactsDeletionConfig $artifacts_deletion_config;
    private EventManager $event_manager;
    private \Tracker_REST_TrackerRestBuilder $tracker_rest_builder;

    public function __construct()
    {
        $this->tracker_factory     = TrackerFactory::instance();
        $this->formelement_factory = Tracker_FormElementFactory::instance();
        $this->artifact_factory    = Tracker_ArtifactFactory::instance();
        $this->user_manager        = UserManager::instance();
        $this->builder             = new ArtifactRepresentationBuilder(
            $this->formelement_factory,
            $this->artifact_factory,
            new TypeDao(),
            new ChangesetRepresentationBuilder(
                $this->user_manager,
                $this->formelement_factory,
                new CommentRepresentationBuilder(
                    CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance())
                ),
                new PermissionChecker(new CachingTrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentUGroupEnabledDao())))
            )
        );
        $this->moved_value_builder = new MovedArtifactValueBuilder();

        $this->artifacts_deletion_config = new ArtifactsDeletionConfig(
            new ArtifactsDeletionConfigDAO()
        );

        $artifacts_deletion_dao           = new ArtifactsDeletionDAO();
        $this->user_deletion_retriever    = new UserDeletionRetriever($artifacts_deletion_dao);
        $this->artifacts_deletion_manager = new ArtifactsDeletionManager(
            $artifacts_deletion_dao,
            ArtifactDeletorBuilder::build(),
            new ArtifactDeletionLimitRetriever($this->artifacts_deletion_config, $this->user_deletion_retriever)
        );

        $this->event_manager = EventManager::instance();

        $this->post_move_action = new PostMoveArtifactRESTAddFeedback(
            new FeedbackDao()
        );

        $transition_retriever = new TransitionRetriever(
            new StateFactory(
                TransitionFactory::instance(),
                new SimpleWorkflowDao()
            ),
            new TransitionExtractor()
        );

        $frozen_fields_detector = new FrozenFieldDetector(
            $transition_retriever,
            new FrozenFieldsRetriever(
                new FrozenFieldsDao(),
                Tracker_FormElementFactory::instance()
            )
        );

        $this->tracker_rest_builder = new \Tracker_REST_TrackerRestBuilder(
            $this->formelement_factory,
            new FormElementRepresentationsBuilder(
                $this->formelement_factory,
                new PermissionsExporter(
                    $frozen_fields_detector
                ),
                new HiddenFieldsetChecker(
                    new HiddenFieldsetsDetector(
                        $transition_retriever,
                        new HiddenFieldsetsRetriever(
                            new HiddenFieldsetsDao(),
                            Tracker_FormElementFactory::instance()
                        ),
                        Tracker_FormElementFactory::instance()
                    ),
                    new FieldsExtractor()
                ),
                new PermissionsForGroupsBuilder(
                    new \UGroupManager(),
                    $frozen_fields_detector,
                    new PermissionsFunctionsWrapper()
                ),
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao()
                )
            ),
            new PermissionsRepresentationBuilder(
                new \UGroupManager(),
                new PermissionsFunctionsWrapper()
            ),
            new WorkflowRestBuilder()
        );
    }

    /**
     * Get artifacts
     *
     *
     * <p>
     * $query parameter expects following format <code>{"id":[x,y,z]}</code> where x, y and z are artifact ID.
     * No more than 100 artifacts can be requested at once.
     * </p>
     *
     *
     * @url GET
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param string $query JSON object of search criteria properties {@from query}
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}{@max 100}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array
     * @psalm-return array{"collection":ArtifactRepresentation[]}
     *
     * @throws RestException 403
     */
    public function getArtifacts(string $query, int $limit = self::MAX_ARTIFACT_BATCH, int $offset = self::DEFAULT_OFFSET): array
    {
        $this->checkAccess();

        $this->options();

        $query_parameter_parser = new QueryParameterParser(new JsonDecoder());

        try {
            $requested_artifact_ids = $query_parameter_parser->getArrayOfInt($query, 'id');
        } catch (QueryParameterException $ex) {
            throw new RestException(400, $ex->getMessage());
        }
        if (count($requested_artifact_ids) > 100) {
            throw new RestException(403, 'No more than ' . self::MAX_ARTIFACT_BATCH . ' artifacts can be requested at once.');
        }

        $user                     = $this->user_manager->getCurrentUser();
        $artifact_representations = [];

        $artifacts = $this->artifact_factory->getArtifactsByArtifactIdList(
            array_slice($requested_artifact_ids, $offset, $limit)
        );

        if (! (count($artifacts) > 0)) {
            Header::sendPaginationHeaders($limit, $offset, count($requested_artifact_ids), self::MAX_ARTIFACT_BATCH);
            return [self::VALUES_FORMAT_COLLECTION => $artifact_representations];
        }

        foreach ($artifacts as $artifact) {
            if ($artifact->userCanView($user)) {
                $artifact_representations[] = $this->builder->getArtifactRepresentationWithFieldValuesInBothFormat(
                    $user,
                    $artifact,
                    MinimalTrackerRepresentation::build($artifact->getTracker()),
                    StatusValueRepresentation::buildFromArtifact($artifact, $user)
                );
            }
        }

        Header::sendPaginationHeaders($limit, $offset, count($requested_artifact_ids), self::MAX_ARTIFACT_BATCH);
        return [self::VALUES_FORMAT_COLLECTION => $artifact_representations];
    }

    /**
     * Get artifact
     *
     * Get the content of a given artifact. In addition to the artifact representation,
     * it sets Last-Modified header with the last update date of the element.
     * <br/><br/>
     * The "values_format" parameter allows you to choose how the artifact's values will be
     * formatted. When no "values_format" is provided, "collection" is chosen by default. Using "all"
     * will return both formats at the same time.
     * <br/><br/>
     * <pre>
     * /!\ Only alphanumeric fields are returned when choosing the "by_field" format! /!\
     * </pre>
     * <br/><br/>
     * Example with "collection":
     * <pre>
     * "values": [<br/>
     *   {<br/>
     *     "field_id": 1369,<br/>
     *     "type": "string",<br/>
     *     "label": "Title",<br/>
     *     "value": "Lorem ipsum dolor sit amet"<br/>
     *   },<br/>
     *   {<br/>
     *     "field_id": 1368,<br/>
     *     "type": "int",<br/>
     *     "label": "Remaining Effort",<br/>
     *     "value": 1447<br/>
     *   }<br/>
     * ]<br/>
     * </pre>
     * <br/><br/>
     * Example with "by_field":
     * <pre>
     * "values_by_field": {<br/>
     *   "title": {<br/>
     *     "field_id": 1369,<br/>
     *     "type": "string",<br/>
     *     "label": "Title",<br/>
     *     "value": "Lorem ipsum dolor sit amet"<br/>
     *   },<br/>
     *   "remaining_effort": {<br/>
     *     "field_id": 1368,<br/>
     *     "type": "int",<br/>
     *     "label": "Remaining Effort",<br/>
     *     "value": 1447<br/>
     *   }<br/>
     * }<br/>
     * </pre>
     * <p>Text field values can be rendered as two formats: HTML or Text. Text field values rendered as HTML can have two source formats: HTML itself or CommonMark (Markdown).<br/>
     * Text field values that are already written in HTML have the following structure:</p>
     * <pre><code>{<br/>
     *   &quot;value&quot;: &quot;&lt;p&gt;HTML string&lt;/p&gt;&quot;,<br/>
     *   &quot;format&quot;: &quot;html&quot;<br/>
     * }</code></pre>
     * <p>Text field values that are written in CommonMark format (Markdown) have an additional "commonmark" property that contains the source.
     * Notice that they also have format "html":</p>
     * <pre><code>{<br/>
     *   &quot;value&quot;: &quot;&lt;p&gt;&lt;strong&gt;Markdown&lt;/strong&gt; string&lt;/p&gt;&quot;,<br/>
     *   &quot;format&quot;: &quot;html&quot;,<br/>
     *   &quot;commonmark&quot;: &quot;\*\*Markdown\*\* string&quot;<br/>
     * }</code></pre>
     *
     * @url GET {id}
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the artifact
     * @param string $values_format The format of the value {@from query} {@choice ,collection,by_field,all}
     * @param string $tracker_structure_format The format of the structure {@from query} {@choice ,minimal,complete}
     *
     * @return ArtifactRepresentation
     *
     * @throws RestException 403
     */
    public function getId($id, $values_format = self::VALUES_DEFAULT, $tracker_structure_format = self::DEFAULT_TRACKER_STRUCTURE)
    {
        $this->checkAccess();

        $user     = $this->user_manager->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $artifact->getTracker()->getProject()
        );

        $this->sendAllowHeadersForArtifact();
        $this->sendLastModifiedHeader($artifact);
        $this->sendETagHeader($artifact);

        if ($tracker_structure_format === self::COMPLETE_TRACKER_STRUCTURE) {
            $tracker_representation = $this->tracker_rest_builder->getTrackerRepresentationInArtifactContext(
                $user,
                $artifact
            );
        } else {
            $tracker_representation = MinimalTrackerRepresentation::build($artifact->getTracker());
        }

        if ($values_format === self::VALUES_DEFAULT || $values_format === self::VALUES_FORMAT_COLLECTION) {
            $representation = $this->builder->getArtifactRepresentationWithFieldValues($user, $artifact, $tracker_representation, StatusValueRepresentation::buildFromArtifact($artifact, $user));
        } elseif ($values_format === self::VALUES_FORMAT_BY_FIELD) {
            $representation = $this->builder->getArtifactRepresentationWithFieldValuesByFieldValues($user, $artifact, $tracker_representation, StatusValueRepresentation::buildFromArtifact($artifact, $user));
        } elseif ($values_format === self::VALUES_FORMAT_ALL) {
            $representation = $this->builder->getArtifactRepresentationWithFieldValuesInBothFormat($user, $artifact, $tracker_representation, StatusValueRepresentation::buildFromArtifact($artifact, $user));
        }

        return $representation;
    }

    /**
     * Get possible types for an artifact
     *
     * @url GET {id}/links
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the artifact
     *
     * @return ArtifactLinkRepresentation
     *
     * @throws RestException 403
     */
    public function getArtifactLinkTypes($id)
    {
        $this->checkAccess();

        $user     = $this->user_manager->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $artifact->getTracker()->getProject()
        );

        $artifact_link_representation = ArtifactLinkRepresentation::build($artifact);

        $this->sendAllowHeadersForLinkTypes();
        return $artifact_link_representation;
    }

    /**
     * @url OPTIONS {id}/links
     *
     * @param int $id Id of the artifact
     */
    public function optionsArtifactLinkTypes($id)
    {
        $this->sendAllowHeadersForLinkTypes();
    }

    /**
     * Get all artifacts linked by type
     *
     * Get all the artifacts linked by type.
     * If no type is provided, it will search linked artifacts with no type.
     *
     * @url GET {id}/linked_artifacts
     *
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the artifact
     * @param string $direction The artifact link direction {@from query} {@choice forward,reverse}
     * @param string $nature The artifact link type to filter {@from query}
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array
     * @psalm-return array{collection:list<\Tuleap\Tracker\REST\Artifact\ArtifactRepresentation>}
     *
     * @throws RestException 403
     */
    public function getLinkedArtifacts(
        $id,
        $direction,
        $nature = self::EMPTY_TYPE,
        $limit = 10,
        $offset = self::DEFAULT_OFFSET,
    ) {
        $this->checkAccess();

        $user     = $this->user_manager->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $artifact->getTracker()->getProject()
        );

        $linked_artifacts = $this->builder->getArtifactRepresentationCollection(
            $user,
            $artifact,
            $nature,
            $direction,
            $offset,
            $limit
        );

        $this->sendAllowHeadersForLinkedArtifacts();
        Header::sendPaginationHeaders($limit, $offset, $linked_artifacts->getTotalSize(), self::MAX_LIMIT);

        $artifact_representations = [];
        foreach ($linked_artifacts->getArtifacts() as $linked_artifact) {
            $tracker_representation = MinimalTrackerRepresentation::build($linked_artifact->getTracker());

            $artifact_representations[] = $this->builder->getArtifactRepresentationWithFieldValuesInBothFormat(
                $user,
                $linked_artifact,
                $tracker_representation,
                StatusValueRepresentation::buildFromArtifact($linked_artifact, $user)
            );
        }

        return [
            self::VALUES_FORMAT_COLLECTION => $artifact_representations,
        ];
    }

    /**
     * @url OPTIONS {id}/linked_artifacts
     *
     * @param int $id Id of the artifact
     */
    public function optionsLinkedArtifacts($id)
    {
        $this->sendAllowHeadersForLinkedArtifacts();
    }

    /**
     * @url OPTIONS {id}/changesets
     *
     * @param int $id Id of the artifact
     */
    public function optionsArtifactChangesets($id)
    {
        $this->sendAllowHeadersForChangesets();
    }

    /**
     * Get changesets
     *
     * Get the changesets of a given artifact
     * <br/>
     * <br/>
     * Comments can be rendered as two formats: HTML or Text. Comments rendered as HTML can have two source formats: HTML itself or CommonMark (Markdown).<br/>
     * Comments that are already written in HTML have the following structure:
     * <pre><code>{<br/>
     *   &quot;body&quot;: &quot;&lt;p&gt;HTML with art #123&lt;/p&gt;&quot;,<br/>
     *   &quot;post_processed_body&quot;: &quot;&lt;p&gt;HTML with &lt;a href=&quot;...&amp;aid=123&quot;&gt;art #123&lt;/a&gt;&lt;/p&gt;&quot;,<br/>
     *   &quot;format&quot;: &quot;html&quot;<br/>
     * }</code></pre>
     * <p>Comments that are written in CommonMark format (Markdown) have an additional "commonmark" property that contains the source.
     * Notice that they also have format "html":</p>
     * <pre><code>{<br/>
     *   &quot;body&quot;: &quot;&lt;p&gt;&lt;strong&gt;Markdown&lt;/strong&gt; with art #123&lt;/p&gt;&quot;,<br/>
     *   &quot;post_processed_body&quot;: &quot;&lt;p&gt;&lt;strong&gt;Markdown&lt;/strong&gt; with &lt;a href=&quot;...&amp;aid=123&quot;&gt;art #123&lt;/a&gt;&lt;/p&gt;&quot;,<br/>
     *   &quot;format&quot;: &quot;html&quot;,<br/>
     *   &quot;commonmark&quot;: &quot;\*\*Markdown\*\* with art #123&quot;<br/>
     * }</code></pre>
     * <p>"post_processed_body" will have its references (for example "art #123") converted to links.</p>
     *
     * @url GET {id}/changesets
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the artifact
     * @param string $fields Whether you want to fetch all fields or just comments {@from path}{@choice all,comments}
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     * @param string $order By default the changesets are returned by Changeset Id ASC. Set this parameter to either ASC or DESC {@from path}{@choice asc,desc}
     * @return array {@type Tuleap\Tracker\REST\ChangesetRepresentation}
     *
     * @throws RestException 403
     */
    public function getArtifactChangesets(
        $id,
        $fields = Changeset::FIELDS_ALL,
        $limit = self::DEFAULT_LIMIT,
        $offset = self::DEFAULT_OFFSET,
        $order = self::ORDER_ASC,
    ) {
        $this->checkAccess();
        $user     = $this->user_manager->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $artifact->getTracker()->getProject()
        );

        $reverse_order = (bool) (strtolower($order) === self::ORDER_DESC);
        $changesets    = $this->builder->getArtifactChangesetsRepresentation($user, $artifact, $fields, $offset, $limit, $reverse_order);

        $this->sendAllowHeadersForChangesets();
        Header::sendPaginationHeaders($limit, $offset, $changesets->totalCount(), self::MAX_LIMIT);
        return $changesets->toArray();
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the artifact
     */
    public function optionsId($id)
    {
        $this->sendAllowHeadersForArtifact();
    }

    /**
     * Update artifact
     *
     * Things to take into account:
     * <ol>
     *  <li>You will get an error (400) if there are no changes in submitted document</li>
     *  <li>You can re-use the same document provided by /artifacts/:id route
     *      section. Even if it contains more data. The extra data/info will be ignored</li>
     *  <li>You don't need to set all 'values' of the artifact, you can restrict to the modified ones</li>
     *  <li>Examples:</li>
     *    <ol>
     *      <li>To update a file field, the value must be an array of the ids of the attachment you want to keep attached together with the new ones you want to attach.
     *          Each new file must correspond to valid /artifact_temporary_files/:id resource.
     *          A user can only add their own temporary files</li>
     *      <li>To empty a file field of its content, the value should be empty (e.g. value: [] or value: "").</li>
     *      <li>To link an artifact you can use the "<strong>all_links</strong>" key which contains a json array of artifact id you want to link with the wanted type. </br>
     *          Example: {"id": 11, "direction": "reverse", "type": "my_link_type"} with: </br>
     *          id: the artifact id to be linked</br>
     *          direction: the direction of the link between two artifact, the value must be "<strong>forward</strong>" or "<strong>reverse</strong>" </br>
     *          type: the artifact link type (e.g: _is_child) </br>
     *          /!\ The "<strong>all_links</strong>" key cannot be used at the same time as "<strong>links</strong>" and/or "<strong>parent</strong>" key </br>
     *          Example when I update artifact #5 with following payload:
     *          <pre>
     *           {  <br/>
     *             &nbsp; "values": [{ <br/>
     *             &nbsp; "field_id": 543, // field id of the artifact link field<br/>
     *             &nbsp; "all_links": <br/>
     *              &nbsp; &nbsp; [ <br/>
     *                 &nbsp; &nbsp; &nbsp; {"id": 11, "direction": "reverse", "type": "_is_child"}, // artifact #11 will be the parent of the given artifact #5 <br/>
     *                 &nbsp; &nbsp; &nbsp; {"id": 151, "direction": "forward", "type": "_is_child"} // artifact #151 will be the child of the given artifact #5 <br/>
     *             &nbsp; &nbsp; ] <br/>
     *            &nbsp; }] <br/>
     *          }
     *         </pre>
     *          In previous payload:</br>
     *          "<strong>Forward</strong>" direction will create a link between artifacts like the following : art #151 will be a direct link in art #5</br>
     *          "<strong>Reverse</strong>" direction will create a link between artifacts like the following : art #11 will be a reverse link of art #5 </br>
     *      </li>
     *    </ol>
     * </ol>
     *
     * @url PUT {id}
     * @param string                            $id      Id of the artifact
     * @param array                             $values  Artifact fields values {@from body} {@type \Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation}
     * @param NewChangesetCommentRepresentation $comment Comment about update {body, format} {@from body}
     *
     * @throws RestException 400
     * @throws RestException 403
     */
    protected function putId($id, array $values, ?NewChangesetCommentRepresentation $comment = null)
    {
        $transaction_executor = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );
        $user                 = $this->user_manager->getCurrentUser();
        $artifact             = $this->getArtifactById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $artifact->getTracker()->getProject()
        );

        $usage_dao        = new ArtifactLinksUsageDao();
        $fields_retriever = new FieldsToBeSavedInSpecificOrderRetriever($this->formelement_factory);
        $event_dispatcher = EventManager::instance();

        $changeset_creator = new NewChangesetCreator(
            new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                $this->formelement_factory,
                new ArtifactLinkValidator(
                    $this->artifact_factory,
                    new TypePresenterFactory(new TypeDao(), $usage_dao),
                    $usage_dao,
                    $event_dispatcher,
                ),
                new WorkflowUpdateChecker(
                    new FrozenFieldDetector(
                        new TransitionRetriever(
                            new StateFactory(\TransitionFactory::instance(), new SimpleWorkflowDao()),
                            new TransitionExtractor()
                        ),
                        FrozenFieldsRetriever::instance(),
                    )
                )
            ),
            $fields_retriever,
            $this->event_manager,
            new \Tracker_Artifact_Changeset_ChangesetDataInitializator($this->formelement_factory),
            $transaction_executor,
            ArtifactChangesetSaver::build(),
            new ParentLinkAction($this->artifact_factory),
            new AfterNewChangesetHandler($this->artifact_factory, $fields_retriever),
            ActionsRunner::build(\BackendLogger::getDefaultLogger()),
            new ChangesetValueSaver(),
            \WorkflowFactory::instance(),
            new CommentCreator(
                new \Tracker_Artifact_Changeset_CommentDao(),
                \ReferenceManager::instance(),
                new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao()),
                new ChangesetCommentIndexer(
                    new ItemToIndexQueueEventBased($event_dispatcher),
                    $event_dispatcher,
                    new \Tracker_Artifact_Changeset_CommentDao(),
                ),
                new TextValueValidator(),
            )
        );

        $fields_data_builder       = new FieldsDataBuilder(
            $this->formelement_factory,
            new NewArtifactLinkChangesetValueBuilder(
                new ArtifactForwardLinksRetriever(
                    new ArtifactLinksByChangesetCache(),
                    new ChangesetValueArtifactLinkDao(),
                    $this->artifact_factory
                ),
            ),
            new NewArtifactLinkInitialChangesetValueBuilder()
        );
        $update_conditions_checker = new ArtifactRestUpdateConditionsChecker();
        $artifact_factory          =  Tracker_ArtifactFactory::instance();

        $reverse_link_retriever = new ReverseLinksRetriever(
            new ReverseLinksDao(),
            $artifact_factory
        );

        $artifact_from_rest_updater = new ArtifactUpdateHandler(
            $changeset_creator,
            $this->formelement_factory,
            $artifact_factory,
        );

        $this->sendAllowHeadersForArtifact();

        $put_handler = new PUTHandler(
            $fields_data_builder,
            $reverse_link_retriever,
            $artifact_from_rest_updater,
            $transaction_executor,
            $update_conditions_checker,
        );
        $put_handler->handle($values, $artifact, $user, $comment);

        $this->sendLastModifiedHeader($artifact);
        $this->sendETagHeader($artifact);
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        Header::allowOptionsGetPost();
    }

    /**
     * Create artifact
     *
     * Create an artifact in a tracker
     *
     * <p>Things to take into account:</p>
     * <ol>
     *  <li>You don't need to set all "values" of the artifact, you can pass only the ones you want to add,
     *      together with those that are required (depends on a given tracker's configuration).
     *  </li>
     *  <li>
     *    <pre>
     *    /!\ Only alphanumeric fields are taken into account when providing the "values_by_field" format! /!\
     *    </pre>
     *    <br/><br/>
     *    You can create an artifact with the "values_by_field" format.
     *    Example:
     *    <pre>
     *    {<br/>
     *      "tracker": {"id": 29},<br/>
     *      "values_by_field": {<br/>
     *        "title": {<br/>
     *          "value": "Lorem ipsum dolor sit amet"<br/>
     *        },<br/>
     *        "remaining_effort": {<br/>
     *          "value": 75<br/>
     *        }<br/>
     *      }<br/>
     *    }<br/>
     *    </pre>
     *    <br/><br/>
     *  </li>
     *  <li>Submitting with both "values" and "values_by_field" will result in an error.</li>
     *
     *  <li>Note on files:
     *  To attach a file on a file field, the value must contain the ids of the attachements you want to add.
     *    Example:
     *    <pre>
     *    {<br/>
     *      "field_id": 101,<br/>
     *      "value": [41, 42]<br/>
     *    }<br/>
     *    </pre>
     *    <br/>
     *  Note that 41 and 42 ids are provided by /artifact_temporary_files routes.
     *  A user can only add their own temporary files.
     *  To create a temporary file, use POST on /artifact_temporary_files.
     *  </li>
     *  <li>
     *     To link an artifact you can use the "<strong>all_links</strong>" key which contains a json array of artifact id you want to link with the wanted type. </br>
     *      Example: {"id": 11, "direction": "reverse", "type": "my_link_type"} with: </br>
     *      id: the artifact id to be linked</br>
     *      direction: the direction of the link between two artifact, the value must be "<strong>forward</strong>" or "<strong>reverse</strong>" </br>
     *      type: the artifact link type (e.g: _is_child) </br>
     *      /!\ The "<strong>all_links</strong>" key cannot be used at the same time as "<strong>links</strong>" and/or "<strong>parent</strong>" key </br>
     *  </li>
     *  <li>Full Example:
     *  <pre>
     *  {<br/>
     *    "tracker": {"id" : 54},<br/>
     *    "values": [<br/>
     *       &nbsp; {"field_id": 1806, "value" : "my new artifact"},<br/>
     *       &nbsp; {"field_id": 1841, "bind_value_ids" : [254,598,148]},<br/>
     *       &nbsp; { <br/>
     *             &nbsp; "field_id": 543, // field id of the artifact link field<br/>
     *             &nbsp; "all_links": <br/>
     *              &nbsp; &nbsp; [ <br/>
     *                 &nbsp; &nbsp; &nbsp; {"id": 11, "direction": "reverse", "type": "_is_child"}, // artifact #11 will be the parent of the created artifact <br/>
     *                 &nbsp; &nbsp; &nbsp; {"id": 151, "direction": "forward", "type": "_is_child"} // artifact #151 will be the child of the created artifact <br/>
     *             &nbsp; &nbsp; ] <br/>
     *            &nbsp; } <br/>
     *    ]<br/>
     *  }<br/>
     *  </pre>
     *   Notes about the artifact link in the previous payload:</br>
     *  "<strong>Forward</strong>" direction will create a direct link between the created artifact and the artifact #151</br>
     *  "<strong>Reverse</strong>" direction will create a reverse link between the created artifact and the artifact #11</br>
     *  </li>
     * </ol>
     *
     * You can create artifact with <code>values</code>, <code>values_by_field</code>, or <code>from_artifact_id</code>.
     *
     * With <code>from_artifact_id</code>, the source artifact will be copied into the new one, even if they are not in
     * the same tracker. For now it will only copy the semantic title.
     *
     * <pre>
     * /!\ This Copy mechanism is under construction and subject to changes /!\
     * </pre>
     *
     * @url POST
     * @param TrackerReference $tracker Tracker in which the artifact must be created {@from body}
     * @param array $values Artifact fields values {@from body} {@type \Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation}
     * @param array $values_by_field Artifact fields values indexed by field {@from body}
     * @param ArtifactReference $from_artifact Id of the artifact to copy {@from body}
     *
     * @status 201
     * @return ArtifactReference
     *
     * @throws RestException 403
     */
    protected function post(
        TrackerReference $tracker,
        array $values = [],
        array $values_by_field = [],
        ?ArtifactReference $from_artifact = null,
    ) {
        $this->options();

        $this->checkThatThereIsOnlyOneSourceOfValuesToCreateArtifact($values, $values_by_field, $from_artifact);

        $user               = $this->user_manager->getCurrentUser();
        $target_tracker     = $this->getTrackerById($user, $tracker->id);
        $status_verificator = ProjectStatusVerificator::build();
        $status_verificator->checkProjectStatusAllowsAllUsersToAccessIt(
            $target_tracker->getProject()
        );

        try {
            if (! empty($from_artifact)) {
                $source_artifact = $this->getArtifactById($user, $from_artifact->id);

                $status_verificator->checkProjectStatusAllowsAllUsersToAccessIt(
                    $source_artifact->getTracker()->getProject()
                );

                $values = $this->moved_value_builder->getValues($source_artifact, $target_tracker);
                $target_tracker->getWorkflow()->disable();
            }

            $usage_dao            = new ArtifactLinksUsageDao();
            $fields_retriever     = new FieldsToBeSavedInSpecificOrderRetriever($this->formelement_factory);
            $event_dispatcher     = EventManager::instance();
            $transaction_executor = new DBTransactionExecutorWithConnection(
                DBFactory::getMainTuleapDBConnection()
            );

            $changeset_creator = new NewChangesetCreator(
                new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                    $this->formelement_factory,
                    new ArtifactLinkValidator(
                        $this->artifact_factory,
                        new TypePresenterFactory(new TypeDao(), $usage_dao),
                        $usage_dao,
                        $event_dispatcher,
                    ),
                    new WorkflowUpdateChecker(
                        new FrozenFieldDetector(
                            new TransitionRetriever(
                                new StateFactory(\TransitionFactory::instance(), new SimpleWorkflowDao()),
                                new TransitionExtractor()
                            ),
                            FrozenFieldsRetriever::instance(),
                        )
                    )
                ),
                $fields_retriever,
                $event_dispatcher,
                new \Tracker_Artifact_Changeset_ChangesetDataInitializator($this->formelement_factory),
                $transaction_executor,
                ArtifactChangesetSaver::build(),
                new ParentLinkAction($this->artifact_factory),
                new AfterNewChangesetHandler($this->artifact_factory, $fields_retriever),
                ActionsRunner::build(\BackendLogger::getDefaultLogger()),
                new ChangesetValueSaver(),
                \WorkflowFactory::instance(),
                new CommentCreator(
                    new \Tracker_Artifact_Changeset_CommentDao(),
                    \ReferenceManager::instance(),
                    new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao()),
                    new ChangesetCommentIndexer(
                        new ItemToIndexQueueEventBased($event_dispatcher),
                        $event_dispatcher,
                        new \Tracker_Artifact_Changeset_CommentDao(),
                    ),
                    new TextValueValidator(),
                )
            );

            $artifact_link_initial_builder = new NewArtifactLinkInitialChangesetValueBuilder();
            $creator                       = new ArtifactCreator(
                new FieldsDataBuilder(
                    $this->formelement_factory,
                    new NewArtifactLinkChangesetValueBuilder(
                        new ArtifactForwardLinksRetriever(
                            new ArtifactLinksByChangesetCache(),
                            new ChangesetValueArtifactLinkDao(),
                            $this->artifact_factory
                        )
                    ),
                    $artifact_link_initial_builder
                ),
                $this->artifact_factory,
                $this->tracker_factory,
                new FieldsDataFromValuesByFieldBuilder($this->formelement_factory, $artifact_link_initial_builder),
                $this->formelement_factory,
                new ArtifactUpdateHandler($changeset_creator, $this->formelement_factory, $this->artifact_factory),
                SubmissionPermissionVerifier::instance()
            );

            if (! empty($values)) {
                $artifact_reference = $creator->create($user, $tracker, $values, true);
            } elseif (! empty($values_by_field)) {
                $artifact_reference = $creator->createWithValuesIndexedByFieldName($user, $tracker, $values_by_field);
            } else {
                throw new RestException(400, "No valid data are provided");
            }

            $this->sendLastModifiedHeader($artifact_reference->getArtifact());
            $this->sendETagHeader($artifact_reference->getArtifact());
            $this->sendLocationHeader($artifact_reference->uri);
            return $artifact_reference;
        } catch (SemanticTitleNotDefinedException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_RESTValueByField_NotImplementedException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_FileNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
    }

    /**
     * Delete an artifact given its id
     *
     * @url DELETE {id}
     *
     * @throws RestException 401 Unauthorized
     * @throws RestException 403 Forbidden
     * @throws RestException 404 Artifact Not found
     * @throws RestException 429 Too Many Requests (rate limit exceeded)
     *
     * @access hybrid
     * @param int $id Id of the artifact
     */
    public function deleteArtifact($id)
    {
        $this->checkAccess();

        $user                  = $this->user_manager->getCurrentUser();
        $artifact              = $this->getArtifactById($user, $id);
        $is_user_tracker_admin = $artifact->getTracker()->userIsAdmin($user);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $artifact->getTracker()->getProject()
        );

        if (! $is_user_tracker_admin) {
            throw new RestException(403);
        }

        $remaining_deletions = 0;
        $limit               = $this->artifacts_deletion_config->getArtifactsDeletionLimit();

        try {
            $remaining_deletions = $this->artifacts_deletion_manager->deleteArtifact($artifact, $user);
        } catch (DeletionOfArtifactsIsNotAllowedException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (ArtifactsDeletionLimitReachedException $limit_reached_exception) {
            throw new RestException(429, $limit_reached_exception->getMessage());
        } finally {
            Header::sendRateLimitHeaders($limit, $remaining_deletions);
            $this->sendAllowHeadersForArtifact();
        }
    }

    /**
     * Artifact partial update
     *
     * Partial update of an artifact.
     * <br/>
     *
     * <pre>
     * /!\ REST route under construction and subject to changes /!\
     * </pre>
     * <br/>
     *
     * This partial update allows user to move an artifact from one tracker to another.
     * <br/>
     * This route moves an artifact from one tracker to another keeping:
     * <br/>
     * <ul>
     * <li> Artifact ID </li>
     * <li> Submitter user </li>
     * <li> Submitted on date </li>
     * <li> Semantic title</li>
     * <li> Semantic descprition</li>
     * <li> Semantic status</li>
     * <li> Semantic initial effort</li>
     * </ul>
     *
     * <br/>
     * To move an Artifact:
     * <pre>
     * {<br>
     * &nbsp;"move": {<br/>
     * &nbsp;&nbsp;"tracker_id": 1<br/>
     * &nbsp;}<br/>
     * }
     * </pre>
     * <br/>
     * Limitations:
     * <ul>
     * <li>User must be admin of both source and target trackers in order to be able to move an artifact.</li>
     * <li>Artifact must not be linked to a FRS release or be part of a Program.</li>
     * <li>Both trackers must have the title semantic, the description semantic, the status semantic, the contributor semantic and the initial effort semantic aligned
     * (traget tracker must have at least one semantic used in source tracker)
     * </li>
     * </ul>
     * <br/>
     * <br/>
     * Values for list fields (status and initial effort) are retrieved with duck typing:
     * <ul>
     * <li>Values are checked without taking into account the case</li>
     * <li>The first value matching the label is returned</li>
     * <li>If no corresponding value found, the default value is returned</li>
     * </ul>
     * <br/>
     * <br/>
     * Values for list fields bound to users (contributor) are are moved "as much as possible".
     * <br/>
     * If a user is not part of the target group, he is silently ignored..
     * <br/>
     * <br/>
     * A new dry-run mode has been added, it allows user to know which fields will be moved or not without doing the action.
     *  <br/>
     * To move an Artifact in dry-run:
     * <pre>
     * {<br>
     * &nbsp;"move": {<br/>
     * &nbsp;&nbsp;"tracker_id": 1<br/>
     * &nbsp;&nbsp;"dry_run": true<br/>
     * &nbsp;}<br/>
     * }
     * </pre>
     *
     * <br>
     * Note for should_populate_feedback_on_success: this parameter is here to create (if true) a feedback in Tuleap UI
     * in case of a successful move. The feedback will be displayed the next time the user browse Tuleap. If dry_run is
     * true, then should_populate_feedback_on_success has no incidence since no move is really done.
     * By default should_populate_feedback_on_success is false, no feedback will be created.
     *
     * @url PATCH {id}
     *
     * @access protected
     *
     * @param int                         $id    Id of the artifact
     * @param ArtifactPatchRepresentation $patch Tracker in which the artifact must be created {@from body} {@type \Tuleap\Tracker\REST\v1\ArtifactPatchRepresentation}
     *
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 426
     * @throws RestException 404 Artifact Not found
     * @throws RestException 500
     */
    protected function patchArtifact(int $id, ArtifactPatchRepresentation $patch): ArtifactPatchResponseRepresentation
    {
        $this->checkAccess();
        $user = $this->user_manager->getCurrentUser();

        $collector = new DryRunDuckTypingFieldCollector(
            $this->formelement_factory,
            $this->formelement_factory,
            new FieldCanBeEasilyMigratedVerifier(
                $this->formelement_factory,
                $this->formelement_factory,
            ),
            new StaticListFieldVerifier(),
            new AreStaticListFieldsCompatibleVerifier(),
            new CanStaticFieldValuesBeFullyMovedVerifier(
                new FieldValueMatcher(
                    new XMLImportHelper($this->user_manager)
                )
            ),
            new UserListFieldVerifier(),
            new AreUserFieldsCompatibleVerifier(),
            new CanUserFieldValuesBeFullyMovedVerifier($this->user_manager),
            new IsUserGroupListFieldVerifier(),
            new AreUserGroupFieldsCompatibleVerifier(),
            new CanUserGroupValuesBeFullyMovedVerifier(),
            new IsPermissionsOnArtifactFieldVerifier(),
            new AreTherePermissionsToMigrateVerifier(),
            new CanPermissionsBeFullyMovedVerifier(),
            new OpenListFieldVerifier(),
            new OpenListFieldsCompatibilityVerifier(),
        );

        $mega_mover_artifact = $this->getMegaMoverArtifact($user);
        $move_patch_action   = new MovePatchAction(
            $this->tracker_factory,
            new DryRunMover($mega_mover_artifact, new FeedbackFieldCollector(), $collector),
            new RestArtifactMover(
                $mega_mover_artifact,
                $this->post_move_action,
                $this->getMoveDuckTypingAction($user),
                new NoFeedbackFieldCollector(),
                $collector
            ),
            new ArtifactDeletionLimitRetriever($this->artifacts_deletion_config, $this->user_deletion_retriever),
            new BeforeMoveChecker($this->event_manager, ProjectStatusVerificator::build()),
            new HeaderForMoveSender(),
            $this->artifacts_deletion_config,
        );

        $artifact = $this->getArtifactById($user, $id);
        return $move_patch_action->patchMove($patch, $user, $artifact);
    }

    private function getMegaMoverArtifact(PFUser $user): MegaMoverArtifact
    {
        $builder                = new Tracker_XML_Exporter_ArtifactXMLExporterBuilder();
        $children_collector     = new Tracker_XML_Exporter_NullChildrenCollector();
        $file_path_xml_exporter = new Tracker_XML_Exporter_LocalAbsoluteFilePathXMLExporter();

        $user_xml_exporter = new UserXMLExporter(
            $this->user_manager,
            new UserXMLExportedCollection(new XML_RNGValidator(), new XML_SimpleXMLCDATAFactory())
        );

        $xml_import_builder = new Tracker_Artifact_XMLImportBuilder();
        $user_finder        = new XMLImportHelper($this->user_manager);

        $title_semantic_checker       = new MoveTitleSemanticChecker();
        $description_semantic_checker = new MoveDescriptionSemanticChecker($this->formelement_factory);
        $status_semantic_checker      = new MoveStatusSemanticChecker($this->formelement_factory);
        $contributor_semantic_checker = new MoveContributorSemanticChecker($this->formelement_factory);

        $field_value_matcher = new FieldValueMatcher($user_finder);
        return new MegaMoverArtifact(
            new ArtifactsDeletionManager(
                new ArtifactsDeletionDAO(),
                ArtifactDeletorBuilder::buildForcedSynchronousDeletor(),
                new ArtifactDeletionLimitRetriever($this->artifacts_deletion_config, $this->user_deletion_retriever),
            ),
            $builder->build($children_collector, $file_path_xml_exporter, $user, $user_xml_exporter, true),
            new MoveChangesetXMLSemanticUpdater(
                new MoveChangesetXMLUpdater(),
                $title_semantic_checker,
                $description_semantic_checker,
                $status_semantic_checker,
                $contributor_semantic_checker,
                $this->event_manager,
                new BindValueForSemanticUpdater($field_value_matcher),
                $field_value_matcher
            ),
            $xml_import_builder->build(
                $user_finder,
                new NullLogger()
            ),
            new Tracker_Artifact_PriorityManager(
                new Tracker_Artifact_PriorityDao(),
                new Tracker_Artifact_PriorityHistoryDao(),
                $this->user_manager,
                $this->artifact_factory
            ),
            new BeforeMoveArtifact(
                $this->event_manager,
                $title_semantic_checker,
                $description_semantic_checker,
                $status_semantic_checker,
                $contributor_semantic_checker
            ),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
        );
    }

    private function getTrackerById(PFUser $user, $tracker_id)
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if (! $tracker) {
            throw new RestException(404, "Tracker not found");
        }
        if (! $tracker->userCanSubmitArtifact($user)) {
            throw new RestException(403, "User can't write in destination tracker");
        }

        return $tracker;
    }

    /**
     * @param int $id
     *
     * @return Artifact
     * @throws Project_AccessProjectNotFoundException 404
     * @throws Project_AccessException 403
     * @throws RestException 404
     */
    private function getArtifactById(PFUser $user, $id)
    {
        $artifact = $this->artifact_factory->getArtifactById($id);
        if ($artifact) {
            if (! $artifact->userCanView($user)) {
                throw new RestException(403);
            }

            ProjectAuthorization::userCanAccessProject($user, $artifact->getTracker()->getProject(), new Tracker_URLVerification());
            return $artifact;
        }
        throw new RestException(404);
    }

    private function sendAllowHeadersForChangesets()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForLinkTypes()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForLinkedArtifacts()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForArtifact()
    {
        Header::allowOptionsGetPutDeletePatch();
    }

    private function sendLastModifiedHeader(Artifact $artifact)
    {
        Header::lastModified($artifact->getLastUpdateDate());
    }

    private function sendETagHeader(Artifact $artifact)
    {
        Header::eTag($artifact->getVersionIdentifier());
    }

    private function sendLocationHeader($uri)
    {
        $uri_with_api_version = '/api/v1/' . $uri;

        Header::Location($uri_with_api_version);
    }

    /**
     * @param array $values
     * @param array $values_by_field
     * @param $from_artifact
     * @throws RestException
     */
    private function checkThatThereIsOnlyOneSourceOfValuesToCreateArtifact(
        array $values,
        array $values_by_field,
        $from_artifact,
    ) {
        $nb_sources_to_create_artifact = 0;
        if (! empty($values)) {
            $nb_sources_to_create_artifact++;
        }
        if (! empty($values_by_field)) {
            $nb_sources_to_create_artifact++;
        }
        if (! empty($from_artifact)) {
            $nb_sources_to_create_artifact++;
        }
        if ($nb_sources_to_create_artifact > 1) {
            throw new RestException(400, 'Not able to deal with both formats at the same time');
        }
    }

    private function getMoveDuckTypingAction(PFUser $user): MegaMoverArtifactByDuckTyping
    {
        $builder                = new Tracker_XML_Exporter_ArtifactXMLExporterBuilder();
        $children_collector     = new Tracker_XML_Exporter_NullChildrenCollector();
        $file_path_xml_exporter = new Tracker_XML_Exporter_LocalAbsoluteFilePathXMLExporter();

        $user_xml_exporter = new UserXMLExporter(
            $this->user_manager,
            new UserXMLExportedCollection(new XML_RNGValidator(), new XML_SimpleXMLCDATAFactory())
        );

        $xml_import_builder = new Tracker_Artifact_XMLImportBuilder();
        $user_finder        = new XMLImportHelper($this->user_manager);
        $XML_updater        = new MoveChangesetXMLUpdater();
        $cdata_factory      = new XML_SimpleXMLCDATAFactory();

        return new MegaMoverArtifactByDuckTyping(
            new ArtifactsDeletionManager(
                new ArtifactsDeletionDAO(),
                ArtifactDeletorBuilder::buildForcedSynchronousDeletor(),
                new ArtifactDeletionLimitRetriever($this->artifacts_deletion_config, $this->user_deletion_retriever),
            ),
            $builder->build($children_collector, $file_path_xml_exporter, $user, $user_xml_exporter, true),
            new MoveChangesetXMLDuckTypingUpdater(
                $XML_updater,
                new BindValueForDuckTypingUpdater(
                    new FieldValueMatcher($user_finder),
                    $XML_updater,
                    $cdata_factory
                ),
                new PermissionsByDuckTypingUpdater(
                    new PermissionDuckTypingMatcher(),
                    $XML_updater
                ),
                new OpenListUserGroupsByDuckTypingUpdater(
                    new BindUgroupsValueDao(),
                    new BindUgroupsValueDao(),
                    new UGroupManager(),
                    new UGroupManager(),
                    $XML_updater,
                    $cdata_factory
                ),
                new OpenListFieldVerifier(),
                new UserGroupOpenListFieldVerifier(),
                new IsPermissionsOnArtifactFieldVerifier()
            ),
            new Tracker_Artifact_PriorityManager(
                new Tracker_Artifact_PriorityDao(),
                new Tracker_Artifact_PriorityHistoryDao(),
                $this->user_manager,
                $this->artifact_factory
            ),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            $xml_import_builder->build(
                $user_finder,
                new NullLogger()
            ),
        );
    }
}
