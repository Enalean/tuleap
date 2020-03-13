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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RemovedLinkCollection;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\AddedLinkByNatureCollection;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\UpdatedNatureLinkCollection;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfLinksFormatter;

class Tracker_Artifact_ChangesetValue_ArtifactLinkDiff
{
    /**
     * Plugin can choose to hide artifacts
     *
     * Parameters:
     *  - nature        => (input) String
     *  - hide_artifact => (output) Bool
     *  - artifact      => Tracker_Artifact
     *
     */
    public const HIDE_ARTIFACT = 'hide_artifact';

    /** @var Tracker_ArtifactLinkInfo[] */
    private $previous;

    /** @var Tracker_ArtifactLinkInfo[] */
    private $next;

    /** @var RemovedLinkCollection */
    private $removed;

    /** @var AddedLinkByNatureCollection[] */
    private $added_by_nature;

    /** @var UpdatedNatureLinkCollection[] */
    private $updated_by_nature;

    /**
     * @param Tracker_ArtifactLinkInfo[] $previous
     * @param Tracker_ArtifactLinkInfo[] $next
     */
    public function __construct(
        array $previous,
        array $next,
        Tracker $tracker,
        NaturePresenterFactory $nature_factory
    ) {
        $this->previous       = $previous;
        $this->next           = $next;
        if ($this->hasChanges()) {
            $formatter = new CollectionOfLinksFormatter();
            $this->removed = new RemovedLinkCollection($formatter);
            $removed_elements = array_diff(array_keys($previous), array_keys($next));
            foreach ($removed_elements as $key) {
                $this->removed->add($previous[$key]);
            }

            $this->added_by_nature   = array();
            $this->updated_by_nature = array();
            foreach ($next as $key => $artifactlinkinfo) {
                if (! isset($previous[$key])) {
                    $this->fillAddedByNature($artifactlinkinfo, $nature_factory, $formatter);
                } elseif ($previous[$key]->getNature() !== $artifactlinkinfo->getNature()) {
                    $this->fillUpdatedByNature($previous[$key], $artifactlinkinfo, $tracker, $nature_factory, $formatter);
                }
            }
        }
    }

    private function fillAddedByNature(
        Tracker_ArtifactLinkInfo $artifactlinkinfo,
        NaturePresenterFactory $nature_factory,
        CollectionOfLinksFormatter $formatter
    ) {
        if ($artifactlinkinfo->getNature() !== "" && $artifactlinkinfo->shouldLinkBeHidden($artifactlinkinfo->getNature())) {
            return;
        }
        $nature = $nature_factory->getFromShortname($artifactlinkinfo->getNature());
        if ($nature === null) {
            return;
        }
        if (! isset($this->added_by_nature[$nature->shortname])) {
            $this->added_by_nature[$nature->shortname] = new AddedLinkByNatureCollection($nature, $formatter);
        }
        $this->added_by_nature[$nature->shortname]->add($artifactlinkinfo);
    }

    private function getNatureFormChangesets(
        Tracker_ArtifactLinkInfo $previous_link,
        Tracker_ArtifactLinkInfo $next_link
    ) {
        $nature = $next_link->getNature();
        if ($previous_link->getNature()) {
            $nature = $previous_link->getNature();
        }

        return $nature;
    }

    private function fillUpdatedByNature(
        Tracker_ArtifactLinkInfo $previous_link,
        Tracker_ArtifactLinkInfo $next_link,
        Tracker $tracker,
        NaturePresenterFactory $nature_factory,
        CollectionOfLinksFormatter $formatter
    ) {
        if (! $tracker->isProjectAllowedToUseNature()) {
            return;
        }

        $nature = $this->getNatureFormChangesets($previous_link, $next_link);
        if ($nature !== "" && $previous_link->shouldLinkBeHidden($nature)) {
            return;
        }

        $previous_nature = $nature_factory->getFromShortname($previous_link->getNature());
        $next_nature     = $nature_factory->getFromShortname($next_link->getNature());
        if ($previous_nature == $next_nature) {
            return;
        }

        $key = $previous_nature->shortname . '-' . $next_nature->shortname;
        if (! isset($this->updated_by_nature[$key])) {
            $this->updated_by_nature[$key] = new UpdatedNatureLinkCollection(
                $previous_nature,
                $next_nature,
                $formatter
            );
        }
        $this->updated_by_nature[$key]->add($next_link);
    }

    /**
     * @return bool
     */
    public function hasChanges()
    {
        return $this->previous != $this->next;
    }

    public function fetchFormatted(PFUser $user, $format, $ignore_perms)
    {
        if (! $this->hasChanges()) {
            return;
        }

        if (empty($this->next)) {
            return ' ' . dgettext('tuleap-tracker', 'cleared');
        }

        $formatted_messages = array();
        $formatted_messages[] = $this->removed->fetchFormatted($user, $format, $ignore_perms);
        foreach ($this->added_by_nature as $collection) {
            $formatted_messages[] = $collection->fetchFormatted($user, $format, $ignore_perms);
        }
        foreach ($this->updated_by_nature as $collection) {
            $formatted_messages[] = $collection->fetchFormatted($user, $format, $ignore_perms);
        }

        return $this->groupFormattedMessages(array_filter($formatted_messages), $format);
    }

    private function groupFormattedMessages(array $formatted_messages, $format)
    {
        if (! $formatted_messages) {
            return false;
        }

        if ($format === 'html') {
            return '<ul><li>' . implode('</li><li>', $formatted_messages) . '</li></ul>';
        } else {
            $separator = "\n    * ";
            return $separator . implode($separator, $formatted_messages) . "\n";
        }
    }
}
