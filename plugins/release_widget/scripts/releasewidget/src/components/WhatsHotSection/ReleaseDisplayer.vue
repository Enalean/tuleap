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

<template>
    <div class="project-release"
         v-bind:class="{ 'project-release-toggle-closed': !is_open }"
    >
        <release-header
            v-on:toggleReleaseDetails="toggleReleaseDetails()"
            v-bind:release-data="releaseData"
            data-test="project-release-toggle"
        />

        <div v-if="is_open" data-test="toggle_open" class="release-toggle">
            <release-badges
                v-bind:release-data="releaseData"
                data-test="display-releases-badges"
            />
            <release-description v-bind:release-data="releaseData"/>
        </div>
    </div>
</template>

<script>
import ReleaseBadges from "./ReleaseBadges.vue";
import ReleaseDescription from "./ReleaseDescription.vue";
import ReleaseHeader from "./ReleaseHeader.vue";

export default {
    name: "ReleaseDisplayer",
    components: {
        ReleaseHeader,
        ReleaseDescription,
        ReleaseBadges
    },
    props: {
        releaseData: Object
    },
    data() {
        return {
            is_open: false
        };
    },
    methods: {
        toggleReleaseDetails() {
            this.is_open = !this.is_open;
        }
    }
};
</script>
