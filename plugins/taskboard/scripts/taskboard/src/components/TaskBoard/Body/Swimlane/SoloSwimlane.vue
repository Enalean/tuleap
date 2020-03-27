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
        <swimlane-header v-bind:swimlane="swimlane" />
        <solo-swimlane-cell
            v-for="col of columns"
            v-bind:key="col.id"
            v-bind:column="col"
            v-bind:swimlane="swimlane"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { namespace } from "vuex-class";
import { ColumnDefinition, Swimlane } from "../../../../type";
import CardWithRemainingEffort from "./Card/CardWithRemainingEffort.vue";
import SwimlaneHeader from "./Header/SwimlaneHeader.vue";
import DropContainerCell from "./Cell/DropContainerCell.vue";
import { getColumnOfCard } from "../../../../helpers/list-value-to-column-mapper";
import SoloSwimlaneCell from "./Cell/SoloSwimlaneCell.vue";

const column_store = namespace("column");

@Component({
    components: {
        SoloSwimlaneCell,
        CardWithRemainingEffort,
        DropContainerCell,
        SwimlaneHeader,
    },
})
export default class SoloSwimlane extends Vue {
    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @column_store.State
    readonly columns!: Array<ColumnDefinition>;

    get should_solo_card_be_displayed(): boolean {
        return !this.column.is_collapsed;
    }

    get column(): ColumnDefinition {
        return this.getColumnOfSoloCard(this.swimlane);
    }

    getColumnOfSoloCard(swimlane: Swimlane): ColumnDefinition {
        const column = getColumnOfCard(this.columns, swimlane.card);
        if (column === undefined) {
            throw new Error("Solo card must have a mapping");
        }
        return column;
    }
}
</script>
