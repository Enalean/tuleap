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
    <div class="taskboard-swimlane" v-if="should_solo_card_be_displayed">
        <swimlane-header v-bind:swimlane="swimlane"/>
        <cell-for-solo-card
            v-for="col of columns"
            v-bind:key="col.id"
            v-bind:column="col"
            v-bind:data-swimlane-id="swimlane.card.id"
            v-bind:data-column-id="col.id"
            v-bind:data-accepted-trackers-ids="accepted_trackers_ids(col)"
        >
            <card-with-remaining-effort
                v-if="column.id === col.id"
                v-bind:card="swimlane.card"
                class="taskboard-cell-solo-card taskboard-draggable-item"
                v-bind:data-card-id="swimlane.card.id"
                v-bind:data-tracker-id="swimlane.card.tracker_id"
            />
        </cell-for-solo-card>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { namespace } from "vuex-class";
import { ColumnDefinition, Swimlane } from "../../../../type";
import CardWithRemainingEffort from "./Card/CardWithRemainingEffort.vue";
import SwimlaneHeader from "./Header/SwimlaneHeader.vue";
import CellForSoloCard from "./CellForSoloCard.vue";

const column_store = namespace("column");

@Component({
    components: {
        CardWithRemainingEffort,
        CellForSoloCard,
        SwimlaneHeader
    }
})
export default class SoloSwimlane extends Vue {
    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @column_store.State
    readonly columns!: Array<ColumnDefinition>;

    @column_store.Getter
    readonly accepted_trackers_ids!: (column: ColumnDefinition) => number[];

    get should_solo_card_be_displayed(): boolean {
        return !this.column.is_collapsed;
    }
}
</script>
