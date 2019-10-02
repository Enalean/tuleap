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
    <span v-if="has_remaining_effort" class="taskboard-card-remaining-effort" v-bind:class="additional_classes">
        {{ card.remaining_effort }}
        <i class="fa fa-long-arrow-right"></i>
        <i class="fa fa-flag-checkered"></i>
    </span>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card } from "../../../type";

@Component
export default class ParentCardRemainingEffort extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    get has_remaining_effort(): boolean {
        return this.card.remaining_effort !== null;
    }

    get additional_classes(): string {
        return `tlp-badge-${this.card.color} taskboard-card-remaining-effort-${this.card.color}`;
    }
}
</script>
