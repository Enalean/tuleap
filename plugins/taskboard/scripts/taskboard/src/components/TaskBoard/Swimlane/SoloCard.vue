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
    <div class="taskboard-swimlane">
        <template v-if="! target_column">
            <parent-cell v-bind:card="card"/>
            <div class="taskboard-cell" v-for="col of columns" v-bind:key="col.id"></div>
        </template>
        <template v-else>
            <div class="taskboard-cell"></div>
            <div class="taskboard-cell" v-for="col of columns" v-bind:key="col.id">
                <solo-card-cell v-if="target_column.id === col.id" v-bind:card="card"/>
            </div>
        </template>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card, ColumnDefinition, Mapping, MappedListValue } from "../../../type";
import ParentCell from "./ParentCell.vue";
import SoloCardCell from "./SoloCardCell.vue";
import ParentCard from "../Card/ParentCard.vue";
import ParentCardRemainingEffort from "../Card/ParentCardRemainingEffort.vue";

import { State } from "vuex-class";

@Component({
    components: { ParentCell, ParentCard, ParentCardRemainingEffort, SoloCardCell }
})
export default class SoloCard extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    @State
    readonly columns!: Array<ColumnDefinition>;

    get target_column(): ColumnDefinition | undefined {
        if (!this.card.mapped_list_value) {
            return undefined;
        }

        const mapped_list_value = this.card.mapped_list_value;
        return this.columns.find(column =>
            this.is_status_accepted_by_column(mapped_list_value, column)
        );
    }

    is_status_accepted_by_column(
        mapped_list_value: MappedListValue,
        column: ColumnDefinition
    ): boolean {
        return column.mappings.some(mapping =>
            this.is_status_part_of_mapping(mapped_list_value, mapping)
        );
    }

    is_status_part_of_mapping(mapped_list_value: MappedListValue, mapping: Mapping): boolean {
        return (
            mapping.tracker_id === this.card.tracker_id &&
            mapping.accepts.some(list_value => list_value.id === mapped_list_value.id)
        );
    }
}
</script>
