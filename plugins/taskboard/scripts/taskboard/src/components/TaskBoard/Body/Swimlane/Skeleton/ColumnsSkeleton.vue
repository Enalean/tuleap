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
    <div class="taskboard-cell" v-bind:class="classes">
        <template v-if="!column.is_collapsed">
            <card-skeleton v-for="i in nb_skeletons" v-bind:key="i" />
        </template>
    </div>
</template>

<script lang="ts">
import { Component, Mixins, Prop } from "vue-property-decorator";
import SkeletonMixin from "./skeleton-mixin";
import CardSkeleton from "./CardSkeleton.vue";
import { ColumnDefinition } from "../../../../../type";

@Component({
    components: { CardSkeleton },
})
export default class ColumnsSkeleton extends Mixins(SkeletonMixin) {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    get classes(): string {
        return this.column.is_collapsed ? "taskboard-cell-collapsed" : "";
    }
}
</script>
