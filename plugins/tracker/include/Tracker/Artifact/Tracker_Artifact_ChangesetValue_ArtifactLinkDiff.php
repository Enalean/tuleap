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

use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\AddedLinkByTypeCollection;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfLinksFormatter;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RemovedLinkCollection;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\UpdatedTypeLinkCollection;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Tracker;

class Tracker_Artifact_ChangesetValue_ArtifactLinkDiff
{
    /** @var Tracker_ArtifactLinkInfo[] */
    private $previous;

    /** @var Tracker_ArtifactLinkInfo[] */
    private $next;

    /** @var RemovedLinkCollection */
    private $removed;

    /** @var AddedLinkByTypeCollection[] */
    private $added_by_type;

    /** @var UpdatedTypeLinkCollection[] */
    private $updated_by_type;

    /**
     * @param Tracker_ArtifactLinkInfo[] $previous
     * @param Tracker_ArtifactLinkInfo[] $next
     */
    public function __construct(
        array $previous,
        array $next,
        Tracker $tracker,
        TypePresenterFactory $type_factory,
    ) {
        $this->previous = $previous;
        $this->next     = $next;
        if ($this->hasChanges()) {
            $formatter        = new CollectionOfLinksFormatter();
            $this->removed    = new RemovedLinkCollection($formatter);
            $removed_elements = array_diff(array_keys($previous), array_keys($next));
            foreach ($removed_elements as $key) {
                $this->removed->add($previous[$key]);
            }

            $this->added_by_type   = [];
            $this->updated_by_type = [];
            foreach ($next as $key => $artifactlinkinfo) {
                if (! isset($previous[$key])) {
                    $this->fillAddedByType($artifactlinkinfo, $type_factory, $formatter);
                } elseif ($previous[$key]->getType() !== $artifactlinkinfo->getType()) {
                    $this->fillUpdatedByType($previous[$key], $artifactlinkinfo, $tracker, $type_factory, $formatter);
                }
            }
        }
    }

    private function fillAddedByType(
        Tracker_ArtifactLinkInfo $artifactlinkinfo,
        TypePresenterFactory $type_factory,
        CollectionOfLinksFormatter $formatter,
    ) {
        $type = $type_factory->getFromShortname($artifactlinkinfo->getType());
        if ($type === null) {
            return;
        }
        if (! isset($this->added_by_type[$type->shortname])) {
            $this->added_by_type[$type->shortname] = new AddedLinkByTypeCollection($type, $formatter);
        }
        $this->added_by_type[$type->shortname]->add($artifactlinkinfo);
    }

    private function getTypeFormChangesets(
        Tracker_ArtifactLinkInfo $previous_link,
        Tracker_ArtifactLinkInfo $next_link,
    ) {
        $type = $next_link->getType();
        if ($previous_link->getType()) {
            $type = $previous_link->getType();
        }

        return $type;
    }

    private function fillUpdatedByType(
        Tracker_ArtifactLinkInfo $previous_link,
        Tracker_ArtifactLinkInfo $next_link,
        Tracker $tracker,
        TypePresenterFactory $type_factory,
        CollectionOfLinksFormatter $formatter,
    ) {
        if (! $tracker->isProjectAllowedToUseType()) {
            return;
        }

        $type = $this->getTypeFormChangesets($previous_link, $next_link);

        $previous_type = $type_factory->getFromShortname($previous_link->getType());
        $next_type     = $type_factory->getFromShortname($next_link->getType());
        if ($previous_type == $next_type) {
            return;
        }

        $key = $previous_type->shortname . '-' . $next_type->shortname;
        if (! isset($this->updated_by_type[$key])) {
            $this->updated_by_type[$key] = new UpdatedTypeLinkCollection(
                $previous_type,
                $next_type,
                $formatter
            );
        }
        $this->updated_by_type[$key]->add($next_link);
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

        $formatted_messages   = [];
        $formatted_messages[] = $this->removed->fetchFormatted($user, $format, $ignore_perms);
        foreach ($this->added_by_type as $collection) {
            $formatted_messages[] = $collection->fetchFormatted($user, $format, $ignore_perms);
        }
        foreach ($this->updated_by_type as $collection) {
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
