<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <div ref="fields_list" data-is-container="true">
        <div
            v-for="field in currently_selected_fields"
            v-bind:key="field.field_id"
            v-bind:data-field-id="field.field_id"
            v-bind:class="{
                'with-hidden-controls': is_drag_and_dropping,
                'row-field': true,
            }"
            data-test="readonly-field-rows"
            draggable="true"
        >
            <div class="dragndrop-grip"><dragndrop-grip-illustration /></div>
            <div class="field-label">{{ field.label }}</div>
            <div class="field-display-type">
                <label
                    class="tlp-label tlp-checkbox"
                    v-bind:title="getSwitchDisplayTypeCheckboxTitle(field)"
                >
                    <input
                        type="checkbox"
                        value="1"
                        data-not-drag-handle="true"
                        data-test="switch-display-type-checkbox"
                        draggable="false"
                        v-bind:checked="field.display_type === DISPLAY_TYPE_BLOCK"
                        v-bind:disabled="!field.can_display_type_be_changed"
                        v-on:change="() => switchFieldDisplayType(field)"
                    />
                    {{ $gettext("Full row") }}
                </label>
            </div>
            <div class="field-actions">
                <button
                    type="button"
                    class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline"
                    data-not-drag-handle="true"
                    v-on:click="emit('unselect-field', field)"
                >
                    <i class="tlp-button-icon fa-solid fa-trash fa-fw" aria-hidden="true"></i>
                    {{ $gettext("Remove") }}
                </button>
            </div>
            <div data-not-drag-handle="true" draggable="false" class="field-reorder-arrows">
                <reorder-fields-arrows
                    v-bind:field="field"
                    v-bind:is_first="fields_reorderer.isFirstField(field)"
                    v-bind:is_last="fields_reorderer.isLastField(field)"
                    v-on:move-up="fields_reorderer.moveFieldUp(field)"
                    v-on:move-down="fields_reorderer.moveFieldDown(field)"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import type {
    DragCallbackParameter,
    Drekkenov,
    SuccessfulDropCallbackParameter,
} from "@tuleap/drag-and-drop";
import { init } from "@tuleap/drag-and-drop";
import DragndropGripIllustration from "@/components/dnd/DragndropGripIllustration.vue";
import ReorderFieldsArrows from "@/components/configuration/ReorderFieldsArrows.vue";
import type { FieldsReorderer } from "@/sections/readonly-fields/FieldsReorderer";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import {
    DISPLAY_TYPE_BLOCK,
    DISPLAY_TYPE_COLUMN,
} from "@/sections/readonly-fields/AvailableReadonlyFields";

const { $gettext } = useGettext();

const props = defineProps<{
    currently_selected_fields: ConfigurationField[];
    fields_reorderer: FieldsReorderer;
}>();

const emit = defineEmits<{
    (e: "unselect-field", field: ConfigurationField): void;
}>();

const fields_list = ref<HTMLElement>();
const is_drag_and_dropping = ref(false);

let drek: Drekkenov | undefined = undefined;

onMounted(() => {
    if (!fields_list.value) {
        return;
    }

    drek = init({
        mirror_container: fields_list.value,
        isDropZone: (element: HTMLElement) => element === fields_list.value,
        isDraggable: (element: HTMLElement) => element.draggable,
        isInvalidDragHandle: (handle: HTMLElement) =>
            Boolean(handle.closest("[data-not-drag-handle]")),
        isConsideredInDropzone: (child: Element) => child.hasAttribute("draggable"),
        doesDropzoneAcceptDraggable: () => true,
        onDragStart: (context: DragCallbackParameter): void => {
            if (context.dragged_element.dataset.fieldId === undefined) {
                return;
            }

            is_drag_and_dropping.value = true;
        },
        onDrop: (context: SuccessfulDropCallbackParameter): void => {
            const dropped_field_id = Number.parseInt(
                context.dropped_element.dataset.fieldId ?? "",
                10,
            );
            if (!dropped_field_id) {
                return;
            }

            const dropped_field = props.currently_selected_fields.find(
                (field) => field.field_id === dropped_field_id,
            );
            if (!dropped_field) {
                return;
            }

            const next_sibling_field_id =
                context.next_sibling instanceof HTMLElement
                    ? Number.parseInt(context.next_sibling.dataset.fieldId ?? "", 10)
                    : null;

            if (next_sibling_field_id === null) {
                props.fields_reorderer.moveFieldAtTheEnd(dropped_field);
                return;
            }

            const next_sibling = props.currently_selected_fields.find(
                (field) => field.field_id === next_sibling_field_id,
            );
            if (!next_sibling) {
                return;
            }

            props.fields_reorderer.moveFieldBeforeSibling(dropped_field, next_sibling);
        },
        cleanupAfterDragCallback: (): void => {
            is_drag_and_dropping.value = false;
        },
    });
});

onBeforeUnmount(() => {
    drek?.destroy();
});

const switchFieldDisplayType = (field: ConfigurationField): void => {
    field.display_type =
        field.display_type === DISPLAY_TYPE_COLUMN ? DISPLAY_TYPE_BLOCK : DISPLAY_TYPE_COLUMN;
};

const getSwitchDisplayTypeCheckboxTitle = (field: ConfigurationField): string => {
    if (field.can_display_type_be_changed) {
        return "";
    }

    return $gettext(
        "The display type of this type of field cannot be changed, otherwise its readability would be impacted.",
    );
};
</script>

<style scoped lang="scss">
@use "pkg:@tuleap/drag-and-drop";
@use "@/themes/includes/size";

.reorder-arrows {
    transition: opacity ease-in-out 150ms;
    opacity: 0;
}

.row-field {
    display: flex;
    flex-direction: row;

    &:hover {
        background: var(--tlp-main-color-hover-background);
    }

    &:hover .reorder-arrows,
    &:focus-within .reorder-arrows {
        opacity: 0.5;
    }

    &:hover .reorder-arrows:hover,
    .reorder-arrows:focus-within {
        opacity: 1;
    }

    &:has(> .dragndrop-grip:hover) {
        transition: background ease-in-out 150ms;
        background: var(--tlp-main-color-lighter-90);
    }

    &:hover > .dragndrop-grip {
        background: var(--tlp-main-color);
        color: var(--tlp-main-color-lighter-90);
    }
}

.field-display-type > .tlp-label {
    margin: 0;
}

.field-label {
    flex: 1 1 1px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.field-display-type {
    width: size.$fields-selection-display-type-column-width;
}

.field-actions {
    width: size.$fields-selection-action-button-column-width;
}

.field-reorder-arrows {
    width: size.$reorder-arrow-size;
}

.field-label,
.field-display-type,
.field-actions {
    padding: var(--tlp-small-spacing);
}

.dragndrop-grip {
    display: flex;
    align-items: center;
    justify-content: center;
    width: size.$drag-and-drop-handle-width;
    transition:
        opacity ease-in-out 150ms,
        background ease-in-out 150ms,
        color ease-in-out 150ms;
    background: inherit;
    color: var(--tlp-white-color);
    cursor: move;

    &:hover {
        background: var(--tlp-main-color);
        color: var(--tlp-main-color-lighter-90);
    }

    &.dragndrop-grip-when-sections-loading {
        visibility: hidden;
    }
}

.with-hidden-move-controls {
    .dragndrop-grip {
        visibility: hidden;
    }

    .reorder-arrows {
        opacity: 0;
    }
}

.drek-ghost {
    border-radius: 0;

    > div {
        visibility: hidden;
    }
}

.drek-hide {
    display: none;
}
</style>
