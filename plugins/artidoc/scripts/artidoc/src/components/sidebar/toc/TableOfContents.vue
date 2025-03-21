<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <h1 class="tlp-pane-title">
        {{ $gettext("Table of contents") }}
    </h1>
    <ul ref="list" data-is-container="true">
        <li
            data-test="section-in-toc"
            v-for="(section, index) in sections_collection.sections.value"
            v-bind:key="section.value.id"
            v-bind:draggable="sections_being_saved.length === 0"
            v-bind:data-internal-id="section.value.internal_id"
            v-bind:class="{
                'section-saved-with-success': just_saved_sections.some(
                    (just_saved_section) =>
                        just_saved_section.internal_id === section.value.internal_id,
                ),
                'section-being-saved': sections_being_saved.some(
                    (section_being_saved) => section_being_saved === section.value,
                ),
                'child-of-hovered-parent': isSectionParentHovered(section.value),
                'child-of-dragged-parent': isSectionParentDragged(section.value),
                'with-hidden-move-controls':
                    sections_being_dragged.length > 0 ||
                    sections_being_saved.length > 0 ||
                    just_saved_sections.length > 0,
            }"
        >
            <span
                class="dragndrop-grip"
                data-test="dragndrop-grip"
                v-if="is_reorder_allowed"
                v-bind:class="{ 'dragndrop-grip-when-sections-loading': is_loading_sections }"
                v-on:mouseover="setSectionBeingHovered(section)"
                v-on:mouseout="clearSectionBeingHovered()"
            >
                <dragndrop-grip-illustration />
            </span>

            <span class="toc-display-level" data-test="display-level">
                {{ section.value.display_level }}
            </span>

            <span v-if="is_loading_sections" class="tlp-skeleton-text"></span>
            <a
                v-else-if="
                    isArtifactSection(section.value) || !isSectionBasedOnArtifact(section.value)
                "
                v-bind:href="`#section-${section.value.id}`"
                class="table-of-content-section-title"
                data-not-drag-handle="true"
            >
                {{ getReactiveEditedTitle(section) }}
            </a>
            <span v-else class="table-of-content-section-title" data-not-drag-handle="true">
                {{ getReactiveEditedTitle(section) }}
            </span>
            <span
                class="reorder-arrows"
                data-test="reorder-arrows"
                v-if="is_reorder_allowed"
                v-bind:class="{ 'reorder-arrows-when-sections-loading': is_loading_sections }"
                data-not-drag-handle="true"
                v-on:mouseover="setSectionBeingHovered(section)"
                v-on:mouseout="clearSectionBeingHovered()"
                v-on:focusin="setSectionBeingHovered(section)"
                v-on:focusout="clearSectionBeingHovered()"
            >
                <reorder-arrows
                    v-bind:is_first="index === 0"
                    v-bind:is_last="isLastSectionOrBlock(section.value, index)"
                    v-bind:section="section"
                    v-bind:sections_reorderer="sections_reorderer"
                    v-bind:sections_structurer="sections_structurer"
                    v-on:moved-section-up-or-down="showJustSavedTemporaryFeedback"
                    v-on:moving-section-up-or-down="showSectionBeingSavedTemporaryFeedback"
                    v-on:moved-section-up-or-down-fault="handleReorderingFault"
                />
            </span>
        </li>
    </ul>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { onMounted, onUnmounted, ref } from "vue";
import type { Ref } from "vue";
import { isArtifactSection, isSectionBasedOnArtifact } from "@/helpers/artidoc-section.type";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Fault } from "@tuleap/fault";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import DragndropGripIllustration from "@/components/sidebar/toc/DragndropGripIllustration.vue";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import ReorderArrows from "@/components/sidebar/toc/ReorderArrows.vue";
import { init } from "@tuleap/drag-and-drop";
import type { Drekkenov, SuccessfulDropCallbackParameter } from "@tuleap/drag-and-drop";
import type {
    InternalArtidocSectionId,
    ReactiveStoredArtidocSection,
} from "@/sections/SectionsCollection";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { TEMPORARY_FLAG_DURATION_IN_MS } from "@/components/temporary-flag-duration";
import { SET_GLOBAL_ERROR_MESSAGE } from "@/global-error-message-injection-key";
import { IS_LOADING_SECTIONS } from "@/is-loading-sections-injection-key";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import { isCannotReorderSectionsFault } from "@/sections/reorder/CannotReorderSectionsFault";
import { buildSectionsReorderer } from "@/sections/reorder/SectionsReorderer";
import { getSectionsStructurer } from "@/sections/reorder/SectionsStructurer";
import type { DragCallbackParameter } from "@tuleap/drag-and-drop/src";

const { $gettext } = useGettext();

const is_loading_sections = strictInject(IS_LOADING_SECTIONS);
const sections_collection = strictInject(SECTIONS_COLLECTION);
const states_collection = strictInject(SECTIONS_STATES_COLLECTION);
const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);
const document_id = strictInject(DOCUMENT_ID);
const setGlobalErrorMessage = strictInject(SET_GLOBAL_ERROR_MESSAGE);

