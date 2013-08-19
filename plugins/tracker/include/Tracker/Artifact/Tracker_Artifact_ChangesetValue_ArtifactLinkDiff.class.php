<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Artifact_ChangesetValue_ArtifactLinkDiff {
    private $previous = array();
    private $next     = array();
    private $added    = array();
    private $removed  = array();

    /**
     * @param Tracker_ArtifactLinkInfo[] $previous
     * @param Tracker_ArtifactLinkInfo[] $next
     */
    public function __construct(array $previous, array $next) {
        $this->previous = $previous;
        $this->next     = $next;
        if ($this->hasChanges()) {
            $removed_elements = array_diff(array_keys($previous), array_keys($next));
            foreach ($removed_elements as $key) {
                $this->removed[] = $previous[$key];
            }

            $added_elements = array_diff(array_keys($next), array_keys($previous));
            foreach ($added_elements as $key) {
                $this->added[] = $next[$key];
            }
        }
    }

    /**
     * @return boolean
     */
    public function hasChanges() {
        return $this->previous != $this->next;
    }

    /**
     * @return boolean
     */
    public function isCleared() {
        return empty($this->next);
    }

    /**
     * @return boolean
     */
    public function isInitialized() {
        return empty($this->previous);
    }

    /**
     * @return boolean
     */
    public function isReplace() {
        return count($this->previous) == 1 && count($this->next) == 1;
    }

    /**
     * @return Tracker_ArtifactLinkInfo[]
     */
    public function getAdded() {
        return $this->added;
    }

    /**
     * @return Tracker_ArtifactLinkInfo[]
     */
    public function getRemoved() {
        return $this->removed;
    }

    /**
     * @param String $format
     * @return String
     */
    public function getAddedFormatted($format) {
        return $this->getFormatted($this->getAdded(), $format);
    }

    /**
     * @param String $format
     * @return String
     */
    public function getRemovedFormatted($format) {
        return $this->getFormatted($this->getRemoved(), $format);
    }

    private function getFormatted(array $array, $format) {
        $method = 'getLabel';
        if ($format === 'html') {
            $method = 'getUrl';
        }

        $formatted = array();
        foreach ($array as $element) {
            $formatted[] = $element->$method();
        }

        return implode(', ', $formatted);
    }
}

?>
