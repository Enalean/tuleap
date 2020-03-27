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
  -->

<template>
    <cancel-save-buttons
        v-if="should_display_buttons"
        v-bind:is_action_ongoing="is_action_ongoing"
        v-on:cancel="cancel"
        v-on:save="save"
    />
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import CancelSaveButtons from "./CancelSaveButtons.vue";
import { Card, TaskboardEvent } from "../../../../../../type";
import EventBus from "../../../../../../helpers/event-bus";

@Component({
    components: { CancelSaveButtons },
})
export default class EditCardButtons extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    get should_display_buttons(): boolean {
        if (this.card.is_in_edit_mode) {
            return true;
        }

        if (!this.card.remaining_effort) {
            return false;
        }

        return this.card.remaining_effort.is_in_edit_mode;
    }

    get is_action_ongoing(): boolean {
        if (this.card.is_being_saved) {
            return true;
        }

        if (!this.card.remaining_effort) {
            return false;
        }

        return this.card.remaining_effort.is_being_saved;
    }

    cancel(): void {
        EventBus.$emit(TaskboardEvent.CANCEL_CARD_EDITION, this.card);
    }

    save(): void {
        EventBus.$emit(TaskboardEvent.SAVE_CARD_EDITION, this.card);
    }
}
</script>
