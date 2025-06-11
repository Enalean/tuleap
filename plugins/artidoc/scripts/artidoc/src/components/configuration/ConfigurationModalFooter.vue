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
        <success-feedback v-if="is_success && current_tab === TRACKER_SELECTION_TAB" />

        <div class="artidoc-modal-buttons">
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                v-if="is_success"
                v-on:click="closeModal()"
                data-test="close-modal-after-success"
            >
                {{ $gettext("Close") }}
            </button>

            <template v-else>
                <button
                    type="button"
                    class="tlp-button-primary tlp-button-outline tlp-modal-action"
                    data-test="cancel-modal-button"
                    v-on:click="closeModal()"
                >
                    {{ $gettext("Cancel") }}
                </button>
                <button
                    type="button"
                    class="tlp-button-primary tlp-modal-action"
                    v-bind:disabled="is_submit_button_disabled"
                    data-test="submit"
                    v-on:click="on_save_callback()"
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
import { computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    TRACKER_SELECTION_TAB,
    CLOSE_CONFIGURATION_MODAL,
} from "@/components/configuration/configuration-modal";
import type { ConfigurationTab } from "@/components/configuration/configuration-modal";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";

const closeModal = strictInject(CLOSE_CONFIGURATION_MODAL);

const { error_message, is_error, is_saving, is_success } = strictInject(CONFIGURATION_STORE);

defineProps<{
    on_save_callback(): void;
    current_tab: ConfigurationTab;
    is_submit_button_disabled: boolean;
}>();

const submit_button_icon = computed(() =>
    is_saving.value ? "fa-solid fa-spin fa-circle-notch" : "fa-solid fa-floppy-disk",
);
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
