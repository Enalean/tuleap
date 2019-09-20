<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use Tracker_FormElement_Field_ArtifactLink;

class NatureTablePresenter
{

    public $table_id;
    public $nature;
    public $nature_label;
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

    public const TABLE_ID_PREFIX = "tracker_report_table_nature_";

    public function __construct(
        NaturePresenter $nature,
        array $artifact_links,
        $is_reverse_artifact_links,
        Tracker_FormElement_Field_ArtifactLink $field
    ) {
        $this->table_id              = self::TABLE_ID_PREFIX . $nature->shortname;
        $this->nature                = $nature->shortname;
        $this->nature_label          = $this->fetchTabLabel($nature, $is_reverse_artifact_links);
        $this->tracker_id            = $field->getTracker()->getId();

        $language                 = $GLOBALS['Language'];
        $this->id_label           = $language->getText('plugin_tracker_formelement_admin', 'artifactid_label');
        $this->project_label      = $language->getText('plugin_tracker_include_artifact', 'project');
        $this->tracker_label      = $language->getText('plugin_tracker_import_admin', 'tracker');
        $this->summary_label      = $language->getText('plugin_tracker_include_artifact', 'summary');
        $this->status_label       = $language->getText('plugin_tracker_admin_semantic', 'status_label');
        $this->last_update_label  = $language->getText('plugin_tracker_formelement_admin', 'lastupdatedate_label');
        $this->submitted_by_label = $language->getText('plugin_tracker_formelement_admin', 'submittedby_label');
        $this->assigned_to_label  = $language->getText('plugin_tracker_formelement_admin', 'assignedto_label');

        $art_factory = \Tracker_ArtifactFactory::instance();
        $this->artifact_links = array();
        $html_classes = '';
        foreach ($artifact_links as $artifact_link) {
            $artifact               = $art_factory->getArtifactById($artifact_link->getArtifactId());
            $this->artifact_links[] = new ArtifactInNatureTablePresenter($artifact, $html_classes, $field);
        }

        $this->mass_unlink_title = $GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'mass_unlink_title');
    }

    public static function buildForHeader(NaturePresenter $nature_presenter, Tracker_FormElement_Field_ArtifactLink $field)
    {
        return new NatureTablePresenter(
            $nature_presenter,
            array(),
            false,
            $field
        );
    }

    private function fetchTabLabel($nature, $is_reverse_artifact_links)
    {
        $nature_label = '';
        if ($is_reverse_artifact_links) {
            $nature_label = $nature->reverse_label;
        } else {
            $nature_label = $nature->forward_label;
        }
        return $nature_label;
    }
}
