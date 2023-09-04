<!--
  - Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
    <div
        class="tlp-form-element"
        v-bind:class="{ 'tlp-form-element-disabled': tracker_id === null }"
    >
        <label class="tlp-label" v-bind:for="select_element_id">
            {{ $gettext("Link type") }}
        </label>
        <select
            ref="art_link_select"
            multiple
            v-bind:id="select_element_id"
            v-model="artifact_link_types"
            v-bind:disabled="tracker_id === null || is_processing"
        >
            <option
                v-for="link in current_tracker_artifact_link_types"
                v-bind:key="link.shortname"
                v-bind:value="link.shortname"
            >
                {{ getArtifactLinkLabel(link) }}
            </option>
        </select>
    </div>
</template>
<script lang="ts" setup>
import { computed, ref, watch } from "vue";
import { getTrackerCurrentlyUsedArtifactLinkTypes as getTrackerCurrentlyUsedArtifactLinkTypesFromAPI } from "../rest-querier";
import { usePromise } from "../Helpers/use-promise";
import type { TrackerUsedArtifactLinkResponse } from "@tuleap/plugin-tracker-rest-api-types";
import { useGettext } from "vue3-gettext";
import { generateElementID } from "../Helpers/id-element-generator";
import { useListPicker } from "../Helpers/use-list-picker";

const NO_TYPE_SHORTNAME = "";

const props = defineProps<{ tracker_id: number | null; artifact_link_types: string[] }>();
const emit = defineEmits<{
    (e: "update:artifact_link_types", value: string[]): void;
}>();

const select_element_id = generateElementID();

const default_artifact_link_types: TrackerUsedArtifactLinkResponse[] = [];
function getTrackerCurrentlyUsedArtifactLinkTypes(
    tracker_id: number | null,
): Promise<TrackerUsedArtifactLinkResponse[]> {
    if (tracker_id === null) {
        return Promise.resolve(default_artifact_link_types);
    }
    return getTrackerCurrentlyUsedArtifactLinkTypesFromAPI(tracker_id);
}

const { is_processing, data: current_tracker_artifact_link_types } = usePromise(
    default_artifact_link_types,
    computed(() => getTrackerCurrentlyUsedArtifactLinkTypes(props.tracker_id)),
);

const { $gettext } = useGettext();

function getArtifactLinkLabel(art_link: TrackerUsedArtifactLinkResponse): string {
    if (art_link.shortname === NO_TYPE_SHORTNAME) {
        return $gettext("No type");
    }
    return art_link.forward_label;
}

const artifact_link_types = computed({
    get(): string[] {
        return props.artifact_link_types;
    },
    set(value: string[]) {
        emit("update:artifact_link_types", value);
    },
});

watch(
    () => current_tracker_artifact_link_types.value,
    (art_link_types) => {
        artifact_link_types.value = art_link_types.map((type) => type.shortname);
    },
);

const art_link_select = ref<HTMLSelectElement>();
useListPicker(art_link_select, {
    placeholder: $gettext("No usable link types have been found"),
});
</script>
