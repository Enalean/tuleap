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

        <div class="artidoc-modal-buttons">
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
                v-bind:disabled="is_submit_button_disabled || is_saving"
                data-test="submit"
                v-on:click="onFieldsSubmit()"
            >
                <i class="tlp-button-icon" v-bind:class="submit_button_icon" aria-hidden="true"></i>
                {{ $gettext("Save configuration") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CLOSE_CONFIGURATION_MODAL } from "@/components/configuration/configuration-modal";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import type { SaveFieldsConfiguration } from "@/configuration/FieldsConfigurationSaver";

const closeModal = strictInject(CLOSE_CONFIGURATION_MODAL);

const is_saving = ref<boolean>(false);
const is_error = ref<boolean>(false);
const error_message = ref<string>("");

const props = defineProps<{
    is_submit_button_disabled: boolean;
    new_selected_fields: ConfigurationField[];
    configuration_saver: SaveFieldsConfiguration;
}>();

const submit_button_icon = computed(() =>
    is_saving.value ? "fa-solid fa-spin fa-circle-notch" : "fa-solid fa-floppy-disk",
);

function onFieldsSubmit(): void {
    is_saving.value = true;
    is_error.value = false;
    props.configuration_saver.saveFieldsConfiguration(props.new_selected_fields).match(
        () => {
            is_saving.value = false;
            closeModal(true);
        },
        (fault) => {
            is_saving.value = false;
            is_error.value = true;
            error_message.value = String(fault);
        },
    );
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
