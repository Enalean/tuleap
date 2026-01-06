<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ActionButtons;

class GlobalButtonsActionPresenter
{
    /**
     * @var bool
     */
    public $has_at_least_one_action;
    /**
     * @var ArtifactMoveButtonPresenter
     */
    public $artifact_move_button_presenter;
    /**
     * @var ArtifactCopyButtonPresenter
     */
    public $artifact_copy_button_presenter;
    /**
     * @var ArtifactNotificationsButtonPresenter
     */
    public $artifact_notifications_button_presenter;
    /**
     * @var ArtifactOriginalEmailButtonPresenter
     */
    public $artifact_original_email_button_presenter;
    /**
     * @var bool
     */
    public $should_load_modal;
    /**
     * @var ArtifactMoveModalPresenter
     */
    public $artifact_move_modal_presenter;
    /**
     * @var array
     */
    public $additional_buttons;
    public bool $notifications_divider;
    public bool $additional_buttons_divider;
    public bool $can_delete;
    public bool $delete_divider;
    public bool $is_deletion_disabled;
    public string $deletion_disabled_reason;

    public function __construct(
        array $additional_buttons,
        ?ArtifactMoveButtonPresenter $artifact_move_button_presenter,
        ?ArtifactMoveModalPresenter $artifact_move_modal_presenter,
        ?ArtifactCopyButtonPresenter $artifact_copy_button_presenter,
        ?ArtifactNotificationsButtonPresenter $artifact_notifications_button_presenter,
        ?ArtifactOriginalEmailButtonPresenter $artifact_original_email_button_presenter,
        public readonly ?ArtifactDeleteModalPresenter $artifact_delete_modal_presenter,
    ) {
        $this->artifact_move_button_presenter           = $artifact_move_button_presenter;
        $this->artifact_move_modal_presenter            = $artifact_move_modal_presenter;
        $this->artifact_copy_button_presenter           = $artifact_copy_button_presenter;
        $this->artifact_notifications_button_presenter  = $artifact_notifications_button_presenter;
        $this->artifact_original_email_button_presenter = $artifact_original_email_button_presenter;

        $this->should_load_modal = $artifact_move_button_presenter !== null &&
            ! $artifact_move_button_presenter->hasError();

        $has_primary_action                 = $this->hasPrimaryAction(
            $artifact_move_button_presenter,
            $artifact_copy_button_presenter,
            $artifact_original_email_button_presenter
        );
        $has_at_least_one_additional_action = count($additional_buttons) > 0;
        $has_notifications_action           = $artifact_notifications_button_presenter !== null;

        $this->additional_buttons_divider = $has_primary_action && $has_at_least_one_additional_action;

        $this->notifications_divider = ($has_primary_action || $has_at_least_one_additional_action)
            && $has_notifications_action;

        $this->can_delete     = $this->artifact_delete_modal_presenter !== null;
        $this->delete_divider = ($has_primary_action || $has_at_least_one_additional_action || $has_notifications_action)
            && $this->can_delete;

        $is_deletion_allowed     = $this->artifact_delete_modal_presenter?->artifacts_deletion_limit > 0;
        $has_remaining_deletions = $this->artifact_delete_modal_presenter?->artifacts_deletion_count < $this->artifact_delete_modal_presenter?->artifacts_deletion_limit;

        $this->is_deletion_disabled     = ! $is_deletion_allowed || ! $has_remaining_deletions;
        $this->deletion_disabled_reason = $this->getDeletionDisabledReason($is_deletion_allowed, $has_remaining_deletions);

        $this->has_at_least_one_action = $has_primary_action
            || $has_at_least_one_additional_action
            || $has_notifications_action
            || $this->can_delete;

        $this->additional_buttons = $additional_buttons;
    }

    /**
     *
     * @return bool
     */
    private function hasPrimaryAction(
        ?ArtifactMoveButtonPresenter $artifact_move_button_presenter = null,
        ?ArtifactCopyButtonPresenter $artifact_copy_button_presenter = null,
        ?ArtifactOriginalEmailButtonPresenter $artifact_original_email_button_presenter = null,
    ) {
        return ($artifact_move_button_presenter !== null ||
            $artifact_copy_button_presenter !== null ||
            $artifact_original_email_button_presenter !== null
        );
    }

    public function shouldLoadMoveArtifactModal(): bool
    {
        return $this->should_load_modal;
    }

    public function shouldLoadDeleteArtifactModal(): bool
    {
        return $this->can_delete;
    }

    private function getDeletionDisabledReason(bool $is_deletion_allowed, bool $has_remaining_deletions): string
    {
        if (! $is_deletion_allowed) {
            return dgettext('tuleap-tracker', 'Artifacts deletion is deactivated. Please contact your site administrator.');
        }

        if (! $has_remaining_deletions) {
            return dgettext('tuleap-tracker', 'You have reached the limit of artifacts deletion for the next 24 hours. Please come back later.');
        }

        return '';
    }
}
