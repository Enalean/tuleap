<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Codendi_HTMLPurifier;
use Codendi_Request;
use DateTimeImmutable;
use EventManager;
use Feedback;
use PFUser;
use Tracker_Artifact_ReadOnlyRenderer;
use Tracker_Artifact_Redirect;
use Tracker_Exception;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElementFactory;
use Tracker_IDisplayTrackerLayout;
use Tuleap\Dashboard\Project\ProjectDashboard;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Layout\BaseLayout;
use Tuleap\NeverThrow\Fault;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistFault;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\NoChangeFault;
use Tuleap\Tracker\Artifact\ChangesetValue\BuildChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Link\ArtifactReverseLinksUpdater;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Artifact\Renderer\ArtifactViewCollectionBuilder;
use Tuleap\Tracker\FormElement\ArtifactLinkFieldDoesNotExistFault;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\Permission\ArtifactPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnArtifacts;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;

final readonly class UpdateArtifactAction
{
    public function __construct(
        private Artifact $artifact,
        private Tracker_FormElementFactory $form_element_factory,
        private EventManager $event_manager,
        private TypeIsChildLinkRetriever $artifact_retriever,
        private VisitRecorder $visit_recorder,
        private HiddenFieldsetsDetector $hidden_fieldsets_detector,
        private ArtifactReverseLinksUpdater $artifact_updater,
        private RetrieveUserPermissionOnArtifacts $user_permission_on_artifacts,
        private BuildChangesetValuesContainer $changeset_values_container_builder,
        private ProjectDashboardRetriever $project_dashboard_retriever,
        private ProjectByIDFactory $project_by_id_factory,
    ) {
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user): void
    {
        $base_layout = $GLOBALS['Response'];
        assert($base_layout instanceof BaseLayout);

        if (
            $this->user_permission_on_artifacts
                ->retrieveUserPermissionOnArtifacts($current_user, [$this->artifact], ArtifactPermissionType::PERMISSION_UPDATE)
                ->allowed === []
        ) {
            $base_layout->addFeedback(Feedback::ERROR, dgettext('tuleap-tracker', 'You are not allowed to update this artifact'));
            $base_layout->redirect($this->artifact->getUri());
        }

        $fields_data = $request->get('artifact');
        if ($fields_data === false) {
            $fields_data = [];
        }
        $fields_data['request_method_called'] = 'artifact-update';
        $this->artifact->getTracker()->augmentDataFromRequest($fields_data);
        unset($fields_data['request_method_called']);

        try {
            if ($current_user->isAnonymous()) {
                $request_email = $request->get('email');
                $email         = ($request_email !== false) ? $request_email : null;
                $current_user->setEmail($email);
            }

            $comment_body    = $request->get('artifact_followup_comment') !== false
                ? (string) $request->get('artifact_followup_comment')
                : '';
            $comment_format  = $this->artifact->validateCommentFormat($request, 'comment_formatnew');
            $submission_date = new DateTimeImmutable();
            $redirect        = $this->getRedirectUrlAfterArtifactUpdate($request);
            $this->artifact_updater->updateArtifactAndItsLinks(
                $this->artifact,
                $this->changeset_values_container_builder->buildChangesetValuesContainer(
                    $fields_data,
                    $this->artifact->getTracker(),
                    $this->artifact,
                    $current_user,
                ),
                $current_user,
                $submission_date,
                NewComment::fromParts(
                    $comment_body,
                    $comment_format,
                    $current_user,
                    $submission_date->getTimestamp(),
                    [],
                ),
            )->mapErr(
                fn(Fault $fault) => $this->handleFault($fault, $base_layout, $layout, $redirect, $request, $current_user)
            );

            $art_link = $this->artifact->fetchDirectLinkToArtifact();
            $base_layout->addFeedback(Feedback::INFO, sprintf(dgettext('tuleap-tracker', 'Successfully Updated (%1$s)'), $art_link), CODENDI_PURIFIER_LIGHT);

            $this->artifact->summonArtifactRedirectors($request, $redirect);

            if ($request->isAjax()) {
                $this->sendAjaxCardsUpdateInfo($current_user);
            } elseif ($request->existAndNonEmpty('from_overlay')) {
                $purifier  = Codendi_HTMLPurifier::instance();
                $csp_nonce = $base_layout->getCSPNonce();
                echo sprintf('<script type="text/javascript" nonce="%s">window.parent.tuleap.cardwall.cardsEditInPlace.validateEdition(%d);</script>', $purifier->purify($csp_nonce), $this->artifact->getId());
                return;
            } else {
                $base_layout->redirect($redirect->toUrl());
            }
        } catch (Tracker_Exception $e) {
            if ($request->isAjax()) {
                $this->sendAjaxCardsUpdateInfo($current_user);
            } else {
                $base_layout->addFeedback(Feedback::ERROR, $e->getMessage());
                $render = new Tracker_Artifact_ReadOnlyRenderer(
                    $this->event_manager,
                    $this->artifact,
                    $layout,
                    $this->artifact_retriever,
                    $this->visit_recorder,
                    $this->hidden_fieldsets_detector,
                    new ArtifactViewCollectionBuilder($this->event_manager)
                );
                $render->display($request, $current_user);
            }
        }
    }

    /**
     * @psalm-return never-return
     */
    private function handleFault(
        Fault $fault,
        BaseLayout $base_layout,
        Tracker_IDisplayTrackerLayout $layout,
        Tracker_Artifact_Redirect $redirect,
        Codendi_Request $request,
        PFUser $current_user,
    ): void {
        if ($fault instanceof NoChangeFault) {
            if ($request->isAjax()) {
                $this->sendAjaxCardsUpdateInfo($current_user);
                exit();
            }
            $base_layout->addFeedback(Feedback::INFO, (string) $fault, CODENDI_PURIFIER_LIGHT);
            $render = new Tracker_Artifact_ReadOnlyRenderer(
                $this->event_manager,
                $this->artifact,
                $layout,
                $this->artifact_retriever,
                $this->visit_recorder,
                $this->hidden_fieldsets_detector,
                new ArtifactViewCollectionBuilder($this->event_manager)
            );
            $render->display($request, $current_user);
            exit();
        }
        $error_message = match ($fault::class) {
            ArtifactDoesNotExistFault::class => sprintf(
                dgettext('tuleap-tracker', 'You tried to create a link to Artifact #%s, but it could not be found.'),
                $fault->artifact_id
            ),
            ArtifactLinkFieldDoesNotExistFault::class => sprintf(
                dgettext(
                    'tuleap-tracker',
                    'You tried to create a link to Artifact #%s, but there is no artifact links field in its tracker.'
                ),
                $fault->artifact_id
            ),
            default => (string) $fault,
        };
        $base_layout->addFeedback(Feedback::ERROR, $error_message);
        $base_layout->redirect($redirect->toUrl());
    }

    public function getRedirectUrlAfterArtifactUpdate(Codendi_Request $request): Tracker_Artifact_Redirect
    {
        $stay                 = $request->get('submit_and_stay');
        $from_aid             = $request->get('from_aid');
        $my_dashboard_id      = $request->get('my-dashboard-id');
        $project_dashboard_id = $request->get('project-dashboard-id');

        $redirect                   = new Tracker_Artifact_Redirect();
        $redirect->mode             = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $redirect->base_url         = TRACKER_BASE_URL;
        $redirect->query_parameters = $this->calculateRedirectParams($stay, $from_aid, $my_dashboard_id, $project_dashboard_id);
        if ($stay) {
            $redirect->mode = Tracker_Artifact_Redirect::STATE_STAY;
        } elseif ($my_dashboard_id !== false) {
            $redirect->base_url = '/my/';
            $redirect->mode     = Tracker_Artifact_Redirect::TO_MY_DASHBOARD;
        } elseif ($project_dashboard_id !== false) {
            $this->project_dashboard_retriever
                ->getProjectDashboardById((int) $project_dashboard_id)
                ->match(
                    fn (ProjectDashboard $dashboard) => $this->redirectToProjectDashboard($dashboard, $redirect),
                    fn () => $this->fallbackRedirectOnTracker($redirect)
                );
        }
        return $redirect;
    }

    private function calculateRedirectParams($stay, $from_aid, string|bool $my_dashboard_id, string|bool $project_dashboard_id): array
    {
        $redirect_params = [];
        if ($stay) {
            $redirect_params['aid']      = $this->artifact->getId();
            $redirect_params['from_aid'] = $from_aid;
        } elseif ($from_aid) {
            $redirect_params['aid'] = $from_aid;
        } else {
            $redirect_params['tracker'] = $this->artifact->tracker_id;
        }
        if ($my_dashboard_id !== false) {
            $redirect_params['dashboard_id'] = $my_dashboard_id;
        }
        if ($project_dashboard_id !== false) {
            $redirect_params['dashboard_id'] = $project_dashboard_id;
        }
        return array_filter($redirect_params);
    }

    private function redirectToProjectDashboard(
        ProjectDashboard $dashboard,
        Tracker_Artifact_Redirect $redirect,
    ): void {
        $project            = $this->project_by_id_factory->getProjectById($dashboard->getProjectId());
        $redirect->base_url = '/projects/' . urlencode($project->getUnixName()) . '/';
        $redirect->mode     = Tracker_Artifact_Redirect::TO_PROJECT_DASHBOARD;
    }

    private function fallbackRedirectOnTracker(Tracker_Artifact_Redirect $redirect): void
    {
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
    }

    private function sendAjaxCardsUpdateInfo(PFUser $current_user): void
    {
        $cards_info = $this->getCardUpdateInfo($this->artifact, $current_user);
        $parent     = $this->artifact->getParent($current_user);
        if ($parent) {
            $cards_info = $cards_info + $this->getCardUpdateInfo($parent, $current_user);
        }

        $GLOBALS['Response']->sendJSON($cards_info);
    }

    private function getCardUpdateInfo(Artifact $artifact, PFUser $current_user): array
    {
        $card_info              = [];
        $tracker_id             = $artifact->getTracker()->getId();
        $remaining_effort_field = $this->form_element_factory->getComputableFieldByNameForUser(
            $tracker_id,
            Tracker::REMAINING_EFFORT_FIELD_NAME,
            $current_user
        );
        if ($remaining_effort_field !== null) {
            $remaining_effort = $remaining_effort_field->fetchCardValue($artifact);
            $remaining_effort = $this->addAutocomputeLabelIfFieldIsAutcocomputed($artifact, $remaining_effort_field, $remaining_effort);

            $card_info[$artifact->getId()] = [
                Tracker::REMAINING_EFFORT_FIELD_NAME => $remaining_effort,
            ];
        }
        return $card_info;
    }

    private function addAutocomputeLabelIfFieldIsAutcocomputed(
        Artifact $artifact,
        Tracker_FormElement_Field $remaining_effort_field,
        $remaining_effort,
    ) {
        if (
            $artifact->getTracker()->hasFormElementWithNameAndType($remaining_effort_field->getName(), ['computed'])
            && $remaining_effort_field instanceof Tracker_FormElement_Field_Computed
            && $remaining_effort_field->isArtifactValueAutocomputed($artifact)
        ) {
            $remaining_effort .= ' (' . dgettext('tuleap-tracker', 'autocomputed') . ')';
        }

        return $remaining_effort;
    }
}
