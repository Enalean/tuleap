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
        <div class="taskboard-cell">
            <div class="tlp-card tlp-skeleton-card taskboard-card-parent taskboard-card-skeleton"></div>
        </div>
        <div class="taskboard-cell" v-for="(col, index) of columns" v-bind:key="col.id">
            <div class="tlp-card tlp-skeleton-card taskboard-card-skeleton" v-for="i in nb_skeletons(index)" v-bind:key="i"></div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { ColumnDefinition } from "../../type";
import { State } from "vuex-class";

const nb_skeletons_per_column = [4, 1, 2, 3, 1];

@Component
export default class SwimlaneSkeleton extends Vue {
    @State
    readonly columns!: Array<ColumnDefinition>;

    nb_skeletons(index: number): number {
        return nb_skeletons_per_column[index % nb_skeletons_per_column.length];
    }
}
</script>
