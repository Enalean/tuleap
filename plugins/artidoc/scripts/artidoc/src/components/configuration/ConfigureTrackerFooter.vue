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
    <div class="tlp-modal-footer">
        <error-feedback v-if="is_error" v-bind:error_message="error_message" />
        <success-feedback v-if="is_success" />

        <div class="artidoc-modal-buttons">
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                v-if="is_success"
                v-on:click="closeModal(true)"
                data-test="close-modal-after-success"
            >
                {{ $gettext("Close") }}
            </button>

            <template v-else>
                <button
                    type="button"
                    class="tlp-button-primary tlp-button-outline tlp-modal-action"
                    data-test="cancel-modal-button"
                    v-on:click="closeModal(false)"
                >
                    {{ $gettext("Cancel") }}
                </button>
                <button
                    type="button"
                    class="tlp-button-primary tlp-modal-action"
                    v-bind:disabled="is_submit_button_disabled"
                    data-test="submit"
                    v-on:click="onSubmit()"
                >
                    <i
                        class="tlp-button-icon"
                        v-bind:class="submit_button_icon"
                        aria-hidden="true"
                    ></i>
                    {{ $gettext("Save configuration") }}
                </button>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CLOSE_CONFIGURATION_MODAL } from "@/components/configuration/configuration-modal";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import type { SaveTrackerConfiguration } from "@/configuration/TrackerConfigurationSaver";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import { ALLOWED_TRACKERS } from "@/configuration/AllowedTrackersCollection";
import type { Option } from "@tuleap/option";

const closeModal = strictInject(CLOSE_CONFIGURATION_MODAL);
const allowed_trackers = strictInject(ALLOWED_TRACKERS);
const saved_tracker = strictInject(SELECTED_TRACKER);

const is_saving = ref<boolean>(false);
const is_error = ref<boolean>(false);
const is_success = ref<boolean>(false);
const error_message = ref<string>("");

const props = defineProps<{
    new_selected_tracker: Option<Tracker>;
    configuration_saver: SaveTrackerConfiguration;
}>();

const emit = defineEmits<{
    (e: "after-save", successful: boolean): void;
}>();

const submit_button_icon = computed(() =>
    is_saving.value ? "fa-solid fa-spin fa-circle-notch" : "fa-solid fa-floppy-disk",
);
const is_submit_button_disabled = computed(
    () =>
        allowed_trackers.isEmpty() ||
        is_saving.value ||
        props.new_selected_tracker.mapOr(
            (tracker) => tracker.id === saved_tracker.value.mapOr((saved) => saved.id, Number.NaN),
            false,
        ),
);

function onSubmit(): void {
    props.new_selected_tracker.apply((tracker) => {
        is_saving.value = true;
        is_error.value = false;
        is_success.value = false;
        props.configuration_saver.saveTrackerConfiguration(tracker).match(
            () => {
                is_saving.value = false;
                is_success.value = true;
                emit("after-save", true);
            },
            (fault) => {
                is_saving.value = false;
                is_error.value = true;
                error_message.value = String(fault);
                emit("after-save", false);
            },
        );
    });
}
</script>

<style scoped lang="scss">
.tlp-modal-footer {
    flex-direction: column;
}

.artidoc-modal-buttons {
    display: flex;
    justify-content: flex-end;
    margin: var(--tlp-medium-spacing) 0 0;
}
</style>
