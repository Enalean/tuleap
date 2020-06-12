<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="tlp-card">
        <span class="tlp-badge-outline" v-bind:class="badge_color">
            {{ backlog_item.short_type }} #{{ backlog_item.id }}
        </span>
        <span class="test-plan-backlog-item-title">
            {{ backlog_item.label }}
        </span>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { BacklogItem } from "../../type";

@Component
export default class BacklogItemCard extends Vue {
    @Prop({ required: true })
    readonly backlog_item!: BacklogItem;

    get badge_color(): string {
        return "tlp-badge-" + this.backlog_item.color;
    }
}
</script>
