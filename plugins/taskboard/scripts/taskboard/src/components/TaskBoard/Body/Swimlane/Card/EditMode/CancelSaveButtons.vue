<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="taskboard-card-cancel-save-buttons" data-not-drag-handle="true">
        <button
            type="button"
            class="tlp-button tlp-button-primary tlp-button-outline tlp-button-small taskboard-card-cancel-button"
            v-bind:disabled="is_action_ongoing"
            v-on:click="cancel"
            data-test="cancel"
        >
            <i class="fa fa-tlp-esc-key tlp-button-icon" aria-hidden="true"></i>
            {{ $gettext("Cancel") }}
        </button>
        <button
            type="button"
            class="tlp-button tlp-button-primary tlp-button-small taskboard-card-save-button"
            v-bind:disabled="is_action_ongoing"
            v-on:click="save"
            data-test="save"
        >
            <i
                class="fa tlp-button-icon"
                v-bind:class="save_icon"
                aria-hidden="true"
                data-test="save-icon"
            ></i>
            {{ $gettext("Save") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount } from "vue";
import emitter from "../../../../../../helpers/emitter";

const props = withDefaults(
    defineProps<{
        is_action_ongoing: boolean;
    }>(),
    {
        is_action_ongoing: false,
    },
);

const emit = defineEmits<{
    (e: "cancel"): void;
    (e: "save"): void;
}>();

const save_icon = computed((): string => {
    return props.is_action_ongoing ? "fa-circle-o-notch fa-spin" : "fa-tlp-enter-key";
});

onMounted((): void => {
    emitter.on("esc-key-pressed", cancel);
});

onBeforeUnmount((): void => {
    emitter.off("esc-key-pressed", cancel);
});

function cancel(): void {
    if (!props.is_action_ongoing) {
        emit("cancel");
    }
}

function save(): void {
    if (!props.is_action_ongoing) {
        emit("save");
    }
}
</script>
