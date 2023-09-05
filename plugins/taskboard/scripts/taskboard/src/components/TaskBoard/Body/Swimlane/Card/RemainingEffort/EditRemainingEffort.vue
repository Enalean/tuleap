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
    />
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Card } from "../../../../../../type";
import { TaskboardEvent } from "../../../../../../type";
import { namespace } from "vuex-class";
import type { NewRemainingEffortPayload } from "../../../../../../store/swimlane/card/type";
import EventBus from "../../../../../../helpers/event-bus";
import { autoFocusAutoSelect } from "../../../../../../helpers/autofocus-autoselect";

const swimlane = namespace("swimlane");

const MINIMAL_WIDTH_IN_PX = 30;
const MAXIMAL_WIDTH_IN_PX = 60;
const NB_PX_PER_CHAR = 10;

@Component
export default class EditRemainingEffort extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    @swimlane.Action
    readonly saveRemainingEffort!: (
        new_remaining_effort: NewRemainingEffortPayload,
    ) => Promise<void>;

    @swimlane.Mutation
    readonly removeRemainingEffortFromEditMode!: (card: Card) => void;

    value = "";

    get classes(): Array<string> {
        let width = NB_PX_PER_CHAR * this.value.length;

        if (width <= MINIMAL_WIDTH_IN_PX) {
            return [];
        } else if (width > MAXIMAL_WIDTH_IN_PX) {
            width = MAXIMAL_WIDTH_IN_PX;
        }

        return [`taskboard-card-remaining-effort-input-width-${width}`];
    }

    mounted(): void {
        this.initValue();

        const input = this.$el;
        if (!(input instanceof HTMLInputElement)) {
            throw new Error("The component is not a HTML input");
        }
        autoFocusAutoSelect(input);

        EventBus.$on(TaskboardEvent.CANCEL_CARD_EDITION, this.cancelButtonCallback);
        EventBus.$on(TaskboardEvent.SAVE_CARD_EDITION, this.saveButtonCallback);
    }

    beforeDestroy(): void {
        EventBus.$off(TaskboardEvent.CANCEL_CARD_EDITION, this.cancelButtonCallback);
        EventBus.$off(TaskboardEvent.SAVE_CARD_EDITION, this.saveButtonCallback);
    }

    initValue(): void {
        if (this.card.remaining_effort) {
            this.value = String(this.card.remaining_effort.value);
        }
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

    cancel(): void {
        this.removeRemainingEffortFromEditMode(this.card);
    }

    save(keyup_event?: KeyboardEvent): void {
        const input = this.$el;
        if (!(input instanceof HTMLInputElement)) {
            throw new Error("The component is not a HTML input");
        }
        if (!input.checkValidity()) {
            // force :invalid pseudo-class
            input.blur();
            input.focus();
            return;
        }

        if (!this.card.remaining_effort) {
            return;
        }

        if (this.card.remaining_effort.is_being_saved) {
            return;
        }

        const value = Number.parseFloat(input.value);
        if (value === this.card.remaining_effort.value) {
            this.cancel();
            return;
        }

        const new_remaining_effort: NewRemainingEffortPayload = { card: this.card, value };
        this.saveRemainingEffort(new_remaining_effort);

        this.$emit("editor-closed");

        if (keyup_event) {
            keyup_event.stopPropagation();
        }
    }
}
</script>
