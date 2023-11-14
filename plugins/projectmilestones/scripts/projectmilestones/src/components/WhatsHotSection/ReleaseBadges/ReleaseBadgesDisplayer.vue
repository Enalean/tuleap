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
    <div>
        <release-badges-displayer-if-open-sprints
            v-if="open_sprints_exist"
            v-bind:release_data="release_data"
            v-bind:is-open="isOpen"
            v-bind:is-past-release="isPastRelease"
        />
        <release-badges-displayer-if-only-closed-sprints
            v-else
            v-bind:release_data="release_data"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { MilestoneData } from "../../../type";
import ReleaseBadgesDisplayerIfOpenSprints from "./ReleaseBadgesDisplayerIfOpenSprints.vue";
import ReleaseBadgesDisplayerIfOnlyClosedSprints from "./ReleaseBadgesDisplayerIfOnlyClosedSprints.vue";
import { openSprintsExist } from "../../../helpers/milestones-sprints-helper";

const props = defineProps<{
    release_data: MilestoneData;
    isOpen: boolean;
    isPastRelease: boolean;
}>();

const open_sprints_exist = computed((): boolean => {
    return openSprintsExist(props.release_data);
});
</script>
