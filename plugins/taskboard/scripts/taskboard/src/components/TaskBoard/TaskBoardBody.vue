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
    <div class="taskboard-body">
        <div class="taskboard-swimlane" v-for="swimlane of swimlanes" v-bind:key="swimlane.card.id">
            <div class="taskboard-cell"><parent-card v-bind:card="swimlane.card"/></div>
            <div class="taskboard-cell" v-for="col of columns" v-bind:key="col.id"></div>
        </div>
        <swimlane-skeleton v-if="is_loading_swimlanes"/>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace, State } from "vuex-class";
import { ColumnDefinition, Swimlane } from "../../type";
import ParentCard from "./Card/ParentCard.vue";
import SwimlaneSkeleton from "./SwimlaneSkeleton.vue";

const swimlane = namespace("swimlane");

@Component({
    components: { SwimlaneSkeleton, ParentCard }
})
export default class TaskBoardBody extends Vue {
    @swimlane.State
    readonly swimlanes!: Array<Swimlane>;

    @State
    readonly columns!: Array<ColumnDefinition>;

    @swimlane.State
    readonly is_loading_swimlanes!: boolean;

    @swimlane.Action
    loadSwimlanes!: () => void;

    created(): void {
        this.loadSwimlanes();
    }
}
</script>
