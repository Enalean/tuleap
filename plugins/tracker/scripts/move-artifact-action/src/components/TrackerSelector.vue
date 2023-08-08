<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div class="move-artifact-tracker-selector-section">
        <label
            data-test="tracker-selector-label"
            for="move-artifact-tracker-selector"
            v-bind:title="selector_title"
        >
            {{ $gettext("Destination tracker") }}
            <span class="highlight">*</span>
        </label>
        <select
            id="move-artifact-tracker-selector"
            name="move-artifact-tracker-selector"
            data-test="move-artifact-tracker-selector"
            v-model="tracker_id"
            v-on:change="saveSelectedTrackerId(tracker_id)"
            ref="move_artifact_tracker_selector"
        >
            <option
                v-for="tracker of tracker_options"
                v-bind:key="tracker.id"
                v-bind:value="tracker.id"
                v-bind:disabled="tracker.disabled"
            >
                {{ tracker.label }}
            </option>
        </select>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, computed } from "vue";
import { useGettext } from "vue3-gettext";

import { useState, useMutations } from "vuex-composition-helpers";
import { createListPicker } from "@tuleap/list-picker";
import type { ListPicker } from "@tuleap/list-picker";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { RootState } from "../store/types";
import type { RootMutations } from "../store/mutations";
import { TrackerSelectorOptions } from "../helpers/TrackerSelectorOptions";
import type { TrackerSelectorOption } from "../helpers/TrackerSelectorOptions";
import { TRACKER_ID } from "../injection-symbols";

const { $gettext } = useGettext();

const { saveSelectedTrackerId } = useMutations<Pick<RootMutations, "saveSelectedTrackerId">>([
    "saveSelectedTrackerId",
]);
const { trackers } = useState<Pick<RootState, "trackers">>(["trackers"]);

const tracker_options = computed((): TrackerSelectorOption[] =>
    TrackerSelectorOptions.fromTrackers(trackers.value, strictInject(TRACKER_ID))
);
const tracker_id = ref(null);
const list_picker = ref<ListPicker | undefined>();
const move_artifact_tracker_selector = ref<HTMLSelectElement>();

const selector_title = computed(() =>
    tracker_options.value.some(({ disabled }) => disabled)
        ? $gettext("An artifact cannot be moved in the same tracker")
        : ""
);

onMounted(() => {
    if (!(move_artifact_tracker_selector.value instanceof HTMLSelectElement)) {
        return;
    }

    list_picker.value = createListPicker(move_artifact_tracker_selector.value, {
        locale: document.body.dataset.userLocale,
        is_filterable: true,
        placeholder: $gettext("Choose tracker..."),
    });
});

onBeforeUnmount(() => {
    list_picker.value?.destroy();
});
</script>
