<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <div class="tlp-modal-body">
        <tracker-selection-introductory-text v-bind:selected_tracker="new_selected_tracker" />
        <tracker-selection
            v-bind:allowed_trackers="allowed_trackers"
            v-bind:selected_tracker="new_selected_tracker"
            v-bind:is_tracker_selection_disabled="is_success"
            v-on:select-tracker="onSelectTracker"
        />
    </div>
    <configure-tracker-footer
        v-on:after-save="onAfterSave"
        v-bind:new_selected_tracker="new_selected_tracker"
        v-bind:configuration_saver="configuration_saver"
    />
</template>

<script setup lang="ts">
import { ref } from "vue";
import type { Option } from "@tuleap/option";
import { strictInject } from "@tuleap/vue-strict-inject";
import TrackerSelectionIntroductoryText from "@/components/configuration/TrackerSelectionIntroductoryText.vue";
import TrackerSelection from "@/components/configuration/TrackerSelection.vue";
import ConfigureTrackerFooter from "@/components/configuration/ConfigureTrackerFooter.vue";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import { ALLOWED_TRACKERS } from "@/configuration/AllowedTrackersCollection";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import { buildTrackerConfigurationSaver } from "@/configuration/TrackerConfigurationSaver";
import { SELECTED_FIELDS } from "@/configuration/SelectedFieldsCollection";
import { AVAILABLE_FIELDS } from "@/configuration/AvailableFieldsCollection";
import { DOCUMENT_ID } from "@/document-id-injection-key";

const document_id = strictInject(DOCUMENT_ID);
const saved_tracker = strictInject(SELECTED_TRACKER);
const selected_fields = strictInject(SELECTED_FIELDS);
const available_fields = strictInject(AVAILABLE_FIELDS);
const allowed_trackers = strictInject(ALLOWED_TRACKERS);

const new_selected_tracker = ref<Option<Tracker>>(saved_tracker.value);
const is_success = ref<boolean>(false);

const configuration_saver = buildTrackerConfigurationSaver(
    document_id,
    saved_tracker,
    selected_fields,
    available_fields,
);

function onSelectTracker(tracker: Option<Tracker>): void {
    new_selected_tracker.value = tracker;
}

function onAfterSave(successful: boolean): void {
    is_success.value = successful;
}
</script>
