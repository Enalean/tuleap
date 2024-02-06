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
  -
  -->

<template>
    <section>
        <div
            v-if="root_store.has_rest_error"
            class="tlp-alert-danger"
            data-test="show-error-message"
        >
            {{ error }}
        </div>
        <div v-else-if="root_store.is_loading" class="release-loader" data-test="is-loading"></div>
        <div v-else>
            <div
                class="project-release-widget-content"
                data-test="widget-content-project-milestones"
            >
                <div v-if="display_empty_state">
                    <roadmap-empty-state-section />
                </div>
                <div v-else>
                    <roadmap-section
                        v-bind:label_tracker_planning="root_store.label_tracker_planning"
                    />
                    <whats-hot-section />
                    <past-section
                        v-bind:label_tracker_planning="root_store.label_tracker_planning"
                    />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import WhatsHotSection from "./WhatsHotSection/WhatsHotSection.vue";
import RoadmapSection from "./RoadmapSection/RoadmapSection.vue";
import { computed } from "vue";
import PastSection from "./PastSection/PastSection.vue";
import RoadmapEmptyStateSection from "./ProjectMilestonesEmpty/RoadmapEmptyStateSection.vue";
import { useStore } from "../stores/root";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const { $gettext } = useGettext();
const root_store = useStore();

root_store.getMilestones();

const error = computed((): string => {
    return root_store.error_message || $gettext("Oops, an error occurred!");
});

const display_empty_state = computed((): boolean => {
    return (
        root_store.nb_backlog_items === 0 &&
        root_store.nb_upcoming_releases === 0 &&
        root_store.current_milestones.length === 0 &&
        root_store.nb_past_releases === 0
    );
});
</script>
