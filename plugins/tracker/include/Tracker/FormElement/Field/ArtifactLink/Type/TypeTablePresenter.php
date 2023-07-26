<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

use Tracker_FormElement_Field_ArtifactLink;

class TypeTablePresenter
{
    public $table_id;
    public $type;
    public $type_label;
    public $id_label;
    public $project_label;
    public $tracker_label;
    public $summary_label;
    public $status_label;
    public $last_update_label;
    public $submitted_by_label;
    public $assigned_to_label;
    public $tracker_id;

    public $artifact_links;
    public $mass_unlink_title;

    public const TABLE_ID_PREFIX = "tracker_report_table_type_";

    public function __construct(
        \PFUser $current_user,
        TypePresenter $type,
        array $artifact_links,
        bool $is_reverse_artifact_links,
        Tracker_FormElement_Field_ArtifactLink $field,
        public bool $are_links_deletable,
    ) {
        $this->table_id   = self::TABLE_ID_PREFIX . $type->shortname;
        $this->type       = $type->shortname;
        $this->type_label = $this->fetchTabLabel($type, $is_reverse_artifact_links);
        $this->tracker_id = $field->getTracker()->getId();

        $this->id_label           = dgettext('tuleap-tracker', 'Artifact ID');
        $this->project_label      = dgettext('tuleap-tracker', 'Project');
        $this->tracker_label      = dgettext('tuleap-tracker', 'Tracker');
        $this->summary_label      = dgettext('tuleap-tracker', 'Summary');
        $this->status_label       = dgettext('tuleap-tracker', 'Status');
        $this->last_update_label  = dgettext('tuleap-tracker', 'Last Update Date');
        $this->submitted_by_label = dgettext('tuleap-tracker', 'Submitted By');
        $this->assigned_to_label  = dgettext('tuleap-tracker', 'Assigned to');

        $art_factory          = \Tracker_ArtifactFactory::instance();
        $this->artifact_links = [];
        $html_classes         = '';
        foreach ($artifact_links as $artifact_link) {
            $artifact = $art_factory->getArtifactByIdUserCanView($current_user, $artifact_link->getArtifactId());
            if ($artifact === null) {
                continue;
            }
            $this->artifact_links[] = new ArtifactInTypeTablePresenter(
                $current_user,
                $artifact,
                $html_classes,
                $field,
                $this->are_links_deletable,
            );
        }

        $this->mass_unlink_title = dgettext('tuleap-tracker', 'Mark all links to be removed');
    }

    public static function buildForHeader(
        \PFUser $current_user,
        TypePresenter $type_presenter,
        Tracker_FormElement_Field_ArtifactLink $field,
        bool $are_links_deletable,
    ): TypeTablePresenter {
        return new TypeTablePresenter(
            $current_user,
            $type_presenter,
            [],
            false,
            $field,
            $are_links_deletable,
        );
    }

    private function fetchTabLabel($type, bool $is_reverse_artifact_links): string
    {
        $type_label = '';
        if ($is_reverse_artifact_links) {
            $type_label = $type->reverse_label;
        } else {
            $type_label = $type->forward_label;
        }
        return $type_label;
    }
}
