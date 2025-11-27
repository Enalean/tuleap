<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <div class="tlp-framed">
        <h2>{{ $gettext("Fields usage") }}</h2>

        <loading-state v-if="is_loading" />

        <tracker-structure
            v-bind:root="root"
            v-if="!is_error && !is_loading && root.children.length > 0"
        />

        <empty-state v-if="!is_loading && !is_error && root.children.length === 0" />

        <error-state v-if="is_error" />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { getJSON, uri } from "@tuleap/fetch-result";
import type { TrackerResponseNoInstance } from "@tuleap/plugin-tracker-rest-api-types";
import EmptyState from "./EmptyState.vue";
import LoadingState from "./LoadingState.vue";
import ErrorState from "./ErrorState.vue";
import TrackerStructure from "./TrackerStructure.vue";
import { mapContentStructureToFields } from "../helpers/map-content-structure-to-fields";
import type { ElementWithChildren } from "../type";

const { $gettext } = useGettext();

const props = defineProps<{ tracker_id: number }>();
const is_error = ref(false);
const is_loading = ref(true);
const root = ref<ElementWithChildren>({ children: [] });

onMounted(() => {
    getJSON<Pick<TrackerResponseNoInstance, "fields" | "structure">>(
        uri`/api/v1/trackers/${props.tracker_id}`,
    ).match(
        (tracker) => {
            root.value = mapContentStructureToFields(tracker.structure, tracker.fields);
            is_loading.value = false;
        },
        () => {
            is_loading.value = false;
            is_error.value = true;
        },
    );
});
</script>
