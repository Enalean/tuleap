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
    public $divider;
    /**
     * @var bool
     */
    public $should_load_modal;
    /**
     * @var bool
     */
    public $has_at_least_one_additional_action;
    /**
     * @var ArtifactMoveModalPresenter
     */
    public $artifact_move_modal_presenter;
    /**
     * @var array
     */
    public $additional_buttons;

    public function __construct(
        array $additional_buttons,
        ?ArtifactMoveButtonPresenter $artifact_move_button_presenter = null,
        ?ArtifactMoveModalPresenter $artifact_move_modal_presenter = null,
        ?ArtifactCopyButtonPresenter $artifact_copy_button_presenter = null,
        ?ArtifactNotificationsButtonPresenter $artifact_notifications_button_presenter = null,
        ?ArtifactOriginalEmailButtonPresenter $artifact_original_email_button_presenter = null,
    ) {
        $this->artifact_move_button_presenter           = $artifact_move_button_presenter;
        $this->artifact_move_modal_presenter            = $artifact_move_modal_presenter;
        $this->artifact_copy_button_presenter           = $artifact_copy_button_presenter;
        $this->artifact_notifications_button_presenter  = $artifact_notifications_button_presenter;
        $this->artifact_original_email_button_presenter = $artifact_original_email_button_presenter;

        $this->should_load_modal = $artifact_move_button_presenter !== null &&
            ! $artifact_move_button_presenter->hasError();

        $this->divider = $this->hasPrimaryAction(
            $artifact_move_button_presenter,
            $artifact_copy_button_presenter,
            $artifact_original_email_button_presenter
        )
        && $artifact_notifications_button_presenter !== null;

        $this->has_at_least_one_additional_action = count($additional_buttons) > 0;

        $this->has_at_least_one_action = $artifact_move_button_presenter !== null ||
            $artifact_copy_button_presenter !== null ||
            $artifact_original_email_button_presenter !== null ||
            $artifact_notifications_button_presenter !== null ||
            $this->has_at_least_one_additional_action;

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
}
