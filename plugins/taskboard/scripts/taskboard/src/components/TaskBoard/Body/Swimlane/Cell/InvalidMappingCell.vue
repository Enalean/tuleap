<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
  -->

<template>
    <div
        class="taskboard-cell"
        v-bind:class="classes"
        v-on:pointerenter="pointerEntersCollapsedColumn"
        v-on:pointerleave="pointerLeavesCollapsedColumn"
        v-on:click="expandCollapsedColumn"
        data-navigation="cell"
    >
        <add-card v-if="is_add_card_rendered" v-bind:column="column" v-bind:swimlane="swimlane" />
    </div>
</template>

<script lang="ts">
import { Component, Mixins, Prop } from "vue-property-decorator";
import { Getter } from "vuex-class";
import HoveringStateForCollapsedColumnMixin from "./hovering-state-for-collapsed-column-mixin";
import ExpandCollapsedColumnMixin from "./expand-collapsed-column-mixin";
import ClassesForCollapsedColumnMixin from "./classes-for-collapsed-column-mixin";
import AddCard from "../Card/Add/AddCard.vue";
import type { Swimlane } from "../../../../../type";

@Component({
    components: { AddCard },
})
export default class InvalidMappingCell extends Mixins(
    HoveringStateForCollapsedColumnMixin,
    ExpandCollapsedColumnMixin,
    ClassesForCollapsedColumnMixin,
) {
    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @Getter
    readonly can_add_in_place!: (swimlane: Swimlane) => boolean;

    get is_add_card_rendered(): boolean {
        return this.can_add_in_place(this.swimlane);
    }
}
</script>
