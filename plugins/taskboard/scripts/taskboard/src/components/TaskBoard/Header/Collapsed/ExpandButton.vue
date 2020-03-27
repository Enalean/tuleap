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
    <span class="taskboard-header-expand-column" v-bind:title="title">
        <i
            class="fa fa-plus-square"
            role="button"
            tabindex="0"
            v-bind:aria-label="title"
            v-on:click="expandColumn(column)"
            data-test="button"
        ></i>
    </span>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { ColumnDefinition } from "../../../../type";
import { namespace } from "vuex-class";

const column_store = namespace("column");

@Component
export default class ExpandButton extends Vue {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @column_store.Action
    readonly expandColumn!: (column: ColumnDefinition) => void;

    get title(): string {
        return this.$gettextInterpolate(this.$gettext('Expand "%{ label }" column'), {
            label: this.column.label,
        });
    }
}
</script>
