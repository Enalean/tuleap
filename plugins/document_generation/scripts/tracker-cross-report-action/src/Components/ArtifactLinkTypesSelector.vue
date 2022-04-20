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
        <Multiselect
            v-bind:id="select_element_id"
            v-model="artifact_link_types"
            class="multiselect-art-link-types"
            mode="tags"
            v-bind:no-results-text="$gettext('All usable link types have been selected')"
            v-bind:no-options-text="$gettext('No usable link types have been found')"
            v-bind:options="multiselect_options"
            v-bind:disabled="tracker_id === null || is_processing"
        />
    </div>
</template>
<script lang="ts" setup>
import { computed, watch } from "vue";
import { getTrackerCurrentlyUsedArtifactLinkTypes as getTrackerCurrentlyUsedArtifactLinkTypesFromAPI } from "../rest-querier";
import { usePromise } from "../Helpers/use-promise";
import type { TrackerUsedArtifactLinkResponse } from "@tuleap/plugin-tracker-rest-api-types";
import Multiselect from "@vueform/multiselect";
import { useGettext } from "vue3-gettext";
import { generateElementID } from "../Helpers/id-element-generator";

const NO_TYPE_SHORTNAME = "";

const props = defineProps<{ tracker_id: number | null; artifact_link_types: string[] }>();
const emit = defineEmits<{
    (e: "update:artifact_link_types", value: string[]): void;
}>();

const select_element_id = generateElementID();

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

const { $gettext } = useGettext();

interface MultiSelectOptions {
    [key: string]: string;
}

const multiselect_options = computed((): MultiSelectOptions => {
    const options: MultiSelectOptions = {};

    for (const art_link of current_tracker_artifact_link_types.value) {
        options[art_link.shortname] =
            art_link.shortname === NO_TYPE_SHORTNAME ? $gettext("No type") : art_link.forward_label;
    }

    return options;
});

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
<style src="@vueform/multiselect/themes/default.css"></style>
<style lang="scss" scoped>
.multiselect-art-link-types {
    --ms-tag-color: var(--tlp-main-color);
    --ms-tag-bg: var(--tlp-main-color-transparent-90);
    --ms-tag-line-height: var(--tlp-badge-line-height);
    --ms-tag-font-weight: var(--tlp-badge-font-weight);
    --ms-tag-font-size: var(--tlp-badge-font-size);
    --ms-tag-radius: 15px;
    --ms-ring-color: var(--tlp-main-color);
    --ms-border-color: var(--tlp-form-element-border-color);
    --ms-caret-color: #696969;
    --ms-clear-color: #696969;
    --ms-bg-disabled: var(--tlp-white-color);

    &.is-active {
        --ms-border-color: var(--tlp-main-color);

        box-shadow: var(--tlp-shadow-focus);
    }

    &.is-disabled {
        opacity: 0.5;
    }

    :deep(.multiselect-caret) {
        transform: none;
        mask-image: url("@tuleap/tlp/src/images/field-double-arrows.svg");
    }
}
</style>
