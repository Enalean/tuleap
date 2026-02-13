<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
  -->

<template>
    <div
        ref="container"
        class="tracker-admin-fields-container-dropzone"
        v-bind:data-container-id="ROOT_CONTAINER_ID"
    >
        <display-form-elements v-bind:elements="tracker_root.children" />
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, useTemplateRef } from "vue";
import DisplayFormElements from "./DisplayFormElements.vue";
import type {
    DragDropCallbackParameter,
    Drekkenov,
    PossibleDropCallbackParameter,
    SuccessfulDropCallbackParameter,
} from "@tuleap/drag-and-drop";
import { init } from "@tuleap/drag-and-drop";
import { POST_FIELD_DND_CALLBACK, TRACKER_ROOT } from "../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import { getSuccessfulDropContextTransformer } from "../helpers/SuccessfulDropContextTransformer";
import { getFieldsMover } from "../helpers/FieldsMover";
import { ROOT_CONTAINER_ID } from "../type";
import { getDropRulesEnforcer } from "../helpers/DropRulesEnforcer";
import { saveNewFieldsOrder } from "../helpers/save-new-fields-order";

const tracker_root = strictInject(TRACKER_ROOT);
const post_field_update_callback = strictInject(POST_FIELD_DND_CALLBACK);
const container = useTemplateRef<HTMLElement>("container");
const drop_rules_enforcer = getDropRulesEnforcer(tracker_root);
const context_transformer = getSuccessfulDropContextTransformer(tracker_root);
const fields_mover = getFieldsMover();

let drek: Drekkenov | undefined = undefined;

onMounted(() => {
    if (!container.value) {
        return;
    }

    drek = init({
        mirror_container: container.value,
        isDropZone: (element: HTMLElement) =>
            element.classList.contains("tracker-admin-fields-container-dropzone"),
        isDraggable: (element: HTMLElement) => element.draggable,
        isInvalidDragHandle: (handle: HTMLElement) =>
            Boolean(handle.closest("[data-not-drag-handle]")),
        isConsideredInDropzone: (child: Element) => child.hasAttribute("draggable"),
        doesDropzoneAcceptDraggable: drop_rules_enforcer.isDropPossible,
        onDragStart: (): void => {},
        onDragEnter(context: PossibleDropCallbackParameter): void {
            context.source_dropzone.classList.remove(
                "tracker-admin-fields-container-dropzone-hover",
            );
            context.target_dropzone.classList.add("tracker-admin-fields-container-dropzone-hover");
        },
        onDragLeave(context: DragDropCallbackParameter): void {
            context.target_dropzone.classList.remove(
                "tracker-admin-fields-container-dropzone-hover",
            );
        },
        onDrop(context: SuccessfulDropCallbackParameter): void {
            context_transformer
                .transformSuccessfulDropContext(context)
                .andThen(fields_mover.moveField)
                .asyncAndThen(saveNewFieldsOrder)
                .match(post_field_update_callback, (fault) => {
                    /* eslint-disable-next-line no-console */
                    console.error(`[tracker-admin-fields] Unable to move element: ${fault}`);
                });
        },
        cleanupAfterDragCallback: (): void => {},
    });
});

onBeforeUnmount(() => {
    drek?.destroy();
});
</script>
<style lang="scss">
@use "pkg:@tuleap/drag-and-drop";
</style>
