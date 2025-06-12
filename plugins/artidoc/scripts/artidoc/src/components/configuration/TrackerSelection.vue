<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
        v-bind:class="{ 'tlp-form-element-error': no_allowed_trackers }"
        data-test="artidoc-configuration-form-element-trackers"
    >
        <label class="tlp-label" for="artidoc-configuration-tracker">
            {{ $gettext("Tracker") }}
            <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
        </label>
        <select
            id="artidoc-configuration-tracker"
            data-test="artidoc-configuration-tracker"
            class="tlp-select tlp-select-adjusted"
            required
            v-bind:disabled="is_tracker_selection_disabled"
            v-model="new_selected_tracker"
        >
            <option value="" disabled>
                {{ $gettext("Choose a tracker") }}
            </option>
            <option
                v-for="tracker in allowed_trackers"
                v-bind:key="tracker.id"
                v-bind:value="tracker"
            >
                {{ tracker.label }}
            </option>
        </select>
        <p class="tlp-text-danger" v-if="no_allowed_trackers">
            {{ $gettext("There isn't any suitable trackers in this project") }}
            <i class="fa-regular fa-face-frown" aria-hidden="true"></i>
        </p>
        <p
            v-if="!is_tracker_selection_disabled"
            class="tlp-text-info"
            data-test="information-message"
        >
            {{
                $gettext(
                    "If you choose another tracker, your current fields will be replaced and lost.",
                )
            }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { useGettext } from "vue3-gettext";
import { Option } from "@tuleap/option";
import type { AllowedTrackersCollection, Tracker } from "@/configuration/AllowedTrackersCollection";

const { $gettext } = useGettext();

const props = defineProps<{
    allowed_trackers: AllowedTrackersCollection;
    selected_tracker: Option<Tracker>;
    is_tracker_selection_disabled: boolean;
}>();

const new_selected_tracker = ref<Tracker | null>(props.selected_tracker.unwrapOr(null));

const emit = defineEmits<{
    (e: "select-tracker", tracker: Option<Tracker>): void;
}>();

watch(new_selected_tracker, () => {
    emit("select-tracker", Option.fromNullable(new_selected_tracker.value));
});

const no_allowed_trackers = props.allowed_trackers.isEmpty();
</script>
