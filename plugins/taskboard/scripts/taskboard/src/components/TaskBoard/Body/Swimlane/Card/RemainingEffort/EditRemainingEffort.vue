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
    <input type="text"
           v-bind:class="classes"
           v-bind:style="style"
           v-model="value"
           v-on:keyup.enter="save"
           pattern="[0-9]*(\.[0-9]+)?"
           v-bind:aria-label="$gettext('New remaining effort')"
    >
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card, Event } from "../../../../../../type";
import { namespace } from "vuex-class";
import { NewRemainingEffortPayload } from "../../../../../../store/swimlane/card/type";
import EventBus from "../../../../../../helpers/event-bus";

const swimlane = namespace("swimlane");

const MINIMAL_WIDTH_IN_PX = 30;
const NB_PX_PER_CHAR = 10;

@Component
export default class EditRemainingEffort extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    @swimlane.Action
    readonly saveRemainingEffort!: (
        new_remaining_effort: NewRemainingEffortPayload
    ) => Promise<void>;

    value = "";

    get classes(): string {
        return `taskboard-card-remaining-effort-input-${this.card.color}`;
    }

    get style(): string {
        const width = NB_PX_PER_CHAR * (this.value.length + 1);

        if (width <= MINIMAL_WIDTH_IN_PX) {
            return "";
        }

        return `width: ${width}px;`;
    }

    mounted(): void {
        this.initValue();

        const input = this.$el as HTMLInputElement;
        input.focus();
        setTimeout(() => {
            input.select();
        }, 10);

        document.addEventListener("keyup", this.keyup);
        EventBus.$on(Event.CANCEL_CARD_EDITION, this.cancelButtonCallback);
        EventBus.$on(Event.SAVE_CARD_EDITION, this.saveButtonCallback);
    }

    beforeDestroy(): void {
        EventBus.$off(Event.CANCEL_CARD_EDITION, this.cancelButtonCallback);
        EventBus.$off(Event.SAVE_CARD_EDITION, this.saveButtonCallback);
        document.removeEventListener("keyup", this.keyup);
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

    keyup(event: KeyboardEvent): void {
        if (event.key === "Escape") {
            this.cancel();
        }
    }

    cancel(): void {
        if (this.card.remaining_effort) {
            this.card.remaining_effort.is_in_edit_mode = false;
        }
    }

    save(): void {
        const input = this.$el as HTMLInputElement;
        if (!input.checkValidity()) {
            // force :invalid pseudo-class
            input.blur();
            input.focus();
            return;
        }

        if (!this.card.remaining_effort) {
            return;
        }

        const value = Number.parseFloat(input.value);
        if (value === this.card.remaining_effort.value) {
            this.cancel();
            return;
        }

        const new_remaining_effort: NewRemainingEffortPayload = { card: this.card, value };
        this.saveRemainingEffort(new_remaining_effort);
    }
}
</script>
