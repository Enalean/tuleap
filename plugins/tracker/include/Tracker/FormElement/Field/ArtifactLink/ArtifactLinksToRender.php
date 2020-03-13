<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tracker;
use Tracker_ArtifactLinkInfo;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;

class ArtifactLinksToRender
{
    private $has_artifact_to_display;
    private $grouped_by_project_then_tracker  = [];
    private $grouped_by_nature_with_presenter = [];

    private $can_user_artifact_link_cache = [];
    private $nature_presenter_cache       = [];

    public function __construct(
        \PFUser $current_user,
        \Tracker_FormElement_Field_ArtifactLink $field,
        \TrackerFactory $tracker_factory,
        \Tracker_ReportFactory $report_factory,
        NaturePresenterFactory $nature_presenter_factory,
        Tracker_ArtifactLinkInfo ...$link_infos
    ) {
        if (empty($link_infos)) {
            $this->has_artifact_to_display = false;
            return;
        }
        $this->has_artifact_to_display = true;
        $matching_ids                  = $this->getMatchingIDs(
            $current_user,
            $field,
            $nature_presenter_factory,
            ...$link_infos
        );
        $this->grouped_by_project_then_tracker = $this->groupAndEnhanceMatchingIDsPerProject(
            $tracker_factory,
            $report_factory,
            $matching_ids
        );
        $this->grouped_by_nature_with_presenter = $this->groupPerNatureWithPresenter(
            $field,
            $current_user,
            $nature_presenter_factory,
            ...$link_infos
        );
    }

    private function getMatchingIDs(
        \PFUser $current_user,
        \Tracker_FormElement_Field_ArtifactLink $field,
        NaturePresenterFactory $nature_presenter_factory,
        Tracker_ArtifactLinkInfo ...$link_infos
    ) {
        $tracker = $field->getTracker();
        if ($tracker === null) {
            return [];
        }
        $project_allowed_to_use_nature = $tracker->isProjectAllowedToUseNature();
        $ids = [];
        // build an array of artifact_id / last_changeset_id for fetch renderer method
        foreach ($link_infos as $artifact_link) {
            if ($this->canUseArtifactLink($current_user, $artifact_link)) {
                $artifact_link_tracker_id = $artifact_link->getTrackerId();
                if (! isset($ids[$artifact_link_tracker_id])) {
                    $ids[$artifact_link->getTrackerId()] = array(
                        'id'                => '',
                        'last_changeset_id' => ''
                    );
                    if ($project_allowed_to_use_nature) {
                        $ids[$artifact_link_tracker_id]['nature'] = [];
                    }
                }
                $artifact_id = $artifact_link->getArtifactId();
                $ids[$artifact_link_tracker_id]['id'] .= "$artifact_id,";
                $ids[$artifact_link_tracker_id]['last_changeset_id'] .= $artifact_link->getLastChangesetId() . ',';
                if ($project_allowed_to_use_nature) {
                    $nature_presenter = $this->getNaturePresenterFromShortnameWithCache(
                        $nature_presenter_factory,
                        $artifact_link->getNature()
                    );
                    $ids[$artifact_link_tracker_id]['nature'][$artifact_id] = $nature_presenter;
                }
            }
        }
        return $ids;
    }

    private function groupAndEnhanceMatchingIDsPerProject(
        \TrackerFactory $tracker_factory,
        \Tracker_ReportFactory $report_factory,
        array $matching_ids_per_tracker
    ) {
        $projects = [];
        foreach ($matching_ids_per_tracker as $tracker_id => $matching_ids) {
            //remove last coma
            $matching_ids['id']                = substr($matching_ids['id'], 0, -1);
            $matching_ids['last_changeset_id'] = substr($matching_ids['last_changeset_id'], 0, -1);

            $tracker = $tracker_factory->getTrackerById($tracker_id);
            $project = $tracker->getProject();
            if ($tracker->userCanView() && ! $tracker->isDeleted()) {
                $report = $report_factory->getDefaultReportsByTrackerId($tracker->getId());
                if ($report) {
                    $renderers = $report->getRenderers();
                    $renderer_table_found = false;
                    // looking for the first table renderer
                    foreach ($renderers as $renderer) {
                        if ($renderer->getType() === \Tracker_Report_Renderer::TABLE) {
                            $projects[$project->getGroupId()][$tracker_id] = new ArtifactLinksToRenderForPerTrackerTable(
                                $tracker,
                                $matching_ids,
                                $renderer
                            );
                            $renderer_table_found = true;
                            break;
                        }
                    }
                    if (! $renderer_table_found) {
                        $projects[$project->getGroupId()][$tracker_id] = new ArtifactLinksToRenderForPerTrackerTable(
                            $tracker,
                            $matching_ids,
                            null
                        );
                    }
                } else {
                    $projects[$project->getGroupId()][$tracker_id] = new ArtifactLinksToRenderForPerTrackerTable(
                        $tracker,
                        $matching_ids,
                        null
                    );
                }
            }
        }
        return $projects;
    }

