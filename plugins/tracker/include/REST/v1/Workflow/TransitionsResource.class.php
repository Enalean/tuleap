<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Workflow;

use EventManager;
use Luracast\Restler\RestException;
use Psr\Log\LoggerInterface;
use Tracker_RuleFactory;
use TrackerFactory;
use Transition_PostAction_CIBuildDao;
use Transition_PostAction_Field_DateDao;
use Transition_PostAction_Field_FloatDao;
use Transition_PostAction_Field_IntDao;
use TransitionFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\RESTLogger;
use Tuleap\Tracker\REST\v1\Event\GetExternalPostActionJsonParserEvent;
use Tuleap\Tracker\REST\v1\TrackerPermissionsChecker;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\PostActionNonEligibleForTrackerException;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\PostActionsRepresentationBuilder;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\PUTHandler;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\TrackerChecker;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\CIBuildJsonParser;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\FrozenFieldsJsonParser;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\HiddenFieldsetsJsonParser;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\PostActionCollectionJsonParser;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\SetDateValueJsonParser;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\SetFloatValueJsonParser;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\SetIntValueJsonParser;
use Tuleap\Tracker\REST\WorkflowTransitionPOSTRepresentation;
use Tuleap\Tracker\Workflow\Event\GetWorkflowExternalPostActionsValueUpdater;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildValueRepository;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildValueUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\FrozenFieldsValueRepository;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\FrozenFieldsValueUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\FrozenFieldsValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\HiddenFieldsetsValueRepository;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\HiddenFieldsetsValueUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\HiddenFieldsetsValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\IncompatibleWorkflowModeException;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionFieldIdValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetDateValueRepository;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetDateValueUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetDateValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetFloatValueRepository;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetFloatValueUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetFloatValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetIntValueRepository;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetIntValueUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetIntValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollectionUpdater;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionCreator;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionUpdater;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicatorBuilder;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;
use Tuleap\Tracker\Workflow\Transition\OrphanTransitionException;
use UserManager;
use WorkflowFactory;

class TransitionsResource extends AuthenticatedResource
{
    /** @var UserManager */
    private $user_manager;

    public function __construct()
    {
        $this->user_manager = UserManager::instance();
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        $this->sendAllowHeaderForTransition();
    }

    /**
     * Add a new transition for a tracker workflow
     *
     * <br>Params tracker id, source id and destination id are required.
     * <br>Use 0 as source id for transitions from new artifact. (new artifact as destination does not exist)
     *
     * @url    POST
     * @status 201
     *
     * @access protected
     *
     * @param int $tracker_id Id of the tracker
     * @param int $from_id Transition source as a field value id
     * @param int $to_id Transition destination as a field value id
     *
     * @return WorkflowTransitionPOSTRepresentation {@type WorkflowTransitionPOSTRepresentation}
     *
     * @throws I18NRestException 400
     * @throws I18NRestException 404
     * @throws \Luracast\Restler\RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_LoginException
     */
    protected function postTransition($tracker_id, $from_id, $to_id)
    {
        $this->checkAccess();
        $this->sendAllowHeaderForTransition();

        $handler = new TransitionPOSTHandler(
            $this->user_manager,
            $this->getTrackerFactory(),
            ProjectStatusVerificator::build(),
            $this->getPermissionsChecker(),
            WorkflowFactory::instance(),
            $this->getTransitionFactory(),
            new TransitionValidator(),
            $this->getTransactionExecutor(),
            $this->getStateFactory(),
            new TransitionCreator(
                $this->getTransitionFactory(),
                $this->getTransitionReplicator(),
                new TransitionExtractor()
            )
        );

        return $handler->handle($tracker_id, $from_id, $to_id);
    }

