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
        <div class="taskboard-card-content">
            <card-xref-label v-bind:card="card" v-bind:label="label" />
            <card-info v-bind:card="card" v-bind:tracker="tracker" v-model="assignees">
                <template #initial_effort>
                    <slot name="initial_effort" />
                </template>
            </card-info>
        </div>
        <button
            v-if="can_user_update_card"
            class="taskboard-card-edit-trigger"
            v-on:click="switchToEditMode"
            data-test="card-edit-button"
            type="button"
            v-bind:title="$gettext('Edit card')"
            data-shortcut="edit-card"
        >
            <i class="fas fa-pencil-alt" aria-hidden="true"></i>
        </button>
        <label-editor v-model="label" v-if="card.is_in_edit_mode" v-on:save="save" />
        <div class="taskboard-card-accessibility" v-if="show_accessibility_pattern"></div>
        <slot name="remaining_effort" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import CardXrefLabel from "./CardXrefLabel.vue";
import type { Card, Tracker, User } from "../../../../../type";
import { TaskboardEvent } from "../../../../../type";
import { namespace, Getter } from "vuex-class";
import EventBus from "../../../../../helpers/event-bus";
import type { UpdateCardPayload } from "../../../../../store/swimlane/card/type";
import LabelEditor from "./Editor/Label/LabelEditor.vue";
import CardInfo from "./CardInfo.vue";
import { haveAssigneesChanged } from "../../../../../helpers/have-assignees-changed";
import { scrollToItemIfNeeded } from "../../../../../helpers/scroll-to-item";

const user = namespace("user");
const swimlane = namespace("swimlane");
const fullscreen = namespace("fullscreen");

@Component({
    components: {
        CardInfo,
        LabelEditor,
        CardXrefLabel,
    },
})
export default class BaseCard extends Vue {
    @user.State
    readonly user_has_accessibility_mode!: boolean;

    @Prop({ required: true })
    readonly card!: Card;

    @Getter
    readonly tracker_of_card!: (card: Card) => Tracker;

    @swimlane.Mutation
    readonly addCardToEditMode!: (card: Card) => void;

    @swimlane.Mutation
    readonly removeCardFromEditMode!: (card: Card) => void;

    @swimlane.Action
    readonly saveCard!: (payload: UpdateCardPayload) => Promise<void>;

    @fullscreen.State
    readonly is_taskboard_in_fullscreen_mode!: boolean;

    label = "";
    assignees: User[] = [];

    mounted(): void {
        this.label = this.card.label;
        this.assignees = this.card.assignees;
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
        if (!this.is_label_changed && !haveAssigneesChanged(this.card.assignees, this.assignees)) {
            this.cancel();
            return;
        }

        const payload: UpdateCardPayload = {
            card: this.card,
            label: this.label,
            assignees: this.assignees,
            tracker: this.tracker,
        };
        this.saveCard(payload);

        this.$emit("editor-closed");
    }

    cancel(): void {
        this.removeCardFromEditMode(this.card);
        this.label = this.card.label;
        this.$emit("editor-closed");
    }

    switchToEditMode(): void {
        if (this.card.is_in_edit_mode) {
            return;
        }

        if (this.card.is_being_saved) {
            return;
        }

        this.addCardToEditMode(this.card);

        setTimeout((): void => {
            let fullscreen_element = null;

            if (this.is_taskboard_in_fullscreen_mode) {
                fullscreen_element = document.querySelector(".taskboard");
            }

            scrollToItemIfNeeded(this.$el, fullscreen_element);
        }, 100);
    }

    get is_label_changed(): boolean {
        return this.label !== this.card.label;
    }

    get additional_classnames(): string {
        const classnames = [`taskboard-card-${this.card.color}`];

        if (this.card.background_color) {
            classnames.push(`taskboard-card-background-${this.card.background_color}`);
        }

        if (this.show_accessibility_pattern) {
            classnames.push("taskboard-card-with-accessibility");
        }

        if (this.card.is_in_edit_mode) {
            classnames.push("taskboard-card-edit-mode");
        } else if (this.card.is_being_saved) {
            classnames.push("taskboard-card-is-being-saved");
        } else if (this.card.is_just_saved) {
            classnames.push("taskboard-card-is-just-saved");
        }

        if (this.can_user_update_card) {
            classnames.push("taskboard-card-editable");
        }

        return classnames.join(" ");
    }

    get can_user_update_card(): boolean {
        return this.tracker.title_field !== null && !this.card.is_being_saved;
    }

    get show_accessibility_pattern(): boolean {
        return this.user_has_accessibility_mode && this.card.background_color.length > 0;
    }

    get tracker(): Tracker {
        return this.tracker_of_card(this.card);
    }
}
</script>
