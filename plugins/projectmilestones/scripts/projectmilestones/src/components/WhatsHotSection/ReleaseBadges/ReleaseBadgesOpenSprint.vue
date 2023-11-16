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
    <div class="project-release-badge-open-sprint">
        <div class="project-release-open-sprint-badges">
            <i class="project-release-open-sprint-badge-icon-toggle fa" />
            <div
                class="project-release-info-badge project-release-info-badge-open-sprint tlp-badge-primary"
                v-bind:class="{ 'tlp-badge-outline': isPastRelease || not_in_progress }"
                data-test="sprint-label"
            >
                <i class="fa fa-map-signs tlp-badge-icon" />
                <div class="label-open-sprints">{{ sprint_data.label }}</div>
            </div>
        </div>
        <release-buttons-description
            v-bind:release_data="sprint_data"
            class="project-release-badge-open-sprint-buttons"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { MilestoneData } from "../../../type";
import ReleaseButtonsDescription from "../ReleaseDescription/ReleaseButtonsDescription.vue";

const props = defineProps<{ sprint_data: MilestoneData; isPastRelease: boolean }>();

const not_in_progress = computed((): boolean => {
    if (props.sprint_data.start_date === null) {
        return true;
    }

    if (props.sprint_data.end_date === null) {
        return true;
    }

    const start_date = new Date(props.sprint_data.start_date);
    const end_date = new Date(props.sprint_data.end_date);
    const now = new Date();

    return !(start_date.getTime() <= now.getTime() && now.getTime() <= end_date.getTime());
});
</script>
