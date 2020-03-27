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
            <div
                class="tlp-card tlp-skeleton-card taskboard-card-parent taskboard-card-skeleton"
            ></div>
        </div>
        <columns-skeleton
            v-for="(col, index) of columns"
            v-bind:key="col.id"
            v-bind:column_index="index"
            v-bind:column="col"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { ColumnDefinition } from "../../../../../type";
import { namespace } from "vuex-class";
import ColumnsSkeleton from "./ColumnsSkeleton.vue";

const column = namespace("column");

@Component({
    components: { ColumnsSkeleton },
})
export default class SwimlaneSkeleton extends Vue {
    @column.State
    readonly columns!: Array<ColumnDefinition>;
}
</script>
