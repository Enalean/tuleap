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
    <div
        class="taskboard-cell taskboard-cell-swimlane-header"
        v-bind:class="taskboard_cell_swimlane_header_classes"
    >
        <i
            class="fa fa-minus-square taskboard-swimlane-toggle"
            v-bind:class="additional_classnames"
            role="button"
            v-bind:title="title"
            v-on:click="collapseSwimlane(swimlane)"
        ></i>
        <slot />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Swimlane } from "../../../../../type";
import { namespace } from "vuex-class";

const fullscreen = namespace("fullscreen");
const swimlane_store = namespace("swimlane");

@Component
export default class SwimlaneHeader extends Vue {
    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @fullscreen.Getter
    readonly fullscreen_class!: string;

    @swimlane_store.Action
    readonly collapseSwimlane!: (swimlane: Swimlane) => void;

    @swimlane_store.Getter
    readonly taskboard_cell_swimlane_header_classes!: string[];

    get additional_classnames(): string {
        return `tlp-swatch-${this.swimlane.card.color}`;
    }

    get title(): string {
        return this.$gettext("Collapse");
    }
}
</script>
