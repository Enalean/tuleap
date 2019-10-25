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
        <expand-button v-bind:column="column"/>
        <collapse-button v-bind:column="column"/>
        <span class="taskboard-header-label" v-if="!column.is_collapsed">{{ column.label }}</span>
        <wrong-color-popover v-if="should_popover_be_displayed" v-bind:color="column.color"/>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { ColumnDefinition } from "../../../type";
import WrongColorPopover from "./WrongColorPopover.vue";
import { namespace } from "vuex-class";
import CollapseButton from "./CollapseButton.vue";
import ExpandButton from "./ExpandButton.vue";

const user = namespace("user");

const DEFAULT_COLOR = "#F8F8F8";

@Component({
    components: { ExpandButton, CollapseButton, WrongColorPopover }
})
export default class TaskBoardHeaderCell extends Vue {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @user.State
    readonly user_is_admin!: boolean;

    get classes(): string {
        const classes = [];

        if (this.column.is_collapsed) {
            classes.push("taskboard-header-collapsed");
        }

        if (!this.is_rgb_color && this.column.color) {
            classes.push("taskboard-header-" + this.column.color);
        }

        return classes.join(" ");
    }

    get is_rgb_color(): boolean {
        return this.column.color.charAt(0) === "#";
    }

    get is_default_color(): boolean {
        return this.column.color === DEFAULT_COLOR;
    }

    get should_popover_be_displayed(): boolean {
        return (
            this.user_is_admin &&
            this.is_rgb_color &&
            !this.is_default_color &&
            !this.column.is_collapsed
        );
    }
}
</script>