    private function groupPerNatureWithPresenter(
        \Tracker_FormElement_Field_ArtifactLink $field,
        \PFUser $current_user,
        NaturePresenterFactory $nature_presenter_factory,
        Tracker_ArtifactLinkInfo ...$link_infos
    ) {
        $tracker = $field->getTracker();
        if ($tracker === null || ! $tracker->isProjectAllowedToUseNature()) {
            return [];
        }

        $by_nature = [];
        foreach ($link_infos as $artifact_link) {
            if ($this->canUseArtifactLink($current_user, $artifact_link)) {
                $nature = $artifact_link->getNature();
                if (! isset($by_nature[$nature])) {
                    $by_nature[$nature] = [];
                }
                $by_nature[$nature][] = $artifact_link;
            }
        }

        $grouped_by_nature_with_presenter = [];
        foreach ($by_nature as $nature => $artifact_links) {
            if (empty($nature)) {
                continue;
            }

            $nature_presenter = $this->getNaturePresenterFromShortnameWithCache($nature_presenter_factory, $nature);
            if ($nature_presenter === null) {
                continue;
            }
            $grouped_by_nature_with_presenter[$nature] = new ArtifactLinksToRenderForPerNatureTable(
                $nature_presenter,
                ...$artifact_links
            );
        }

        return $grouped_by_nature_with_presenter;
    }

    /**
     * @return bool
     */
    private function canUseArtifactLink(\PFUser $user, Tracker_ArtifactLinkInfo $artifact_link)
    {
        if (isset($this->can_user_artifact_link_cache[$user->getId()][spl_object_hash($artifact_link)])) {
            return $this->can_user_artifact_link_cache[$user->getId()][spl_object_hash($artifact_link)];
        }
        $can_use_artifact_link = ($artifact_link->getTracker()->isActive() &&
            $artifact_link->userCanView($user) && ! $this->hideArtifact($artifact_link));
        $this->can_user_artifact_link_cache[$user->getId()][spl_object_hash($artifact_link)] = $can_use_artifact_link;
        return $can_use_artifact_link;
    }

    /**
     * @return bool
     */
    private function hideArtifact(Tracker_ArtifactLinkInfo $artifactlink_info)
    {
        return $artifactlink_info->shouldLinkBeHidden(
            $artifactlink_info->getNature()
        );
    }

    /**
     * @return null|Nature\NaturePresenter
     */
    private function getNaturePresenterFromShortnameWithCache(NaturePresenterFactory $nature_presenter_factory, $shortname)
    {
        if (isset($this->nature_presenter_cache[$shortname])) {
            return $this->nature_presenter_cache[$shortname];
        }
        $nature_presenter                         = $nature_presenter_factory->getFromShortname($shortname);
        $this->nature_presenter_cache[$shortname] = $nature_presenter;
        return $nature_presenter;
    }

    public function hasArtifactLinksToDisplay()
    {
        return $this->has_artifact_to_display;
    }

    public function getArtifactLinksForPerTrackerDisplay()
    {
        foreach ($this->grouped_by_project_then_tracker as $trackers) {
            foreach ($trackers as $artifact_links_per_tracker) {
                yield $artifact_links_per_tracker;
            }
        }
    }

    /**
     *
     * @return ArtifactLinksToRenderForPerTrackerTable|null
     */
    public function getArtifactLinksForAGivenTracker(Tracker $tracker)
    {
        $project_id = $tracker->getGroupId();
        $tracker_id = $tracker->getId();

        if (! isset($this->grouped_by_project_then_tracker[$project_id])) {
            return null;
        }

        if (! isset($this->grouped_by_project_then_tracker[$project_id][$tracker_id])) {
            return null;
        }

        return $this->grouped_by_project_then_tracker[$project_id][$tracker_id];
    }

    public function getArtifactLinksForPerNatureDisplay()
    {
        return $this->grouped_by_nature_with_presenter;
    }
}
