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
    <div class="taskboard" ref="taskboard">
        <taskboard-button-bar />
        <task-board-header />
        <task-board-body />
        <error-modal v-if="has_modal_error" />
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import type {
    DragCallbackParameter,
    DragDropCallbackParameter,
    Drekkenov,
    PossibleDropCallbackParameter,
    SuccessfulDropCallbackParameter,
} from "@tuleap/drag-and-drop";
import { init } from "@tuleap/drag-and-drop";
import type { ArrowKey } from "../../type";
import {
    canMove,
    checkCellAcceptsDrop,
    isContainer,
    isConsideredInDropzone,
    invalid,
} from "../../helpers/drag-drop";
import { focusDraggedCard, getContext } from "../../helpers/keyboard-drop";
import TaskBoardHeader from "./Header/TaskBoardHeader.vue";
import TaskBoardBody from "./Body/TaskBoardBody.vue";
import TaskboardButtonBar from "./ButtonBar/TaskboardButtonBar.vue";
import ErrorModal from "../GlobalError/ErrorModal.vue";
import { KeyboardShortcuts } from "../../keyboard-navigation/keyboard-navigation";
import {
    useGetters,
    useMutations,
    useNamespacedActions,
    useNamespacedMutations,
    useNamespacedState,
    useStore,
} from "vuex-composition-helpers";
import type { ErrorState } from "../../store/error/type";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const { has_modal_error } = useNamespacedState<Pick<ErrorState, "has_modal_error">>("error", [
    "has_modal_error",
]);

const store = useStore();
const gettext_provider = useGettext();

const { pointerEntersColumn } = useNamespacedMutations("column", ["pointerEntersColumn"]);

const { column_of_cell } = useGetters(["column_of_cell"]);

const { setIdOfCardBeingDragged, resetIdOfCardBeingDragged } = useMutations([
    "setIdOfCardBeingDragged",
    "resetIdOfCardBeingDragged",
]);

const { handleDrop } = useNamespacedActions("swimlane", ["handleDrop"]);

const { unsetDropZoneRejectingDrop } = useNamespacedMutations("swimlane", [
    "unsetDropZoneRejectingDrop",
]);

const drek = ref<Drekkenov | undefined>(undefined);
const taskboard = ref();

onBeforeUnmount(() => {
    drek.value?.destroy();
});

onMounted(() => {
    drek.value = init({
        mirror_container: taskboard.value,
        isDropZone: isContainer,
        isDraggable: canMove,
        isInvalidDragHandle: invalid,
        isConsideredInDropzone,
        doesDropzoneAcceptDraggable: (context: PossibleDropCallbackParameter): boolean => {
            return checkCellAcceptsDrop(store, {
                dropped_card: context.dragged_element,
                source_cell: context.source_dropzone,
                target_cell: context.target_dropzone,
            });
        },
        onDragStart: onDragStartHandler,
        onDragEnter: (context: PossibleDropCallbackParameter): void => {
            const { target_dropzone } = context;
            target_dropzone.dataset.drekOver = "1";
            const column = column_of_cell.value(target_dropzone);
            if (!column) {
                return;
            }
            pointerEntersColumn(column);
        },
        onDragLeave: (context: DragDropCallbackParameter): void => {
            const { target_dropzone } = context;
            delete target_dropzone.dataset.drekOver;
        },
        onDrop: onDropHandler,
        cleanupAfterDragCallback: cleanupAfterDragCallback,
    });

    const keyboard_shortcuts = new KeyboardShortcuts(document, gettext_provider);
    keyboard_shortcuts.setNavigation((event: KeyboardEvent, direction: ArrowKey) => {
        const card = event.target;
        if (!(card instanceof HTMLElement) || !canMove(card)) {
            return;
        }

        handleMoveCardWithKeyboard(card, direction).then(() => {
            focusDraggedCard(document, store.state);
            cleanupAfterDragCallback();
        });
    });
    keyboard_shortcuts.setQuickAccess();
});

function onDropHandler(context: SuccessfulDropCallbackParameter): void {
    const sibling_card =
        context.next_sibling instanceof HTMLElement ? context.next_sibling : undefined;
    handleDrop({
        dropped_card: context.dropped_element,
        target_cell: context.target_dropzone,
        source_cell: context.source_dropzone,
        sibling_card,
    });
}

function onDragStartHandler(context: DragCallbackParameter): void {
    setIdOfCardBeingDragged(context.dragged_element);
}

function cleanupAfterDragCallback(): void {
    resetIdOfCardBeingDragged();
    unsetDropZoneRejectingDrop();
}

async function handleMoveCardWithKeyboard(card: HTMLElement, direction: ArrowKey): Promise<void> {
    onDragStartHandler({
        dragged_element: card,
    });

    const context = getContext(document, store.state, direction);
    if (!context) {
        return;
    }

    await onDropHandler(context);
}
</script>
