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
        <parent-cell v-bind:card="card"/>
        <columns-skeleton v-for="(col, index) of columns" v-bind:key="col.id" v-bind:column_index="index"/>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card, ColumnDefinition } from "../../../type";
import { State } from "vuex-class";
import ParentCell from "./ParentCell.vue";
import ColumnsSkeleton from "../ColumnsSkeleton.vue";

@Component({
    components: { ColumnsSkeleton, ParentCell }
})
export default class CardWithChildren extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    @State
    readonly columns!: Array<ColumnDefinition>;
}
</script>
