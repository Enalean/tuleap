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
    <div class="taskboard">
        <taskboard-button-bar/>
        <task-board-header/>
        <task-board-body/>
        <error-modal v-if="has_modal_error"/>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace, Getter, Mutation } from "vuex-class";
import dragula, { Drake } from "dragula";
import { Swimlane, ColumnDefinition, Card, TaskboardEvent } from "../../type";
import { isContainer, canMove, invalid, checkCellAcceptsDrop } from "../../helpers/drag-drop";
import { HandleDropPayload } from "../../store/swimlane/type";
import EventBus from "../../helpers/event-bus";
import TaskBoardHeader from "./Header/TaskBoardHeader.vue";
import TaskBoardBody from "./Body/TaskBoardBody.vue";
import TaskboardButtonBar from "./ButtonBar/TaskboardButtonBar.vue";
import ErrorModal from "../GlobalError/ErrorModal.vue";

const error = namespace("error");
const column = namespace("column");
const swimlane = namespace("swimlane");

@Component({
    components: { TaskBoardBody, TaskBoardHeader, TaskboardButtonBar, ErrorModal }
})
export default class TaskBoard extends Vue {
    @error.State
    readonly has_modal_error!: boolean;

    @column.Mutation
    readonly mouseEntersColumn!: (column: ColumnDefinition) => void;

    @column.Mutation
    readonly mouseLeavesColumn!: (column: ColumnDefinition) => void;

    @swimlane.Getter
    readonly cards_in_cell!: (
        current_swimlane: Swimlane,
        current_column: ColumnDefinition
    ) => Card[];

    @Getter
    readonly column_and_swimlane_of_cell!: (
        cell: HTMLElement
    ) => {
        swimlane?: Swimlane;
        column?: ColumnDefinition;
    };

    @Getter
    readonly column_of_cell!: (cell: HTMLElement) => ColumnDefinition | undefined;

    @Mutation
    readonly setIdOfCardBeingDragged!: (card: Element) => void;

    @Mutation
    readonly resetIdOfCardBeingDragged!: () => void;

    @swimlane.Action
    handleDrop!: (payload: HandleDropPayload) => void;

    @swimlane.Mutation
    readonly unsetDropZoneRejectingDrop!: () => void;

    private drake!: Drake | undefined;

    beforeDestroy(): void {
        if (this.drake) {
            this.drake.destroy();
        }
        EventBus.$off(TaskboardEvent.ESC_KEY_PRESSED, this.cancelDragOnEscape);
    }

    mounted(): void {
        this.drake = dragula({
            isContainer,
            moves: canMove,
            invalid,
            revertOnSpill: true,
            mirrorContainer: this.$el,
            accepts: (
                dropped_card?: Element,
                target_cell?: Element,
                source_cell?: Element
            ): boolean =>
                checkCellAcceptsDrop(this.$store, {
                    dropped_card,
                    target_cell,
                    source_cell
                })
        });

        this.drake.on(
            "drop",
            (
                dropped_card: HTMLElement,
                target_cell: HTMLElement,
                source_cell: HTMLElement,
                sibling_card?: HTMLElement
            ) => this.handleDrop({ dropped_card, target_cell, source_cell, sibling_card })
        );

        this.drake.on("cancel", this.unsetDropZoneRejectingDrop);
        this.drake.on("drag", this.setIdOfCardBeingDragged);
        this.drake.on("dragend", this.resetIdOfCardBeingDragged);

        EventBus.$on(TaskboardEvent.ESC_KEY_PRESSED, this.cancelDragOnEscape);

        this.drake.on(
            "over",
            (element?: Element, target?: Element): void => {
                if (
                    !target ||
                    !target.classList.contains("taskboard-cell-collapsed") ||
                    !(target instanceof HTMLElement)
                ) {
                    return;
                }

                const column = this.column_of_cell(target);

                if (!column) {
                    return;
                }

                this.mouseEntersColumn(column);
            }
        );

        this.drake.on(
            "out",
            (element?: Element, target?: Element): void => {
                if (
                    !target ||
                    !target.classList.contains("taskboard-cell-collapsed") ||
                    !(target instanceof HTMLElement)
                ) {
                    return;
                }

                const column = this.column_of_cell(target);

                if (!column) {
                    return;
                }

                this.mouseLeavesColumn(column);
            }
        );
    }

    cancelDragOnEscape(): void {
        if (this.drake) {
            this.drake.cancel(true);
        }
    }
}
</script>
