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
    <div class="taskboard-card taskboard-card-parent" v-bind:class="additional_classnames">
        <div class="taskboard-card-content">
            <div class="taskboard-card-xref-label">
                <span class="taskboard-card-xref">{{ card.xref }}</span>
                <span class="taskboard-card-label">{{ card.label }}</span>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card } from "../../../type";

@Component
export default class ParentCard extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    add_show_class = true;

    mounted(): void {
        setTimeout(() => {
            this.add_show_class = false;
        }, 500);
    }

    get additional_classnames(): string {
        const classnames = [`taskboard-card-${this.card.color}`];

        if (this.add_show_class) {
            classnames.push("taskboard-card-show");
        }

        return classnames.join(" ");
    }
}
</script>
