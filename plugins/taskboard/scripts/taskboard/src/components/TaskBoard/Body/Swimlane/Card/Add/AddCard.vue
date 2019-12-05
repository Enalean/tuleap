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
        <label-editor v-model="label" v-if="is_in_add_mode" v-on:save="save"/>
        <add-button v-if="!is_in_add_mode" v-on:click="switchToAddMode"/>
    </form>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import AddButton from "./AddButton.vue";
import LabelEditor from "../Editor/Label/LabelEditor.vue";
import EventBus from "../../../../../../helpers/event-bus";
import { Card, ColumnDefinition, TaskboardEvent } from "../../../../../../type";
import { namespace } from "vuex-class";
import { NewCardPayload } from "../../../../../../store/swimlane/card/type";

const swimlane = namespace("swimlane");

@Component({
    components: { LabelEditor, AddButton }
})
export default class AddCard extends Vue {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @Prop({ required: true })
    readonly parent!: Card;

    @swimlane.Action
    readonly addCard!: (payload: NewCardPayload) => Promise<void>;

    private is_in_add_mode = false;
    private label = "";

    mounted(): void {
        EventBus.$on(TaskboardEvent.ESC_KEY_PRESSED, this.cancel);
    }

    beforeDestroy(): void {
        EventBus.$off(TaskboardEvent.ESC_KEY_PRESSED, this.cancel);
    }

    cancel(): void {
        this.is_in_add_mode = false;
    }

    switchToAddMode(): void {
        this.is_in_add_mode = true;
    }

    save(): void {
        const payload: NewCardPayload = {
            parent: this.parent,
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
}
</script>
