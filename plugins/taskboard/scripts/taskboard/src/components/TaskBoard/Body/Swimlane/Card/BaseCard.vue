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
    <div class="taskboard-card" v-bind:class="additional_classnames">
        <span class="taskboard-card-edit-trigger" v-on:click="switchToEditMode">
            <i class="fa fa-pencil"></i>
        </span>
        <div class="taskboard-card-content">
            <card-xref-label v-bind:card="card" v-bind:label="label"/>
            <div class="taskboard-card-info">
                <slot name="initial_effort"/>
                <card-assignees v-bind:assignees="card.assignees"/>
            </div>
        </div>
        <edit-label v-model="label" v-if="card.is_in_edit_mode" v-on:save="save"/>
        <div class="taskboard-card-accessibility" v-if="show_accessibility_pattern"></div>
        <slot name="remaining_effort"/>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import CardXrefLabel from "./CardXrefLabel.vue";
import CardAssignees from "./CardAssignees.vue";
import { Card, TaskboardEvent } from "../../../../../type";
import { namespace } from "vuex-class";
import EventBus from "../../../../../helpers/event-bus";
import EditLabel from "./EditMode/Label/EditLabel.vue";
import { NewCardPayload } from "../../../../../store/swimlane/card/type";

const user = namespace("user");
const swimlane = namespace("swimlane");

@Component({
    components: {
        EditLabel,
        CardXrefLabel,
        CardAssignees
    }
})
export default class BaseCard extends Vue {
    @user.State
    readonly user_has_accessibility_mode!: boolean;

    @Prop({ required: true })
    readonly card!: Card;

    @swimlane.Mutation
    readonly addCardToEditMode!: (card: Card) => void;

    @swimlane.Mutation
    readonly removeCardFromEditMode!: (card: Card) => void;

    @swimlane.Mutation
    readonly setCardHaveAlreadyBeenShown!: (card: Card) => void;

    @swimlane.Action
    readonly saveCard!: (payload: NewCardPayload) => Promise<void>;

    add_show_class = true;
    label = "";

    mounted(): void {
        this.label = this.card.label;
        setTimeout(() => {
            this.add_show_class = false;
        }, 500);
        EventBus.$on(TaskboardEvent.CANCEL_CARD_EDITION, this.cancelButtonCallback);
        EventBus.$on(TaskboardEvent.SAVE_CARD_EDITION, this.saveButtonCallback);
    }

    beforeDestroy(): void {
        EventBus.$off(TaskboardEvent.CANCEL_CARD_EDITION, this.cancelButtonCallback);
        EventBus.$off(TaskboardEvent.SAVE_CARD_EDITION, this.saveButtonCallback);
    }

    cancelButtonCallback(card: Card): void {
        if (card.id === this.card.id) {
            this.cancel();
        }
    }

    saveButtonCallback(card: Card): void {
        if (card.id === this.card.id) {
            this.save();
        }
    }

    save(): void {
        if (this.label === this.card.label) {
            this.cancel();
            return;
        }

        const payload: NewCardPayload = { card: this.card, label: this.label };
        this.saveCard(payload);
    }

    cancel(): void {
        this.removeCardFromEditMode(this.card);
        this.label = this.card.label;
    }

    switchToEditMode(): void {
        if (this.card.is_in_edit_mode) {
            return;
        }

        if (this.card.is_being_saved) {
            return;
        }

        this.addCardToEditMode(this.card);
    }

    get additional_classnames(): string {
        const classnames = [`taskboard-card-${this.card.color}`];

        if (this.card.background_color) {
            classnames.push(`taskboard-card-background-${this.card.background_color}`);
        }

        if (this.show_accessibility_pattern) {
            classnames.push("taskboard-card-with-accessibility");
        }

        if (this.add_show_class && !this.card.has_already_been_shown) {
            classnames.push("taskboard-card-show");
            this.setCardHaveAlreadyBeenShown(this.card);
        }

        if (this.card.is_in_edit_mode) {
            classnames.push("taskboard-card-edit-mode");
        } else if (this.card.is_being_saved) {
            classnames.push("taskboard-card-is-being-saved");
        } else if (this.card.is_just_saved) {
            classnames.push("taskboard-card-is-just-saved");
        }

        if (!this.card.is_being_saved) {
            classnames.push("taskboard-card-editable");
        }

        return classnames.join(" ");
    }

    get show_accessibility_pattern(): boolean {
        return this.user_has_accessibility_mode && this.card.background_color.length > 0;
    }
}
</script>
