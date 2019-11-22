<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

<template functional>
    <div class="release-number-artifact">
        <div
            v-for="tracker in props.release_data.number_of_artifact_by_trackers"
            v-bind:key="tracker.id"
            class="release-number-artifacts-tracker"
            v-bind:class="['release-number-artifacts-tracker-' + tracker.color_name]"
            data-test="color-name-tracker"
        >
            <span class="release-number-artifacts-value" data-test="total-artifact-tracker">{{ tracker.total_artifact }}</span>
            <span class="release-number-artifacts-text" data-test="artifact-tracker-name">{{ tracker.label }}</span>
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";

@Component
export default class ReleaseDescriptionBadgesTracker extends Vue {}
</script>
