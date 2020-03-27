<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div
        class="taskboard-child"
        v-bind:class="{ 'taskboard-draggable-item': !card.is_in_edit_mode }"
        v-if="card.is_open || are_closed_items_displayed"
        v-bind:data-card-id="card.id"
        v-bind:data-tracker-id="card.tracker_id"
        v-bind:draggable="!card.is_in_edit_mode"
    >
        <base-card v-bind:card="card" />
        <edit-card-buttons
            class="taskboard-card-cancel-save-buttons-for-child"
            v-bind:card="card"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { State } from "vuex-class";
import BaseCard from "./BaseCard.vue";
import { Card } from "../../../../../type";
import EditCardButtons from "./EditMode/EditCardButtons.vue";

@Component({
    components: { EditCardButtons, BaseCard },
})
export default class ChildCard extends Vue {
    @State
    readonly are_closed_items_displayed!: boolean;

    @Prop({ required: true })
    readonly card!: Card;
}
</script>
