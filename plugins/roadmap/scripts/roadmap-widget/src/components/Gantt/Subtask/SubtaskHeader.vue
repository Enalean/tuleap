<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div class="roadmap-gantt-task-header roadmap-gantt-subtask-header" v-bind:class="classes">
        <header-link
            v-bind:task="row.subtask"
            v-bind:should_display_project="should_display_project"
        />
        <header-invalid-icon v-if="is_task_invalid" data-test="icon" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { SubtaskRow } from "../../../type";
import HeaderLink from "../Task/HeaderLink.vue";
import type { Popover } from "@tuleap/tlp-popovers/types/scripts/lib/tlp-popovers/src/popovers";
import { createPopover } from "@tuleap/tlp-popovers";
import { doesTaskHaveEndDateGreaterOrEqualToStartDate } from "../../../helpers/task-has-valid-dates";
import HeaderInvalidIcon from "../Task/HeaderInvalidIcon.vue";

@Component({
    components: { HeaderInvalidIcon, HeaderLink },
})
export default class SubtaskHeader extends Vue {
    @Prop({ required: true })
    readonly row!: SubtaskRow;

    @Prop({ required: true })
    private readonly popover_element_id!: string;

    private popover: Popover | undefined;

    mounted(): void {
        const popover_element = document.getElementById(this.popover_element_id);
        if (
            this.is_task_invalid &&
            this.$el instanceof HTMLElement &&
            popover_element instanceof HTMLElement
        ) {
            this.popover = createPopover(this.$el, popover_element, {
                placement: "right",
            });
        }
    }

    beforeDestroy(): void {
        if (this.popover) {
            this.popover.destroy();
        }
    }

    get classes(): string[] {
        const classes = ["roadmap-gantt-task-header-" + this.row.parent.color_name];

        if (this.row.is_last_one) {
            classes.push("roadmap-gantt-subtask-header-last-one");
        }

        if (this.is_task_invalid) {
            classes.push("roadmap-gantt-subtask-header-for-invalid-task");
        }

        return classes;
    }

    get should_display_project(): boolean {
        return this.row.parent.project.id !== this.row.subtask.project.id;
    }

    get is_task_invalid(): boolean {
        return !doesTaskHaveEndDateGreaterOrEqualToStartDate(this.row.subtask);
    }
}
</script>
