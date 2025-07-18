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
    <input
        type="text"
        class="taskboard-card-remaining-effort-input"
        v-bind:class="classes"
        v-model="value"
        v-on:keyup.enter="save"
        pattern="[0-9]*(\.[0-9]+)?"
        v-bind:aria-label="$gettext('New remaining effort')"
        ref="input_element"
        data-test="remaining-effort"
    />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { useNamespacedActions, useNamespacedMutations } from "vuex-composition-helpers";
import type { Card } from "../../../../../../type";
import type { NewRemainingEffortPayload } from "../../../../../../store/swimlane/card/type";
import emitter from "../../../../../../helpers/emitter";
import { autoFocusAutoSelect } from "../../../../../../helpers/autofocus-autoselect";

const { saveRemainingEffort } = useNamespacedActions("swimlane", ["saveRemainingEffort"]);
const { removeRemainingEffortFromEditMode } = useNamespacedMutations("swimlane", [
    "removeRemainingEffortFromEditMode",
]);

const MINIMAL_WIDTH_IN_PX = 30;
const MAXIMAL_WIDTH_IN_PX = 60;
const NB_PX_PER_CHAR = 10;

const props = defineProps<{
    card: Card;
}>();

const emit = defineEmits<{
    (e: "editor-closed"): void;
}>();

const value = ref("");
const input_element = ref();

const classes = computed((): Array<string> => {
    let width = NB_PX_PER_CHAR * value.value.length;

    if (width <= MINIMAL_WIDTH_IN_PX) {
        return [];
    } else if (width > MAXIMAL_WIDTH_IN_PX) {
        width = MAXIMAL_WIDTH_IN_PX;
    }

    return [`taskboard-card-remaining-effort-input-width-${width}`];
});

onMounted((): void => {
    initValue();

    if (!(input_element.value instanceof HTMLInputElement)) {
        throw new Error("The component is not a HTML input");
    }
    autoFocusAutoSelect(input_element.value);

    emitter.on("cancel-card-edition", cancelButtonCallback);
    emitter.on("save-card-edition", saveButtonCallback);
});

onBeforeUnmount((): void => {
    emitter.off("cancel-card-edition", cancelButtonCallback);
    emitter.off("save-card-edition", saveButtonCallback);
});

function initValue(): void {
    if (props.card.remaining_effort) {
        value.value = String(props.card.remaining_effort.value);
    }
}

function cancelButtonCallback(card: Card): void {
    if (card.id === props.card.id) {
        cancel();
    }
}

function saveButtonCallback(card: Card): void {
    if (card.id === props.card.id) {
        save();
    }
}

function cancel(): void {
    removeRemainingEffortFromEditMode(props.card);
}

function save(keyup_event?: KeyboardEvent): void {
    if (!(input_element.value instanceof HTMLInputElement)) {
        throw new Error("The component is not a HTML input");
    }
    if (!input_element.value.checkValidity()) {
        // force :invalid pseudo-class
        input_element.value.blur();
        input_element.value.focus();
        return;
    }

    if (!props.card.remaining_effort) {
        return;
    }

    if (props.card.remaining_effort.is_being_saved) {
        return;
    }

    const value = Number.parseFloat(input_element.value.value);
    if (value === props.card.remaining_effort.value) {
        cancel();
        return;
    }

    const new_remaining_effort: NewRemainingEffortPayload = { card: props.card, value };
    saveRemainingEffort(new_remaining_effort);

    emit("editor-closed");

    if (keyup_event) {
        keyup_event.stopPropagation();
    }
}
</script>
