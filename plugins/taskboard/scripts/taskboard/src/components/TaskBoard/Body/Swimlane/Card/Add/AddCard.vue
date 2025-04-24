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
    <form class="taskboard-add-card-form" data-test="add-in-place-form">
        <div class="taskboard-add-card-form-editor-container" v-if="is_in_add_mode">
            <label-editor
                v-bind:value="label"
                v-on:input="label = $event"
                v-on:save="save"
                v-bind:readonly="is_card_creation_blocked_due_to_ongoing_creation"
            />
            <cancel-save-buttons
                v-on:cancel="cancel"
                v-on:save="save"
                v-bind:is_action_ongoing="is_card_creation_blocked_due_to_ongoing_creation"
            />
        </div>
        <add-button
            v-bind:is_in_add_mode="is_in_add_mode"
            v-on:click="switchToAddMode"
            v-bind:label="button_label"
        />
    </form>
</template>
<script setup lang="ts">
import { ref } from "vue";
import { useNamespacedActions, useNamespacedState, useMutations } from "vuex-composition-helpers";
import type { NewCardPayload } from "../../../../../../store/swimlane/card/type";
import type { ColumnDefinition, Swimlane } from "../../../../../../type";
import LabelEditor from "../Editor/Label/LabelEditor.vue";
import CancelSaveButtons from "../EditMode/CancelSaveButtons.vue";
import AddButton from "./AddButton.vue";

const { addCard } = useNamespacedActions("swimlane", ["addCard"]);

const { is_card_creation_blocked_due_to_ongoing_creation } = useNamespacedState("swimlane", [
    "is_card_creation_blocked_due_to_ongoing_creation",
]);

const { setIsACellAddingInPlace, setBacklogItemsHaveChildren, clearIsACellAddingInPlace } =
    useMutations([
        "setIsACellAddingInPlace",
        "setBacklogItemsHaveChildren",
        "clearIsACellAddingInPlace",
    ]);

const props = withDefaults(
    defineProps<{
        column: ColumnDefinition;
        swimlane: Swimlane;
        button_label: string;
    }>(),
    {
        button_label: "",
    },
);

const is_in_add_mode = ref(false);
const label = ref("");

function cancel(): void {
    if (is_in_add_mode.value) {
        is_in_add_mode.value = false;
        clearIsACellAddingInPlace();
    }
}

function switchToAddMode(): void {
    is_in_add_mode.value = true;
    setIsACellAddingInPlace();
}

function save(): void {
    if (label.value === "") {
        return;
    }

    const payload: NewCardPayload = {
        swimlane: props.swimlane,
        column: props.column,
        label: label.value,
    };
    addCard(payload);
    //add info in state that children are defined
    setBacklogItemsHaveChildren();
    deferResetOfLabel();
}

function deferResetOfLabel(): void {
    setTimeout(() => {
        label.value = "";
    }, 10);
}

defineExpose({ label });
</script>
