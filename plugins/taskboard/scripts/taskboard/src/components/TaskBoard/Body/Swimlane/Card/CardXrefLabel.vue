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
    <div class="taskboard-card-xref-label">
        <a class="taskboard-card-xref"
           v-bind:href="card.artifact_html_uri"
           v-bind:class="additional_classnames"
           data-not-drag-handle="true"
           data-test="xref"
        >{{ card.xref }}</a>
        <span class="taskboard-card-label" v-if="!card.is_in_edit_mode">{{ label }}</span>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card } from "../../../../../type";

@Component
export default class CardXrefLabel extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    @Prop({ required: true })
    readonly label!: string;

    get additional_classnames(): string {
        if (this.card.background_color) {
            return `tlp-swatch-${this.card.background_color}`;
        }

        return `tlp-swatch-${this.card.color}`;
    }
}
</script>
