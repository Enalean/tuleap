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
            <card-xref-label v-bind:card="card"/>
            <div class="taskboard-card-info">
                <card-initial-effort v-bind:card="card"/>
                <card-assignees v-bind:assignees="card.assignees"/>
            </div>
        </div>
        <div class="taskboard-card-accessibility" v-if="show_accessibility_pattern"></div>
        <div class="taskboard-card-progress" v-bind:class="progress_color" v-bind:style="{ width: progress_bar_width }"></div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card } from "../../../type";
import CardXrefLabel from "./CardXrefLabel.vue";
import CardAssignees from "./CardAssignees.vue";
import CardInitialEffort from "./CardInitialEffort.vue";
import { namespace } from "vuex-class";
import { getWidthPercentage } from "../../../helpers/progress-bars";

const user = namespace("user");

@Component({
    components: {
        CardInitialEffort,
        CardXrefLabel,
        CardAssignees
    }
})
export default class ParentCard extends Vue {
    @user.State
    readonly user_has_accessibility_mode!: boolean;

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

        if (this.card.background_color) {
            classnames.push(`taskboard-card-background-${this.card.background_color}`);
        }

        if (this.show_accessibility_pattern) {
            classnames.push("taskboard-card-with-accessibility");
        }

        if (this.add_show_class) {
            classnames.push("taskboard-card-show");
        }

        return classnames.join(" ");
    }

    get progress_bar_width(): string {
        const { initial_effort, remaining_effort } = this.card;

        const percentage_width = getWidthPercentage(initial_effort, remaining_effort);

        return `${percentage_width}%`;
    }

    get progress_color(): string {
        return `taskboard-card-progress-${this.card.color}`;
    }

    get show_accessibility_pattern(): boolean {
        return this.user_has_accessibility_mode && this.card.background_color.length > 0;
    }
}
</script>