const sections_reorderer = buildSectionsReorderer(sections_collection);
const sections_structurer = getSectionsStructurer(sections_collection);

const is_reorder_allowed = can_user_edit_document;

const list = ref<HTMLElement>();

let drek: Drekkenov | undefined = undefined;

const just_saved_sections: Ref<InternalArtidocSectionId[]> = ref([]);
const sections_being_saved: Ref<InternalArtidocSectionId[]> = ref([]);
const section_being_hovered: Ref<null | InternalArtidocSectionId> = ref(null);
const sections_being_dragged: Ref<InternalArtidocSectionId[]> = ref([]);

const setSectionBeingHovered = (section: ReactiveStoredArtidocSection): void => {
    if (sections_being_dragged.value.length > 0) {
        // We do not want to highlight anything since a drag and drop is already in progress
        return;
    }

    section_being_hovered.value = section.value;
};

const clearSectionBeingHovered = (): void => {
    section_being_hovered.value = null;
};

const isSectionParentDragged = (section: InternalArtidocSectionId): boolean => {
    if (sections_being_dragged.value.length <= 1) {
        // Do nothing, no children is being dragged
        return false;
    }

    return sections_being_dragged.value.some((child) => {
        return child === section;
    });
};

const isLastSectionOrBlock = (
    section: InternalArtidocSectionId,
    section_index: number,
): boolean => {
    const section_children = sections_structurer.getSectionChildren(section);
    const end_of_block_index = section_index + section_children.length;
    return end_of_block_index === sections_collection.sections.value.length - 1;
};

const isSectionParentHovered = (section: InternalArtidocSectionId): boolean => {
    if (!section_being_hovered.value) {
        return false;
    }

    const children_of_current_hovered_section = sections_structurer.getSectionChildren(
        section_being_hovered.value,
    );
    return children_of_current_hovered_section.some((section_child) => {
        return section_child.value.internal_id === section.internal_id;
    });
};

const showJustSavedTemporaryFeedback = (moved_sections: InternalArtidocSectionId[]): void => {
    just_saved_sections.value = moved_sections;
    sections_being_saved.value = [];

    setTimeout(() => {
        just_saved_sections.value = [];
    }, TEMPORARY_FLAG_DURATION_IN_MS);
};

const showSectionBeingSavedTemporaryFeedback = (
    moved_sections: InternalArtidocSectionId[],
): void => {
    sections_being_saved.value = moved_sections;
};

const handleReorderingFault = (fault: Fault): void => {
    sections_being_saved.value = [];

    const details = isCannotReorderSectionsFault(fault)
        ? $gettext("An error occurred")
        : String(fault);

    setGlobalErrorMessage({
        message: $gettext("Unable to reorder the sections"),
        details,
    });
};

const getReactiveEditedTitle = (section: ReactiveStoredArtidocSection): string =>
    states_collection.getSectionState(section.value).edited_title.value;

onMounted(() => {
    if (!list.value || !is_reorder_allowed) {
        return;
    }

    drek = init({
        mirror_container: list.value,
        isDropZone: (element: HTMLElement) => Boolean(element.dataset.isContainer),
        isDraggable: (element: HTMLElement) => element.draggable,
        isInvalidDragHandle: (handle: HTMLElement) =>
            Boolean(handle.closest("[data-not-drag-handle]")),
        isConsideredInDropzone: (child: Element) => child.hasAttribute("draggable"),
        doesDropzoneAcceptDraggable: () => true,
        onDragStart: (context: DragCallbackParameter): void => {
            if (context.dragged_element.dataset.internalId === undefined) {
                return;
            }

            const dragged_section = {
                internal_id: context.dragged_element.dataset.internalId,
            };

            sections_being_dragged.value = [
                dragged_section,
                ...sections_structurer
                    .getSectionChildren(dragged_section)
                    .map((child) => child.value),
            ];
        },
        onDrop: (context: SuccessfulDropCallbackParameter): void => {
            if (context.dropped_element.dataset.internalId === undefined) {
                return;
            }

            const moved_section = {
                internal_id: context.dropped_element.dataset.internalId,
            };

            const children_of_moved_section = sections_structurer
                .getSectionChildren(moved_section)
                .map((child) => child.value);
            const moved_sections = [moved_section, ...children_of_moved_section];

            showSectionBeingSavedTemporaryFeedback(moved_sections);

            if (context.next_sibling === null) {
                sections_reorderer.moveSectionAtTheEnd(document_id, moved_section).match(() => {
                    showJustSavedTemporaryFeedback(moved_sections);
                }, handleReorderingFault);
                return;
            }

            if (
                !(context.next_sibling instanceof HTMLElement) ||
                context.next_sibling.dataset.internalId === undefined
            ) {
                return;
            }
            const sibling = {
                internal_id: context.next_sibling.dataset.internalId,
            };
            sections_reorderer.moveSectionBefore(document_id, moved_section, sibling).match(() => {
                showJustSavedTemporaryFeedback(moved_sections);
            }, handleReorderingFault);
        },
        cleanupAfterDragCallback: () => {
            sections_being_dragged.value = [];
        },
    });
});

