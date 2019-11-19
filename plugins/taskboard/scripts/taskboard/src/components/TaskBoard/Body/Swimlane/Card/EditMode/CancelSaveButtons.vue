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
    <div class="taskboard-card-cancel-save-buttons" v-if="should_display_buttons">
        <button type="button"
                class="tlp-button tlp-button-primary tlp-button-small taskboard-card-save-button"
                v-on:click="save"
                data-test="save"
        >
            <i class="fa fa-tlp-enter-key tlp-button-icon"></i>
            <translate>Save</translate>
        </button>
        <button type="button"
                class="tlp-button tlp-button-primary tlp-button-outline tlp-button-small taskboard-card-cancel-button"
                v-on:click="cancel"
                data-test="cancel"
        >
            <i class="fa fa-tlp-esc-key tlp-button-icon"></i>
            <translate>Cancel</translate>
        </button>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card, Event } from "../../../../../../type";
import EventBus from "../../../../../../helpers/event-bus";

@Component
export default class CancelSaveButtons extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    get should_display_buttons(): boolean {
        if (!this.card.remaining_effort) {
            return false;
        }

        return this.card.remaining_effort.is_in_edit_mode;
    }

    cancel(): void {
        EventBus.$emit(Event.CANCEL_CARD_EDITION, this.card);
    }

    save(): void {
        EventBus.$emit(Event.SAVE_CARD_EDITION, this.card);
    }
}
</script>
