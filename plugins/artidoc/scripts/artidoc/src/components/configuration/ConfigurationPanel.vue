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
    <form class="tlp-pane" v-on:submit="onSubmit">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    {{ pane_title }}
                </h1>
            </div>

            <section class="tlp-pane-section">
                <tracker-selection-introductory-text
                    v-bind:selected_tracker="new_selected_tracker"
                />
                <tracker-selection
                    v-bind:allowed_trackers="allowed_trackers"
                    v-bind:selected_tracker="new_selected_tracker"
                    v-bind:is_tracker_selection_disabled="false"
                    v-on:select-tracker="onSelectTracker"
                />
            </section>

            <section class="tlp-pane-section tlp-pane-section-submit">
                <error-feedback
                    class="artidoc-configuration-feedback"
                    v-if="is_error"
                    v-bind:error_message="error_message"
                />

                <button
                    type="submit"
                    class="tlp-button-primary tlp-button-large artidoc-configuration-submit-button"
                    v-bind:disabled="is_submit_button_disabled"
                    data-test="artidoc-configuration-submit-button"
                >
                    <i
                        class="tlp-button-icon"
                        v-bind:class="submit_button_icon"
                        aria-hidden="true"
                    ></i>
                    {{ $gettext("Save configuration") }}
                </button>
            </section>
        </div>
    </form>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { useGettext } from "vue3-gettext";
import type { Option } from "@tuleap/option";
import TrackerSelectionIntroductoryText from "@/components/configuration/TrackerSelectionIntroductoryText.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import { TITLE } from "@/title-injection-key";
import TrackerSelection from "@/components/configuration/TrackerSelection.vue";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import { ALLOWED_TRACKERS } from "@/configuration/AllowedTrackersCollection";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";

const { $gettext } = useGettext();

const title = strictInject(TITLE);
const { error_message, is_error, is_saving, saveTrackerConfiguration } =
    strictInject(CONFIGURATION_STORE);
const saved_tracker = strictInject(SELECTED_TRACKER);
const allowed_trackers = strictInject(ALLOWED_TRACKERS);

const pane_title = $gettext("Configuration of %{ title }", { title });

const new_selected_tracker = ref<Option<Tracker>>(saved_tracker.value);

const is_submit_button_disabled = computed(
    () =>
        allowed_trackers.isEmpty() ||
        is_saving.value ||
        new_selected_tracker.value.mapOr(
            (tracker) => tracker.id === saved_tracker.value.mapOr((saved) => saved.id, Number.NaN),
            false,
        ),
);

const submit_button_icon = computed(() =>
    is_saving.value ? "fa-solid fa-spin fa-circle-notch" : "fa-solid fa-floppy-disk",
);

function onSubmit(event: Event): void {
    event.preventDefault();
    new_selected_tracker.value.apply(saveTrackerConfiguration);
}

function onSelectTracker(tracker: Option<Tracker>): void {
    new_selected_tracker.value = tracker;
}
</script>

<style scoped lang="scss">
.tlp-pane-section-submit {
    flex-direction: column;
    align-items: center;
}

.artidoc-configuration-feedback {
    width: 100%;
}

.artidoc-configuration-submit-button {
    width: min-content;
}
</style>
