<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <div class="tlp-label">
        <span v-bind:class="{ 'in-success': in_success }">
            <span
                contenteditable="true"
                spellcheck="false"
                v-on:input="input"
                v-on:keydown.enter.prevent="updateLabel"
                ref="input_element"
                class="input-element"
                v-bind:class="{
                    'in-edition': in_edition,
                    'in-error': in_error,
                }"
                data-test="input"
                >{{ label }}</span
            >
            <i
                class="fa-solid fa-check editing-status-icon"
                aria-hidden="true"
                v-if="in_success"
                data-test="success"
            ></i>
        </span>
        <i
            class="fa-solid fa-tlp-enter-key editing-status-icon"
            role="img"
            v-if="in_edition && !is_updating"
            v-bind:title="$gettext(`Press Enter to save`)"
            data-test="press-enter-to-save"
        ></i>
        <i
            class="fa-solid fa-circle-notch fa-spin editing-status-icon"
            aria-hidden="true"
            v-if="is_updating"
            data-test="updating"
        ></i>
        <i
            class="fa-solid fa-asterisk"
            aria-hidden="true"
            v-if="field.required"
            data-test="required"
        ></i>
    </div>
    <div
        class="tlp-text-danger error-message"
        v-if="error_message.length > 0"
        data-test="error-message"
    >
        {{ error_message }}
    </div>
</template>

<script setup lang="ts">
import { ref, useTemplateRef } from "vue";
import { patchJSON, uri } from "@tuleap/fetch-result";
import type { BaseFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import type { Fault } from "@tuleap/fault";

const props = defineProps<{
    field: BaseFieldStructure;
}>();

const label = ref(props.field.label);
const input_element = useTemplateRef<HTMLElement>("input_element");
const in_edition = ref(false);
const in_error = ref(false);
const in_success = ref(false);
const is_updating = ref(false);
const error_message = ref("");

function updateLabel(): void {
    if (input_element.value) {
        const new_title = input_element.value.innerText.trim();
        if (new_title !== "" && new_title !== label.value) {
            is_updating.value = true;
            patchJSON(uri`/api/tracker_fields/${props.field.field_id}`, {
                label: new_title,
            }).match(
                () => {
                    label.value = new_title;
                    if (input_element.value) {
                        input_element.value.innerText = new_title;
                    }
                    in_edition.value = false;
                    in_error.value = false;
                    is_updating.value = false;
                    error_message.value = "";

                    in_success.value = true;
                    removeSuccessStatusAfterDelay();
                },
                (fault: Fault) => {
                    is_updating.value = false;
                    in_error.value = true;
                    error_message.value = String(fault);
                },
            );
        }
    }
}

function removeSuccessStatusAfterDelay(): void {
    setTimeout(() => (in_success.value = false), 2000);
}

function input(): void {
    if (input_element.value) {
        const new_title = input_element.value.innerText;
        in_edition.value = new_title !== label.value;
        in_error.value = new_title.trim() === "";
    }
}
</script>

<style lang="scss" scoped>
.input-element {
    display: inline-block;

    // Make sure that label for tlp-property fields (eg lud, subby, …) have content editable cursor instead of default
    cursor: initial;
}

.in-edition {
    height: 1rem;
    min-height: 1rem;
    box-shadow: var(--tlp-shadow-focus);
}

.in-error {
    box-shadow: var(--tlp-shadow-focus-error);
}

@keyframes success {
    from {
        background: var(--tlp-success-color-lighter-80);
    }

    to {
        background: transparent;
    }
}

.in-success {
    animation: 2s ease-in-out success;
}

.editing-status-icon {
    margin: 0 0 0 var(--tlp-small-spacing);
}

.error-message {
    // Reset text transform so that error message is not in uppercase for fieldsets label
    text-transform: none;
}
</style>
