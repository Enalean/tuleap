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
    <drop-container-cell v-bind:column="column" v-bind:swimlane="swimlane">
        <card-with-remaining-effort
            v-if="is_card_rendered"
            v-bind:key="column.id"
            v-bind:card="swimlane.card"
            class="taskboard-cell-solo-card"
            v-bind:class="{ 'taskboard-draggable-item': !swimlane.card.is_in_edit_mode }"
            v-bind:data-card-id="swimlane.card.id"
            v-bind:data-tracker-id="swimlane.card.tracker_id"
            v-bind:draggable="!swimlane.card.is_in_edit_mode"
        />
    </drop-container-cell>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { ColumnDefinition, Swimlane } from "../../../../../type";
import DropContainerCell from "./DropContainerCell.vue";
import CardWithRemainingEffort from "../Card/CardWithRemainingEffort.vue";
import { isStatusAcceptedByColumn } from "../../../../../helpers/list-value-to-column-mapper";

@Component({
    components: { DropContainerCell, CardWithRemainingEffort },
})
export default class SoloSwimlaneCell extends Vue {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    get is_card_rendered(): boolean {
        return isStatusAcceptedByColumn(this.swimlane.card, this.column);
    }
}
</script>