    /**
     * Delete a transition from a workflow
     *
     * @url    DELETE {id}
     * @status 200
     *
     * @access protected
     *
     * @param  int $id Transition id
     *
     * @throws I18NRestException 400
     * @throws RestException 401
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     * @throws \Tuleap\Tracker\Workflow\TransitionDeletionException
     * @throws OrphanTransitionException
     */
    public function deleteTransition($id)
    {
        $this->checkAccess();
        $this->sendAllowHeaderForTransition();

        $transition = $this->getTransitionFactory()->getTransition($id);
        if (!$transition) {
            throw new I18NRestException(404, dgettext('tuleap-tracker', 'Transition not found.'));
        }

        $current_user = $this->user_manager->getCurrentUser();
        $this->getPermissionsChecker()->checkDelete($current_user, $transition);

        $project = $transition->getWorkflow()->getTracker()->getProject();
        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);
        $this->getTransitionFactory()->delete($transition);
    }

    /**
     * Patch a transition from a workflow
     *
     * <br />Parameter "is_comment_required" is not taken into account for transition from (New Artifact).
     *
     * @url    PATCH {id}
     *
     * @status 200
     *
     * @access protected
     *
     * @param int $id Transition id
     * @param WorkflowTransitionPATCHRepresentation $transition_conditions The new transition representation
     *
     * @throws I18NRestException 400
     * @throws I18NRestException 404
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     * @throws OrphanTransitionException
     * @throws \User_LoginException
     * @throws \Rest_Exception_InvalidTokenException
     */
    public function patchTransition($id, $transition_conditions)
    {
        $this->checkAccess();
        $this->sendAllowHeaderForTransition();

        $transition = $this->getTransitionFactory()->getTransition($id);
        if (!$transition) {
            throw new I18NRestException(404, dgettext('tuleap-tracker', 'Transition not found.'));
        }

        $current_user = $this->user_manager->getCurrentUser();
        $this->getPermissionsChecker()->checkUpdate($current_user, $transition);

        $project = $transition->getWorkflow()->getTracker()->getProject();
        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        $handler = new TransitionPatcher(
            $this->getConditionsUpdater(),
            $this->getTransactionExecutor(),
            $this->getStateFactory(),
            $this->getTransitionUpdater()
        );

        try {
            $handler->patch($transition, $transition_conditions);
        } catch (ConditionsUpdateException $exception) {
            throw new I18NRestException(400, dgettext('tuleap-tracker', 'The transition has not been updated.'));
        }
    }

    /**
     * Get a transition
     *
     * REST route to get a transition
     *
     * @url GET {id}
     * @status 200
     * @oauth2-scope read:tracker
     *
     * @access protected
     *
     * @param int $id Id of transition
     *
     * @return TransitionRepresentation
     *
     * @throws I18NRestException 401 I18NRestException
     * @throws I18NRestException 403 I18NRestException
     * @throws I18NRestException 404 I18NRestException
     * @throws \Luracast\Restler\RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     * @throws OrphanTransitionException
     */
    protected function getTransition($id)
    {
        $this->checkAccess();
        $this->sendAllowHeaderForTransition();

        $transition = $this->getTransitionFactory()->getTransition($id);
        if ($transition === null) {
            throw new I18NRestException(404, dgettext('tuleap-tracker', 'Transition not found.'));
        }

        $current_user = $this->user_manager->getCurrentUser();
        $this->getPermissionsChecker()->checkRead($current_user, $transition);
        $project = $transition->getWorkflow()->getTracker()->getProject();
        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt($current_user, $project);

        return (new TransitionRepresentationBuilder($transition))->build();
    }

    /**
     * Get all post actions of a transition
     *
     * REST route to get all post actions of a transition
     *
     * @url GET {id}/actions
     * @status 200
     * @oauth2-scope read:tracker
     *
     * @access protected
     *
     * @param int $id Id of transition
     *
     * @return array
     *
     * @throws I18NRestException 401
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     * @throws OrphanTransitionException
     * @throws \Luracast\Restler\RestException
     */
    protected function getPostActions($id)
    {
        $this->checkAccess();
        $this->sendAllowHeaderForActions();

        $transition = $this->getTransitionFactory()->getTransition($id);
        if ($transition === null) {
            throw new I18NRestException(404, dgettext('tuleap-tracker', 'Transition not found.'));
        }

        $current_user = $this->user_manager->getCurrentUser();
        $this->getPermissionsChecker()->checkRead($current_user, $transition);
        try {
            $project = $transition->getWorkflow()->getTracker()->getProject();
        } catch (OrphanTransitionException $exception) {
            $this->getRESTLogger()->error('Cannot return transition post actions', ['exception' => $exception]);
            throw new RestException(520);
        }
        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt($current_user, $project);

        $post_actions_representation_builder = new PostActionsRepresentationBuilder(
            EventManager::instance(),
            $transition->getAllPostActions()
        );

        return $post_actions_representation_builder->build();
    }

    /**
     * Update all post actions of a transition.
     *
     * <ul>
     * <li>Actions without id will be created</li>
     * <li>Actions with same id will be updated</li>
     * <li>Other actions will be removed</li>
     * </ul>
     *
     * Body sample :
     * <pre>
     * { <br/>
     * &nbsp; "post_actions": [ <br/>
     * &nbsp; &nbsp; { <br/>
     * &nbsp; &nbsp; &nbsp; "id": null, <br/>
     * &nbsp; &nbsp; &nbsp; "type": "run_job", <br/>
     * &nbsp; &nbsp; &nbsp; "job_url": "http://example.com" <br/>
     * &nbsp; &nbsp; }, <br/>
     * &nbsp; &nbsp; { <br/>
     * &nbsp; &nbsp; &nbsp; "id": 1, <br/>
     * &nbsp; &nbsp; &nbsp; "type": "set_field_value", <br/>
     * &nbsp; &nbsp; &nbsp; "field_type": "date", <br/>
     * &nbsp; &nbsp; &nbsp; "field_id": 43, <br/>
     * &nbsp; &nbsp; &nbsp; "value": "current" <br/>
     * &nbsp; &nbsp; }, <br/>
     * &nbsp; &nbsp; { <br/>
     * &nbsp; &nbsp; &nbsp; "id": 2, <br/>
     * &nbsp; &nbsp; &nbsp; "type": "set_field_value", <br/>
     * &nbsp; &nbsp; &nbsp; "field_type": "int", <br/>
     * &nbsp; &nbsp; &nbsp; "field_id": 44, <br/>
     * &nbsp; &nbsp; &nbsp; "value": 3 <br/>
     * &nbsp; &nbsp; }, <br/>
     * &nbsp; &nbsp; { <br/>
     * &nbsp; &nbsp; &nbsp; "id": 2, <br/>
     * &nbsp; &nbsp; &nbsp; "type": "set_field_value", <br/>
     * &nbsp; &nbsp; &nbsp; "field_type": "float", <br/>
     * &nbsp; &nbsp; &nbsp; "field_id": 45, <br/>
     * &nbsp; &nbsp; &nbsp; "value": 1.23 <br/>
     * &nbsp; &nbsp; } <br/>
     * &nbsp; ] <br/>
     * } <br/>
     * </pre>
     *
     * @url PUT {id}/actions
     * @status 200
     *
     * @access protected
     *
     * @param int $id Id of transition
     * @param PostActionsPUTRepresentation $post_actions_representation actions {@from body}
     *
     * @throws I18NRestException 401
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     * @throws OrphanTransitionException
     * @throws \DataAccessQueryException
     * @throws RestException
     */
    protected function putPostActions(int $id, PostActionsPUTRepresentation $post_actions_representation)
    {
        $this->checkAccess();
        $this->sendAllowHeaderForActions();

        $transition = $this->getTransitionFactory()->getTransition($id);
        if ($transition === null) {
            throw new I18NRestException(
                404,
                dgettext('tuleap-tracker', 'Transition not found.')
            );
        }

        try {
            $current_user = $this->user_manager->getCurrentUser();

            $handler = new PUTHandler(
                $this->getPermissionsChecker(),
                ProjectStatusVerificator::build(),
                $this->getPostActionCollectionJsonParser(),
                $this->getPostActionCollectionUpdater(),
                new TrackerChecker(EventManager::instance()),
                $this->getTransactionExecutor(),
                $this->getStateFactory(),
                $this->getTransitionUpdater()
            );

            $handler->handle($current_user, $transition, $post_actions_representation);
        } catch (OrphanTransitionException $exception) {
            $this->getRESTLogger()->error('Cannot update transition post actions', ['exception' => $exception]);
            throw new RestException(520);
        } catch (
            InvalidPostActionException |
                 UnknownPostActionIdsException |
                 IncompatibleWorkflowModeException |
                 PostActionNonEligibleForTrackerException $exception
        ) {
            throw new I18NRestException(400, $exception->getMessage());
        }
    }

    private function sendAllowHeaderForTransition()
    {
        Header::allowOptionsGetPostPatchDelete();
    }

    private function sendAllowHeaderForActions()
    {
        Header::allowOptionsGetPut();
    }

    /**
     * @return TransitionFactory
     */
    private function getTransitionFactory()
    {
        return TransitionFactory::instance();
    }

    /**
     * @return TrackerFactory
     */
    private function getTrackerFactory()
    {
        return TrackerFactory::instance();
    }

    /**
     * @return TransitionsPermissionsChecker
     */
    private function getPermissionsChecker()
    {
        return new TransitionsPermissionsChecker(new TrackerPermissionsChecker(new \URLVerification()));
    }

    private function getRESTLogger(): LoggerInterface
    {
        return RESTLogger::getLogger();
    }

    private function getPostActionCollectionJsonParser(): PostActionCollectionJsonParser
    {
        $parsers = [
            new CIBuildJsonParser(),
            new SetDateValueJsonParser(),
            new SetIntValueJsonParser(),
            new SetFloatValueJsonParser(),
            new FrozenFieldsJsonParser(),
            new HiddenFieldsetsJsonParser(),
        ];

        $event = new GetExternalPostActionJsonParserEvent();
        EventManager::instance()->processEvent($event);

        $parsers = array_merge(
            $parsers,
            $event->getParsers()
        );

        return new PostActionCollectionJsonParser(...$parsers);
    }

    private function getTransactionExecutor(): DBTransactionExecutor
    {
        return new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
    }

    private function getPostActionCollectionUpdater(): PostActionCollectionUpdater
    {
        $field_ids_validator  = new PostActionFieldIdValidator();
        $form_element_factory = \Tracker_FormElementFactory::instance();
        $transaction_executor = $this->getTransactionExecutor();

        $event = new GetWorkflowExternalPostActionsValueUpdater();
        EventManager::instance()->processEvent($event);

        $value_updaters = [
            new CIBuildValueUpdater(
                new CIBuildValueRepository(
                    $this->getCIBuildDao()
                ),
                new CIBuildValueValidator()
            ),
            new SetDateValueUpdater(
                new SetDateValueRepository(
                    $this->getFieldDateDao(),
                    $transaction_executor
                ),
                new SetDateValueValidator($field_ids_validator, $form_element_factory)
            ),
            new SetIntValueUpdater(
                new SetIntValueRepository(
                    $this->getFieldIntDao(),
                    $transaction_executor
                ),
                new SetIntValueValidator($field_ids_validator, $form_element_factory)
            ),
            new SetFloatValueUpdater(
                new SetFloatValueRepository(
                    $this->getFieldFloatDao(),
                    $transaction_executor
                ),
                new SetFloatValueValidator($field_ids_validator, $form_element_factory)
            ),
            new FrozenFieldsValueUpdater(
                new FrozenFieldsValueRepository(
                    $this->getFrozenFieldsDao()
                ),
                new FrozenFieldsValueValidator($form_element_factory, Tracker_RuleFactory::instance())
            ),
            new HiddenFieldsetsValueUpdater(
                new HiddenFieldsetsValueRepository(
                    new HiddenFieldsetsDao()
                ),
                new HiddenFieldsetsValueValidator(
                    $form_element_factory
                )
            )
        ];

        $value_updaters = array_merge(
            $value_updaters,
            $event->getValueUpdaters()
        );

        return new PostActionCollectionUpdater(...$value_updaters);
    }

    private function getCIBuildDao(): Transition_PostAction_CIBuildDao
    {
        return new Transition_PostAction_CIBuildDao();
    }

    private function getFieldDateDao(): Transition_PostAction_Field_DateDao
    {
        return new Transition_PostAction_Field_DateDao();
    }

    private function getFieldIntDao(): Transition_PostAction_Field_IntDao
    {
        return new Transition_PostAction_Field_IntDao();
    }

    private function getFieldFloatDao(): Transition_PostAction_Field_FloatDao
    {
        return new Transition_PostAction_Field_FloatDao();
    }

    private function getFrozenFieldsDao(): FrozenFieldsDao
    {
        return new FrozenFieldsDao();
    }

    private function getConditionsUpdater(): ConditionsUpdater
    {
        return new ConditionsUpdater(
            $this->getTransitionFactory(),
            $this->getConditionFactory()
        );
    }

    private function getConditionFactory(): \Workflow_Transition_ConditionFactory
    {
        return \Workflow_Transition_ConditionFactory::build();
    }

    private function getTransitionReplicator(): TransitionReplicator
    {
        return TransitionReplicatorBuilder::build();
    }

    private function getStateFactory(): StateFactory
    {
        return new StateFactory(
            $this->getTransitionFactory(),
            new SimpleWorkflowDao()
        );
    }

    private function getTransitionUpdater(): TransitionUpdater
    {
        return new TransitionUpdater(
            $this->getConditionsUpdater(),
            $this->getPostActionCollectionUpdater()
        );
    }
}
