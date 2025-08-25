<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Masschange;

use Codendi_Request;
use DateTimeImmutable;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactDao;
use Tracker_ArtifactNotificationSubscriber;
use Tracker_Exception;
use Tracker_NoChangeException;
use Tracker_Report;
use Tracker_RuleFactory;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveUsedListField;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Tracker;

final readonly class MasschangeUpdater
{
    public function __construct(
        private Tracker $tracker,
        private Tracker_Report $tracker_report,
        private MasschangeDataValueExtractor $masschange_values_extractor,
        private Tracker_RuleFactory $rule_factory,
        private RetrieveUsedListField $form_element_factory,
        private RetrieveArtifact $artifact_factory,
        private Tracker_ArtifactDao $artifact_dao,
        private EventDispatcherInterface $event_manager,
        private CreateNewChangeset $changeset_creator,
    ) {
    }

    public function updateArtifacts(PFUser $user, Codendi_Request $request): void
    {
        if ($this->tracker->userIsAdmin($user)) {
            $masschange_aids = array_map('intval', $request->get('masschange_aids'));
            if (empty($masschange_aids)) {
                $GLOBALS['Response']->addFeedback(
                    'error',
                    dgettext('tuleap-tracker', 'No artifacts have been selected')
                );
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId());
            }

            $unsubscribe = $request->get('masschange-unsubscribe-option');
            if ($unsubscribe) {
                $this->unsubscribeUserFromEachArtifactNotification($user, $request, $masschange_aids);
            }

            $send_notifications = $this->getSendNotificationsFromRequest($request);
            $masschange_data    = $request->get('artifact');

            if (! $unsubscribe && empty($masschange_data)) {
                $GLOBALS['Response']->addFeedback(
                    'error',
                    dgettext('tuleap-tracker', 'No artifacts have been selected')
                );
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId());
            }

            $comment = $request->get('artifact_masschange_followup_comment');

            $new_fields_data = $this->masschange_values_extractor->getNewValues($masschange_data);

            if (count($masschange_data) > 0 || $comment !== '') {
                $comment_format = (string) $request->get('comment_formatmass_change');
                $this->updateArtifactsMasschange(
                    $user,
                    $masschange_aids,
                    $new_fields_data,
                    $comment,
                    $send_notifications,
                    $comment_format
                );
            }

            $event = new TrackerMasschangeProcessExternalActionsEvent(
                $user,
                $this->tracker,
                $request,
                $masschange_aids
            );
            $this->event_manager->dispatch($event);

            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId());
        } else {
            $GLOBALS['Response']->addFeedback(
                'error',
                dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.')
            );
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker_report->getId());
        }
    }

    private function updateArtifactsMasschange(
        PFUser $submitter,
        array $masschange_aids,
        array $fields_data,
        string $comment,
        bool $send_notifications,
        string $comment_format,
    ): void {
        $fields_data = $this->consolidateFieldsData($fields_data);

        $list_fields_used_in_tracker_rules = $this->getFieldListUsedInTrackerRules();

        $not_updated_aids = [];

        $comment_format_identifier =  CommentFormatIdentifier::fromStringWithDefault($comment_format);

        foreach ($masschange_aids as $aid) {
            $artifact = $this->artifact_factory->getArtifactById($aid);
            if ($artifact === null) {
                $not_updated_aids[] = $aid;
                continue;
            }

            $last_changeset = $artifact->getLastChangeset();
            if ($last_changeset === null) {
                $not_updated_aids[] = $aid;
                continue;
            }

            $fields_data_for_artifact            = $this->consolidateFieldListDataForArtifact(
                $last_changeset,
                $list_fields_used_in_tracker_rules,
                $fields_data
            );
            $new_changeset                       = NewChangeset::fromFieldsDataArray(
                $artifact,
                $fields_data_for_artifact,
                $comment,
                $comment_format_identifier,
                [],
                $submitter,
                (new DateTimeImmutable())->getTimestamp(),
                new CreatedFileURLMapping()
            );
            $post_creation_context_configuration = PostCreationContext::withNoConfig($send_notifications);
            try {
                $this->changeset_creator->create(
                    $new_changeset,
                    $post_creation_context_configuration
                );
            } catch (Tracker_NoChangeException $e) {
                $GLOBALS['Response']->addFeedback('info', $e->getMessage(), CODENDI_PURIFIER_LIGHT);
                $not_updated_aids[] = $aid;
                continue;
            } catch (Tracker_Exception $e) {
                $GLOBALS['Response']->addFeedback(
                    'error',
                    sprintf(dgettext('tuleap-tracker', 'Unable to update artifact %1$s'), $aid)
                );
                $GLOBALS['Response']->addFeedback('error', $e->getMessage());
                $not_updated_aids[] = $aid;
                continue;
            }
        }
        if (! empty($not_updated_aids)) {
            $GLOBALS['Response']->addFeedback(
                'error',
                sprintf(
                    dgettext('tuleap-tracker', 'The following artifacts were not updated (%1$s)'),
                    implode(', ', $not_updated_aids)
                )
            );

            return;
        }

        $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-tracker', 'Successfully Updated'));
        $GLOBALS['Response']->addFeedback(
            'info',
            sprintf(dgettext('tuleap-tracker', 'Updated %1$s'), implode(', ', $masschange_aids))
        );
    }

    private function consolidateFieldsData(array $fields_data): array
    {
        $fields_data['request_method_called'] = 'artifact-masschange';
        $this->tracker->augmentDataFromRequest($fields_data);
        unset($fields_data['request_method_called']);

        return $fields_data;
    }

    /**
     * @return ListField[]
     */
    private function getFieldListUsedInTrackerRules(): array
    {
        $list_fields_used_in_tracker_rules = [];
        $tracker_rules_for_list_field      = $this->rule_factory->getAllListRulesByTrackerWithOrder(
            $this->tracker->getId()
        );
        foreach ($tracker_rules_for_list_field as $tracker_rule_for_list_field) {
            $source_field_id = $tracker_rule_for_list_field->getSourceFieldId();
            $source_field    = $this->form_element_factory->getUsedListFieldById($this->tracker, $source_field_id);
            $target_field_id = $tracker_rule_for_list_field->getTargetFieldId();
            $target_field    = $this->form_element_factory->getUsedListFieldById($this->tracker, $target_field_id);
            if ($source_field !== null) {
                assert($source_field instanceof ListField);
                $list_fields_used_in_tracker_rules[$source_field_id] = $source_field;
            }
            if ($target_field !== null) {
                assert($target_field instanceof ListField);
                $list_fields_used_in_tracker_rules[$target_field_id] = $target_field;
            }
        }

        return $list_fields_used_in_tracker_rules;
    }

    /**
     * @param ListField[] $list_fields
     */
    private function consolidateFieldListDataForArtifact(
        Tracker_Artifact_Changeset $changeset,
        array $list_fields,
        array $fields_data,
    ): array {
        foreach ($list_fields as $list_field) {
            $changeset_value = $changeset->getValue($list_field);
            if ($changeset_value !== null) {
                $value = $changeset_value->getValue();
                if ($list_field->isNone($value) && ! (isset($fields_data[$list_field->getId()]))) {
                    $field_value = [ListField::NONE_VALUE];
                    if (count($field_value) === 1) {
                        $fields_data[$list_field->getId()] = current($field_value);
                    } else {
                        $fields_data[$list_field->getId()] = $field_value;
                    }
                }
            }
        }

        return $fields_data;
    }

    private function unsubscribeUserFromEachArtifactNotification(
        PFUser $user,
        Codendi_Request $request,
        array $masschange_aids,
    ): void {
        foreach ($masschange_aids as $artifact_id) {
            $notification_subscriber = $this->getArtifactNotificationSubscriber((int) $artifact_id);
            $notification_subscriber->unsubscribeUserWithoutRedirect($user, $request);
        }

        $GLOBALS['Response']->addFeedback(
            'info',
            sprintf(
                dgettext('tuleap-tracker', 'You are unsubscribed from notifications of artifacts %1$s'),
                implode(', ', $masschange_aids)
            )
        );
    }

    private function getArtifactNotificationSubscriber(int $artifact_id): Tracker_ArtifactNotificationSubscriber
    {
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if ($artifact === null) {
            throw new RuntimeException(
                'Impossible to find artifact #' . $artifact_id . ' to mass-unsubscribe a user from notifications'
            );
        }

        return new Tracker_ArtifactNotificationSubscriber(
            $artifact,
            $this->artifact_dao
        );
    }

    private function getSendNotificationsFromRequest(Codendi_Request $request): bool
    {
        $send_notifications = false;
        if ($request->exist('notify')) {
            if ($request->get('notify') == 'ok') {
                $send_notifications = true;
            }
        }

        return $send_notifications;
    }
}
