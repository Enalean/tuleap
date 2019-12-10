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
    <form class="taskboard-add-card-form">
        <template v-if="is_in_add_mode">
            <label-editor v-model="label" v-on:save="save" v-bind:readonly="readonly"/>
            <cancel-save-buttons
                v-on:cancel="cancel"
                v-on:save="save"
            />
        </template>
        <add-button v-if="!is_in_add_mode" v-on:click="switchToAddMode"/>
    </form>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Mutation } from "vuex-class";
import AddButton from "./AddButton.vue";
import LabelEditor from "../Editor/Label/LabelEditor.vue";
import { ColumnDefinition, Swimlane } from "../../../../../../type";
import { namespace } from "vuex-class";
import { NewCardPayload } from "../../../../../../store/swimlane/card/type";
import CancelSaveButtons from "../EditMode/CancelSaveButtons.vue";

const swimlane = namespace("swimlane");

const NAVBAR_HEIGHT_AND_HEADER_HEIGHT_IN_PX = 95;

@Component({
    components: { LabelEditor, AddButton, CancelSaveButtons }
})
export default class AddCard extends Vue {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @swimlane.Action
    readonly addCard!: (payload: NewCardPayload) => Promise<void>;

    @swimlane.State
    readonly is_card_creation_blocked_due_to_ongoing_creation!: boolean;

    private is_in_add_mode = false;
    private label = "";

    @Mutation
    readonly setIsACellAddingInPlace!: () => void;

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

        const current_top = this.$el.getBoundingClientRect().top;
        if (current_top < NAVBAR_HEIGHT_AND_HEADER_HEIGHT_IN_PX) {
            const new_top = window.scrollY + current_top - NAVBAR_HEIGHT_AND_HEADER_HEIGHT_IN_PX;
            setTimeout(() => window.scrollTo({ top: new_top, behavior: "smooth" }), 10);
        }
    }

    save(): void {
        const payload: NewCardPayload = {
            swimlane: this.swimlane,
            column: this.column,
            label: this.label
        };
        this.addCard(payload);
        this.deferResetOfLabel();
    }

    deferResetOfLabel(): void {
        setTimeout(() => {
            this.label = "";
        }, 10);
    }

    get readonly(): boolean {
        return this.is_card_creation_blocked_due_to_ongoing_creation;
    }
}
</script>
