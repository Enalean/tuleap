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
                v-model="label"
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

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Mutation, namespace } from "vuex-class";
import AddButton from "./AddButton.vue";
import LabelEditor from "../Editor/Label/LabelEditor.vue";
import type { ColumnDefinition, Swimlane } from "../../../../../../type";
import type { NewCardPayload } from "../../../../../../store/swimlane/card/type";
import CancelSaveButtons from "../EditMode/CancelSaveButtons.vue";

const swimlane = namespace("swimlane");

@Component({
    components: { LabelEditor, AddButton, CancelSaveButtons },
})
export default class AddCard extends Vue {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @Prop({ required: false, default: "" })
    readonly button_label!: string;

    @swimlane.Action
    readonly addCard!: (payload: NewCardPayload) => Promise<void>;

    @swimlane.State
    readonly is_card_creation_blocked_due_to_ongoing_creation!: boolean;

    private is_in_add_mode = false;
    private label = "";

    @Mutation
    readonly setIsACellAddingInPlace!: () => void;

    @Mutation
    readonly setBacklogItemsHaveChildren!: () => void;

    @Mutation
    readonly clearIsACellAddingInPlace!: () => void;

    cancel(): void {
        if (this.is_in_add_mode) {
            this.is_in_add_mode = false;
            this.clearIsACellAddingInPlace();
        }
    }

    switchToAddMode(): void {
        this.is_in_add_mode = true;
        this.setIsACellAddingInPlace();
    }

    save(): void {
        if (this.label === "") {
            return;
        }

        const payload: NewCardPayload = {
            swimlane: this.swimlane,
            column: this.column,
            label: this.label,
        };
        this.addCard(payload);
        //add info in state that children are defined
        this.setBacklogItemsHaveChildren();
        this.deferResetOfLabel();
    }

    deferResetOfLabel(): void {
        setTimeout(() => {
            this.label = "";
        }, 10);
    }
}
</script>
