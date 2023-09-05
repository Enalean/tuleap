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
        <taskboard-button-bar />
        <task-board-header />
        <task-board-body />
        <error-modal v-if="has_modal_error" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { Getter, Mutation, namespace } from "vuex-class";
import type {
    DragCallbackParameter,
    DragDropCallbackParameter,
    Drekkenov,
    PossibleDropCallbackParameter,
    SuccessfulDropCallbackParameter,
} from "@tuleap/drag-and-drop";
import { init } from "@tuleap/drag-and-drop";
import type { ArrowKey, Card, ColumnDefinition, Swimlane } from "../../type";
import {
    canMove,
    checkCellAcceptsDrop,
    isContainer,
    isConsideredInDropzone,
    invalid,
} from "../../helpers/drag-drop";
import { focusDraggedCard, getContext } from "../../helpers/keyboard-drop";
import type { HandleDropPayload } from "../../store/swimlane/type";
import TaskBoardHeader from "./Header/TaskBoardHeader.vue";
import TaskBoardBody from "./Body/TaskBoardBody.vue";
import TaskboardButtonBar from "./ButtonBar/TaskboardButtonBar.vue";
import ErrorModal from "../GlobalError/ErrorModal.vue";
import { KeyboardShortcuts } from "../../keyboard-navigation/keyboard-navigation";

const error = namespace("error");
const column = namespace("column");
const swimlane = namespace("swimlane");

@Component({
    components: { TaskBoardBody, TaskBoardHeader, TaskboardButtonBar, ErrorModal },
})
export default class TaskBoard extends Vue {
    @error.State
    readonly has_modal_error!: boolean;

    @column.Mutation
    readonly pointerEntersColumn!: (column: ColumnDefinition) => void;

    @column.Mutation
    readonly pointerLeavesColumn!: (column: ColumnDefinition) => void;

    @swimlane.Getter
    readonly cards_in_cell!: (
        current_swimlane: Swimlane,
        current_column: ColumnDefinition,
    ) => Card[];

    @Getter
    readonly column_and_swimlane_of_cell!: (cell: HTMLElement) => {
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

    private drek!: Drekkenov | undefined;

    beforeDestroy(): void {
        if (this.drek) {
            this.drek.destroy();
        }
    }

    mounted(): void {
        this.drek = init({
            mirror_container: this.$el,
            isDropZone: isContainer,
            isDraggable: canMove,
            isInvalidDragHandle: invalid,
            isConsideredInDropzone,
            doesDropzoneAcceptDraggable: (context: PossibleDropCallbackParameter): boolean => {
                return checkCellAcceptsDrop(this.$store, {
                    dropped_card: context.dragged_element,
                    source_cell: context.source_dropzone,
                    target_cell: context.target_dropzone,
                });
            },
            onDragStart: this.onDragStartHandler,
            onDragEnter: (context: PossibleDropCallbackParameter): void => {
                const { target_dropzone } = context;
                target_dropzone.dataset.drekOver = "1";
                const column = this.column_of_cell(target_dropzone);
                if (!column) {
                    return;
                }
                if (column.is_collapsed) {
                    this.pointerEntersColumn(column);
                }
            },
            onDragLeave: (context: DragDropCallbackParameter): void => {
                const { target_dropzone } = context;
                delete target_dropzone.dataset.drekOver;
                const column = this.column_of_cell(target_dropzone);
                if (!column) {
                    return;
                }
                if (column.is_collapsed) {
                    this.pointerLeavesColumn(column);
                }
            },
            onDrop: this.onDropHandler,
            cleanupAfterDragCallback: this.cleanupAfterDragCallback,
        });

        const gettext_provider = {
            $gettext: Vue.prototype.$gettext,
            $pgettext: Vue.prototype.$pgettext,
        };

        const keyboard_shortcuts = new KeyboardShortcuts(document, gettext_provider);
        keyboard_shortcuts.setNavigation((event: KeyboardEvent, direction: ArrowKey) => {
            const card = event.target;
            if (!(card instanceof HTMLElement) || !canMove(card)) {
                return;
            }

            this.handleMoveCardWithKeyboard(card, direction).then(() => {
                focusDraggedCard(document, this.$store.state);
                this.cleanupAfterDragCallback();
            });
        });
        keyboard_shortcuts.setQuickAccess();
    }

    onDropHandler = (context: SuccessfulDropCallbackParameter): void => {
        const sibling_card =
            context.next_sibling instanceof HTMLElement ? context.next_sibling : undefined;
        this.handleDrop({
            dropped_card: context.dropped_element,
            target_cell: context.target_dropzone,
            source_cell: context.source_dropzone,
            sibling_card,
        });
    };

    onDragStartHandler = (context: DragCallbackParameter): void => {
        this.setIdOfCardBeingDragged(context.dragged_element);
    };

    cleanupAfterDragCallback = (): void => {
        this.resetIdOfCardBeingDragged();
        this.unsetDropZoneRejectingDrop();
    };

    handleMoveCardWithKeyboard = async (card: HTMLElement, direction: ArrowKey): Promise<void> => {
        this.onDragStartHandler({
            dragged_element: card,
        });

        const context = getContext(document, this.$store.state, direction);
        if (!context) {
            return;
        }

        await this.onDropHandler(context);
    };
}
</script>
