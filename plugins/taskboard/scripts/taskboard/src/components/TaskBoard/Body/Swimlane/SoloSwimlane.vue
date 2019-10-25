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
        <template v-if="! target_column">
            <parent-cell v-bind:swimlane="swimlane"/>
            <cell-for-solo-card v-for="col of columns" v-bind:key="col.id" v-bind:column="col"/>
        </template>
        <template v-else>
            <swimlane-header v-bind:swimlane="swimlane"/>
            <cell-for-solo-card v-for="col of columns" v-bind:key="col.id" v-bind:column="col">
                <solo-card v-if="target_column.id === col.id" v-bind:card="swimlane.card"/>
            </cell-for-solo-card>
        </template>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { ColumnDefinition, Swimlane } from "../../../../type";
import ParentCell from "./ParentCell.vue";
import SoloCard from "./SoloCard.vue";
import { getColumnOfCard } from "../../../../helpers/list-value-to-column-mapper";
import SwimlaneHeader from "./Header/SwimlaneHeader.vue";

import { State } from "vuex-class";
import CellForSoloCard from "./CellForSoloCard.vue";

@Component({
    components: {
        CellForSoloCard,
        ParentCell,
        SoloCard,
        SwimlaneHeader
    }
})
export default class SoloSwimlane extends Vue {
    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @State
    readonly columns!: Array<ColumnDefinition>;

    get target_column(): ColumnDefinition | undefined {
        return getColumnOfCard(this.columns, this.swimlane.card);
    }

    get should_solo_card_be_displayed(): boolean {
        if (!this.target_column) {
            return true;
        }

        return !this.target_column.is_collapsed;
    }
}
</script>
