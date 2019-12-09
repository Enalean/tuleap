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
        <drop-container-cell
            v-for="col of columns"
            v-bind:key="col.id"
            v-bind:column="col"
            v-bind:swimlane="swimlane"
        >
            <card-with-remaining-effort
                v-if="column.id === col.id"
                v-bind:card="swimlane.card"
                class="taskboard-cell-solo-card"
                v-bind:class="{'taskboard-draggable-item': ! swimlane.card.is_in_edit_mode}"
                v-bind:data-card-id="swimlane.card.id"
                v-bind:data-tracker-id="swimlane.card.tracker_id"
                v-bind:data-is-draggable="! swimlane.card.is_in_edit_mode"
            />
            <add-card v-if="is_add_card_rendered" v-bind:column="col" v-bind:swimlane="swimlane"/>
        </drop-container-cell>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Getter, namespace } from "vuex-class";
import { ColumnDefinition, Swimlane } from "../../../../type";
import CardWithRemainingEffort from "./Card/CardWithRemainingEffort.vue";
import SwimlaneHeader from "./Header/SwimlaneHeader.vue";
import DropContainerCell from "./Cell/DropContainerCell.vue";
import AddCard from "./Card/Add/AddCard.vue";

const column_store = namespace("column");

@Component({
    components: {
        AddCard,
        CardWithRemainingEffort,
        DropContainerCell,
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

    @Getter
    readonly can_add_in_place!: (swimlane: Swimlane) => boolean;

    get should_solo_card_be_displayed(): boolean {
        return !this.column.is_collapsed;
    }

    get is_add_card_rendered(): boolean {
        return this.can_add_in_place(this.swimlane);
    }
}
</script>