onUnmounted(() => {
    drek?.destroy();
});
</script>

<style scoped lang="scss">
@use "pkg:@tuleap/drag-and-drop";
@use "@/themes/includes/viewport-breakpoint";

@keyframes blink-toc-item {
    0% {
        background: var(--tlp-info-color-transparent-90);
    }

    50% {
        background: transparent;
    }

    100% {
        background: var(--tlp-info-color-transparent-90);
    }
}

h1 {
    display: flex;
    align-items: center;
    height: var(--artidoc-sidebar-title-height);
    margin: var(--artidoc-sidebar-title-vertical-margin) var(--tlp-medium-spacing);
}

ul {
    height: var(--artidoc-sidebar-content-height);
    padding: 0 0 var(--tlp-medium-spacing);
    overflow: hidden auto;
    list-style-position: inside;
    color: var(--tlp-dimmed-color);

    @media (max-width: viewport-breakpoint.$small-screen-size) {
        height: fit-content;
    }
}

li {
    &::marker {
        color: transparent; // hack to hide the li point to be displayed before the title
    }

    position: relative;
    padding: calc(var(--tlp-small-spacing) / 2) var(--tlp-medium-spacing);

    &:first-child {
        padding-top: 0;
    }

    &:has(> .dragndrop-grip) {
        padding-left: var(--tlp-small-spacing);
    }

    &:has(> .reorder-arrows) {
        padding-right: var(--tlp-large-spacing);
    }

    &:has(> .reorder-arrows:focus-within),
    &:has(> .reorder-arrows:hover),
    &:has(> .dragndrop-grip:hover) {
        transition: background ease-in-out 250ms;
        background: var(--tlp-main-color-lighter-90);
    }

    &:not(:hover) > .dragndrop-grip {
        display: none;
    }

    &:not(:hover, :focus-within) > .reorder-arrows:not(:focus-within) {
        opacity: 0;
    }
}

.child-of-hovered-parent {
    transition: background ease-in-out 250ms;
    background: var(--tlp-main-color-lighter-90);
}

.child-of-dragged-parent {
    display: none;
}

.with-hidden-move-controls {
    .dragndrop-grip {
        visibility: hidden;
    }

    .reorder-arrows {
        opacity: 0;
    }
}

.section-saved-with-success {
    animation: pulse-section 500ms ease-in-out;
    background-color: var(--tlp-success-color-lighter-90);
}

.section-being-saved {
    animation: blink-toc-item 1200ms ease-in-out alternate infinite;
}

.section-saved-with-success,
.section-being-saved,
li[draggable="false"] {
    > .reorder-arrows,
    > .dragndrop-grip {
        opacity: 0.1;
        pointer-events: none;
    }
}

.drek-ghost {
    border-radius: 0;

    > .dragndrop-grip,
    > .table-of-content-section-title,
    > .reorder-arrows,
    > .toc-display-level {
        visibility: hidden;
    }
}

.dragndrop-grip {
    display: flex;
    position: absolute;
    top: 0;
    left: 0;
    align-items: center;
    justify-content: center;
    width: var(--tlp-medium-spacing);
    height: 100%;
    transition:
        opacity ease-in-out 250ms,
        background ease-in-out 250ms,
        color ease-in-out 250ms;
    opacity: 0.5;
    background: var(--tlp-dimmed-color);
    color: var(--tlp-white-color);
    cursor: move;

    &:hover {
        opacity: 1;
        background: var(--tlp-main-color);
        color: var(--tlp-main-color-lighter-90);
    }

    &.dragndrop-grip-when-sections-loading {
        visibility: hidden;
    }
}

$arrows-overflow: calc(var(--tlp-small-spacing) / 2);

.reorder-arrows {
    display: flex;
    position: absolute;
    z-index: 1;
    top: calc(-1 * $arrows-overflow);
    right: calc(var(--tlp-medium-spacing) / 2);
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    height: calc(100% + 2 * $arrows-overflow);
    transition:
        opacity ease-in-out 250ms,
        color ease-in-out 250ms;
    opacity: 0.5;
    color: var(--tlp-dimmed-color);

    &:focus-within,
    &:hover {
        z-index: 2;
        opacity: 1;
        color: var(--tlp-main-color);
    }

    &.reorder-arrows-when-sections-loading {
        visibility: hidden;
    }
}

.toc-display-level {
    font-variant-numeric: tabular-nums;
}

.section-title,
.table-of-content-section-title {
    color: var(--tlp-dimmed-color);
    font-size: 0.875rem;
    font-weight: 600;
    word-break: break-all;
}
</style>
