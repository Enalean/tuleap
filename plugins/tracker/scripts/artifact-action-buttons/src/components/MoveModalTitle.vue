<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <h3 class="modal-title" id="modal-move-artifact-choose-trackers">
        <i class="fa fa-share"></i>
        <translate class="modal-move-artifact-icon-title">Move</translate>
        <span v-bind:class="artifact_badge_class">
            {{ artifact_badge_tracker_name }} #{{ artifact_badge_artifact_id }}
        </span>
    </h3>
</template>

<script>
import { getTrackerName, getTrackerColor, getArtifactId } from "../from-tracker-presenter.js";
export default {
    name: "MoveModalTitle",
    computed: {
        artifact_badge_class() {
            return getTrackerColor() + " xref-in-title";
        },
        artifact_badge_tracker_name() {
            return getTrackerName();
        },
        artifact_badge_artifact_id() {
            return getArtifactId();
        },
    },
};
</script>
