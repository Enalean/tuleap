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
    <span v-if="has_initial_effort"
          class="taskboard-card-inital-effort tlp-badge-outline taskboard-no-text-selection"
          v-bind:class="color_classname"
          v-bind:title="initial_effort_label"
    >
        <i class="fa fa-flag-checkered fa-flip-horizontal tlp-badge-icon"></i>
        {{ card.initial_effort }}
    </span>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card } from "../../../../../type";

@Component
export default class CardInitialEffort extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    get initial_effort_label(): string {
        return this.$gettext("Initial effort");
    }

    get has_initial_effort(): boolean {
        return this.card.initial_effort !== null;
    }

    get color_classname(): string {
        return `tlp-badge-${this.card.color}`;
    }
}
</script>
