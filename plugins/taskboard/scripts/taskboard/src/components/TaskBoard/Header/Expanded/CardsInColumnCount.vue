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
    <span class="taskboard-header-count" v-bind:class="classes">
        {{ nb_cards_in_column(column) }}
    </span>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { ColumnDefinition } from "../../../../type";
import { namespace } from "vuex-class";

const swimlane = namespace("swimlane");

@Component
export default class CardsInColumnCount extends Vue {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @swimlane.Getter
    readonly is_loading_cards!: boolean;

    @swimlane.Getter
    readonly nb_cards_in_column!: (column: ColumnDefinition) => boolean;

    get classes(): string {
        return this.is_loading_cards ? "taskboard-header-count-loading" : "";
    }
}
</script>
