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
                <parent-card v-bind:card="card" v-if="target_column.id === col.id"/>
            </div>
        </template>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card, ColumnDefinition, Mapping, Status } from "../../../type";
import ParentCell from "./ParentCell.vue";
import ParentCard from "../Card/ParentCard.vue";
import { State } from "vuex-class";

@Component({
    components: { ParentCell, ParentCard }
})
export default class SoloCard extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    @State
    readonly columns!: Array<ColumnDefinition>;

    get target_column(): ColumnDefinition | undefined {
        if (!this.card.status) {
            return undefined;
        }

        const status = this.card.status;
        return this.columns.find(column => this.is_status_accepted_by_column(status, column));
    }

    is_status_accepted_by_column(status: Status, column: ColumnDefinition): boolean {
        return column.mappings.some(mapping => this.is_status_part_of_mapping(status, mapping));
    }

    is_status_part_of_mapping(status: Status, mapping: Mapping): boolean {
        return (
            mapping.tracker_id === this.card.tracker_id &&
            mapping.accepts.some(list_value => list_value.id === status.id)
        );
    }
}
</script>
