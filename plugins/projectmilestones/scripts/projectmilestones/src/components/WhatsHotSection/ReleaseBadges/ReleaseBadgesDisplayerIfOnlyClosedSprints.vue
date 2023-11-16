<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="project-release-infos-badges">
        <release-badges-closed-sprints
            v-if="root_store.user_can_view_sub_milestones_planning"
            v-bind:release_data="release_data"
        />
        <release-others-badges v-bind:release_data="release_data" />
    </div>
</template>

<script setup lang="ts">
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
import type { MilestoneData } from "../../../type";
import { useStore } from "../../../stores/root";

const root_store = useStore();

defineProps<{ release_data: MilestoneData }>();
</script>
