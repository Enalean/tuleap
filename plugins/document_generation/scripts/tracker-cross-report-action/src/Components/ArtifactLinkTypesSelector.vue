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
        <label class="tlp-label">
            {{ $gettext("Link type") }}
            <select
                v-model="artifact_link_types"
                class="tlp-select"
                multiple
                v-bind:disabled="tracker_id === null || is_processing"
            >
                <option
                    v-for="art_link in current_tracker_artifact_link_types"
                    v-bind:key="art_link.shortname"
                    v-bind:value="art_link.shortname"
                >
                    {{
                        art_link.shortname === NO_TYPE_SHORTNAME
                            ? $gettext("No type")
                            : art_link.forward_label
                    }}
                </option>
            </select>
        </label>
    </div>
</template>
<script lang="ts" setup>
import { computed, watch } from "vue";
import { getTrackerCurrentlyUsedArtifactLinkTypes as getTrackerCurrentlyUsedArtifactLinkTypesFromAPI } from "../rest-querier";
import { usePromise } from "../Helpers/use-promise";
import type { TrackerUsedArtifactLinkResponse } from "@tuleap/plugin-tracker-rest-api-types/src";

const NO_TYPE_SHORTNAME = "";

const props = defineProps<{ tracker_id: number | null; artifact_link_types: string[] }>();
const emit = defineEmits<{
    (e: "update:artifact_link_types", value: string[]): void;
}>();

const default_artifact_link_types: TrackerUsedArtifactLinkResponse[] = [];
function getTrackerCurrentlyUsedArtifactLinkTypes(
    tracker_id: number | null
): Promise<TrackerUsedArtifactLinkResponse[]> {
    if (tracker_id === null) {
        return Promise.resolve(default_artifact_link_types);
    }
    return getTrackerCurrentlyUsedArtifactLinkTypesFromAPI(tracker_id);
}

const { is_processing, data: current_tracker_artifact_link_types } = usePromise(
    default_artifact_link_types,
    computed(() => getTrackerCurrentlyUsedArtifactLinkTypes(props.tracker_id))
);

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
    }
);
</script>
