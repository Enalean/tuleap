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
  -->

<template>
    <cancel-save-buttons
        v-if="should_display_buttons"
        v-bind:is_action_ongoing="is_action_ongoing"
        v-on:cancel="cancel"
        v-on:save="save"
    />
</template>
<script setup lang="ts">
import { computed } from "vue";
import emitter from "../../../../../../helpers/emitter";
import type { Card } from "../../../../../../type";
import CancelSaveButtons from "./CancelSaveButtons.vue";

const props = defineProps<{
    card: Card;
}>();

const emit = defineEmits<{
    (e: "editor-closed"): void;
}>();

const should_display_buttons = computed((): boolean => {
    if (props.card.is_in_edit_mode) {
        return true;
    }

    if (!props.card.remaining_effort) {
        return false;
    }

    return props.card.remaining_effort.is_in_edit_mode;
});

const is_action_ongoing = computed((): boolean => {
    if (props.card.is_being_saved) {
        return true;
    }

    if (!props.card.remaining_effort) {
        return false;
    }

    return props.card.remaining_effort.is_being_saved;
});

function cancel(): void {
    emitter.emit("cancel-card-edition", props.card);
    emit("editor-closed");
}

function save(): void {
    emitter.emit("save-card-edition", props.card);
    emit("editor-closed");
}
</script>
