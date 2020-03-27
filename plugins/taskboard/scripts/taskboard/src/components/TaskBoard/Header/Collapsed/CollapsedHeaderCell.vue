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
    <div class="taskboard-header taskboard-header-collapsed" v-bind:class="classes">
        <expand-button v-bind:column="column" />
        <collapsed-label v-bind:column="column" />
    </div>
</template>
<script lang="ts">
import { Component, Mixins } from "vue-property-decorator";
import ExpandButton from "./ExpandButton.vue";
import HeaderCellMixin from "../header-cell-mixin";
import CardsInColumnCount from "../Expanded/CardsInColumnCount.vue";
import CollapsedLabel from "./CollapsedLabel.vue";

@Component({
    components: { CollapsedLabel, CardsInColumnCount, ExpandButton },
})
export default class CollapsedHeaderCell extends Mixins(HeaderCellMixin) {
    get classes(): string {
        const classes = [...this.classes_as_array];

        if (this.column.has_hover) {
            classes.push("taskboard-header-collapsed-show-label");
        }

        return classes.join(" ");
    }
}
</script>
