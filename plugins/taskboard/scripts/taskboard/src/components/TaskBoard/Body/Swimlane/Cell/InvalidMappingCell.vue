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
    <div class="taskboard-cell"
         v-bind:class="classes"
         v-on:mouseenter="mouseEntersCollapsedColumn"
         v-on:mouseout="mouseLeavesCollapsedColumn"
         v-on:click="expandCollapsedColumn"
    ></div>
</template>

<script lang="ts">
import { Component, Mixins } from "vue-property-decorator";
import HoveringStateForCollapsedColumnMixin from "./hovering-state-for-collapsed-column-mixin";
import ExpandCollapsedColumnMixin from "./expand-collapsed-column-mixin";
import ClassesForCollapsedColumnMixin from "./classes-for-collapsed-column-mixin";

@Component({})
export default class InvalidMappingCell extends Mixins(
    HoveringStateForCollapsedColumnMixin,
    ExpandCollapsedColumnMixin,
    ClassesForCollapsedColumnMixin
) {}
</script>
