<?php
/**
 * Copyright (c) Enalean, 2018 - 2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow;

use Luracast\Restler\RestException;
use TrackerFactory;
use Transition_PostAction_CIBuildDao;
use Transition_PostAction_Field_DateDao;
use Transition_PostAction_Field_FloatDao;
use Transition_PostAction_Field_IntDao;
use TransitionFactory;
use Tuleap\DB\DataAccessObject;
use Tuleap\DB\TransactionExecutor;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\RESTLogger;
use Tuleap\REST\UserManager;
use Tuleap\Tracker\REST\v1\TrackerPermissionsChecker;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\PostActionsRepresentationBuilder;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\PUTHandler;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\CIBuildJsonParser;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\PostActionCollectionJsonParser;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\SetDateValueJsonParser;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\SetFloatValueJsonParser;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\SetIntValueJsonParser;
use Tuleap\Tracker\REST\WorkflowTransitionPOSTRepresentation;
use Tuleap\Tracker\Workflow\FeatureFlag;
use Tuleap\Tracker\Workflow\PostAction\PostActionsRetriever;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildRepository;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionFieldIdValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionIdValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionsMapper;
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
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;
use Tuleap\Tracker\Workflow\Transition\OrphanTransitionException;
use Tuleap\Tracker\Workflow\Transition\Update\TransitionReplicator;
use Tuleap\Tracker\Workflow\Transition\Update\TransitionRetriever;
use WorkflowFactory;

class TransitionsResource extends AuthenticatedResource
{
    /** @var UserManager */
    private $user_manager;

    public function __construct()
    {
        $this->user_manager = UserManager::build();
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
     * @param int $from_id    Transition source as a field value id
     * @param int $to_id      Transition destination as a field value id
     *
     * @return WorkflowTransitionPOSTRepresentation {@type WorkflowTransitionPOSTRepresentation}
     *
     * @throws 400 I18NRestException
     * @throws 404 I18NRestException
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
            new TransitionReplicator(
                $this->getConditionFactory(),
                $this->getConditionsUpdater(),
                new PostActionsRetriever(
                    new \Transition_PostAction_CIBuildFactory($this->getCIBuildDao()),
                    new \Transition_PostAction_FieldFactory(
                        \Tracker_FormElementFactory::instance(),
                        $this->getFieldDateDao(),
                        $this->getFieldIntDao(),
                        $this->getFieldFloatDao()
                    )
                ),
                $this->getPostActionCollectionUpdater(),
                new PostActionsMapper()
            ),
            $this->getTransitionRetriever()
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
     * @throws 400 I18NRestException
     * @throws 401 RestException
     * @throws 403 I18NRestException
     * @throws 404 I18NRestException
     * @throws \Luracast\Restler\RestException
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
     * @param int                                   $id                    Transition id
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
            $this->getTransitionRetriever(),
            $this->getTransactionExecutor()
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
     *
     * @access protected
     *
     * @param int $id Id of transition
     *
     * @return TransitionRepresentation
     *
     * @throws 401 I18NRestException
     * @throws 403 I18NRestException
     * @throws 404 I18NRestException
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
     *
     * @access protected
     *
     * @param int $id Id of transition
     *
     * @return array
     *
     * @throws 401 I18NRestException
     * @throws 403 I18NRestException
     * @throws 404 I18NRestException
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
            $this->getRESTLogger()->error('Cannot return transition post actions', $exception);
            throw new RestException(520);
        }
        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt($current_user, $project);

        return (new PostActionsRepresentationBuilder($transition->getAllPostActions()))->build();
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
     * @throws 401 I18NRestException
     * @throws 403 I18NRestException
     * @throws 404 I18NRestException
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
                $this->getTransitionRetriever()
            );

            $handler->handle($current_user, $transition, $post_actions_representation);
        } catch (OrphanTransitionException $exception) {
            $this->getRESTLogger()->error('Cannot update transition post actions', $exception);
            throw new RestException(520);
        } catch (InvalidPostActionException | UnknownPostActionIdsException $e) {
            throw new I18NRestException(400, $e->getMessage());
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

    /**
     * @return RESTLogger
     */
    private function getRESTLogger()
    {
        return new RESTLogger();
    }

    private function getPostActionCollectionJsonParser(): PostActionCollectionJsonParser
    {
        return new PostActionCollectionJsonParser(
            new CIBuildJsonParser(),
            new SetDateValueJsonParser(),
            new SetIntValueJsonParser(),
            new SetFloatValueJsonParser()
        );
    }

    private function getTransactionExecutor(): TransactionExecutor
    {
        return new TransactionExecutor(new DataAccessObject());
    }

    private function getPostActionCollectionUpdater(): PostActionCollectionUpdater
    {
        $ids_validator        = new PostActionIdValidator();
        $field_ids_validator  = new PostActionFieldIdValidator();
        $form_element_factory = \Tracker_FormElementFactory::instance();

        return new PostActionCollectionUpdater(
            $this->getTransactionExecutor(),
            new CIBuildUpdater(
                new CIBuildRepository(
                    $this->getCIBuildDao()
                ),
                new CIBuildValidator($ids_validator)
            ),
            new SetDateValueUpdater(
                new SetDateValueRepository(
                    $this->getFieldDateDao(),
                    new DataAccessObject()
                ),
                new SetDateValueValidator($ids_validator, $field_ids_validator, $form_element_factory)
            ),
            new SetIntValueUpdater(
                new SetintValueRepository(
                    $this->getFieldIntDao(),
                    new DataAccessObject()
                ),
                new SetIntValueValidator($ids_validator, $field_ids_validator, $form_element_factory)
            ),
            new SetFloatValueUpdater(
                new SetFloatValueRepository(
                    $this->getFieldFloatDao(),
                    new DataAccessObject()
                ),
                new SetFloatValueValidator($ids_validator, $field_ids_validator, $form_element_factory)
            )
        );
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

    private function getConditionsUpdater(): ConditionsUpdater
    {
        return new ConditionsUpdater(
            $this->getTransitionFactory(),
            $this->getConditionFactory(),
            $this->getTransactionExecutor()
        );
    }

    private function getConditionFactory(): \Workflow_Transition_ConditionFactory
    {
        return \Workflow_Transition_ConditionFactory::build();
    }

    private function getTransitionRetriever(): TransitionRetriever
    {
        return new TransitionRetriever(new \Workflow_TransitionDao(), $this->getTransitionFactory());
    }
}
