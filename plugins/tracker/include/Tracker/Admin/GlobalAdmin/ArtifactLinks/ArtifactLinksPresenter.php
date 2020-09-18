<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\GlobalAdmin\ArtifactLinks;

use CSRFSynchronizerToken;
use Project;

class ArtifactLinksPresenter
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $table_title;

    /**
     * @var string
     */
    public $switch_label;

    /**
     * @var string
     */
    public $form_url;

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var bool
     */
    public $are_artifact_link_types_enabled;

    /**
     * @var array
     */
    public $artifact_link_types;

    /**
     * @var string
     */
    public $switch_button_label;

    /**
     * @var string
     */
    public $form_type_url;

    /**
     * @var string
     */
    public $warning_message;

    /**
     * @var bool
     */
    public $has_at_least_one_disabled_type;
    /**
     * @var string
     */
    public $global_admin_url;
    /**
     * @var string
     */
    public $available_types;
    /**
     * @var string
     */
    public $shortname_label;
    /**
     * @var string
     */
    public $forward_label_label;
    /**
     * @var string
     */
    public $reverse_label_label;

    public function __construct(
        Project $project,
        CSRFSynchronizerToken $csrf_token,
        bool $are_artifact_link_types_enabled,
        array $artifact_link_types,
        bool $has_at_least_one_disabled_type
    ) {
        $this->title        = dgettext('tuleap-tracker', 'Tracker global admininistration');
        $this->table_title  = dgettext('tuleap-tracker', 'Artifact links types');
        $this->switch_label = dgettext('tuleap-tracker', 'Activate artifact links types for all the trackers of this project?');

        $base_url = ArtifactLinksController::getTrackerGlobalAdministrationURL($project);
        $this->global_admin_url = $base_url;
        $this->form_url = $base_url . '?' . http_build_query(
            [
                'func' => 'edit-artifact-links'
            ]
        );

        $this->csrf_token                      = $csrf_token;
        $this->are_artifact_link_types_enabled = $are_artifact_link_types_enabled;

        $this->available_types     = dgettext('tuleap-tracker', 'Available types');
        $this->shortname_label     = dgettext('tuleap-tracker', 'Shortname');
        $this->forward_label_label = dgettext('tuleap-tracker', 'Forward label');
        $this->reverse_label_label = dgettext('tuleap-tracker', 'Reverse label');
        $this->switch_button_label = dgettext('tuleap-tracker', 'Use');
        $this->artifact_link_types = $artifact_link_types;

        $this->form_type_url = $base_url . '?' . http_build_query(
            [
                'func' => 'use-artifact-link-type'
            ]
        );

        $this->warning_message = dgettext(
            'tuleap-tracker',
            'After artifact edition, all the disabled types used will be cleared without removing the existing link.'
        );

        $this->has_at_least_one_disabled_type = $has_at_least_one_disabled_type;
    }
}
