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
    <div class="taskboard-header" v-bind:class="classes">
        <collapse-button v-bind:column="column" />
        <span class="taskboard-header-label" data-test="label">{{ column.label }}</span>
        <cards-in-column-count v-bind:column="column" />
        <wrong-color-popover v-if="should_popover_be_displayed" v-bind:color="column.color" />
    </div>
</template>
<script lang="ts">
import { Component, Mixins } from "vue-property-decorator";
import WrongColorPopover from "./WrongColorPopover.vue";
import { namespace } from "vuex-class";
import CollapseButton from "./CollapseButton.vue";
import CardsInColumnCount from "./CardsInColumnCount.vue";
import HeaderCellMixin from "../header-cell-mixin";

const user = namespace("user");

const DEFAULT_COLOR = "#F8F8F8";

@Component({
    components: { CardsInColumnCount, CollapseButton, WrongColorPopover },
})
export default class ExpandedHeaderCell extends Mixins(HeaderCellMixin) {
    @user.State
    readonly user_is_admin!: boolean;

    get is_default_color(): boolean {
        return this.column.color === DEFAULT_COLOR;
    }

    get should_popover_be_displayed(): boolean {
        return this.user_is_admin && this.is_rgb_color && !this.is_default_color;
    }
}
</script>
