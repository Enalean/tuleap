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
            v-bind:draggable="section_being_moved === null"
            v-bind:data-internal-id="section.value.internal_id"
            v-bind:class="{
                'section-moved-with-success':
                    just_moved_section?.internal_id === section.value.internal_id,
                'section-being-moved':
                    section_being_moved?.internal_id === section.value.internal_id,
            }"
        >
            <span
                class="dragndrop-grip"
                data-test="dragndrop-grip"
                v-if="is_reorder_allowed"
                v-bind:class="{ 'dragndrop-grip-when-sections-loading': is_loading_sections }"
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
            >
                <reorder-arrows
                    v-bind:is_first="index === 0"
                    v-bind:is_last="index === sections_collection.sections.value.length - 1"
                    v-bind:section="section"
                    v-bind:sections_reorderer="sections_reorderer"
                    v-on:moved-section-up-or-down="showJustSavedTemporaryFeedback"
                    v-on:moving-section-up-or-down="showSectionBeingMovedTemporaryFeedback"
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
import { noop } from "@/helpers/noop";
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

const { $gettext } = useGettext();

const is_loading_sections = strictInject(IS_LOADING_SECTIONS);
const sections_collection = strictInject(SECTIONS_COLLECTION);
const states_collection = strictInject(SECTIONS_STATES_COLLECTION);
const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);
const document_id = strictInject(DOCUMENT_ID);
const setGlobalErrorMessage = strictInject(SET_GLOBAL_ERROR_MESSAGE);

const sections_reorderer = buildSectionsReorderer(sections_collection);

const is_reorder_allowed = can_user_edit_document;

const list = ref<HTMLElement>();

let drek: Drekkenov | undefined = undefined;

const just_moved_section: Ref<null | InternalArtidocSectionId> = ref(null);
const section_being_moved: Ref<null | InternalArtidocSectionId> = ref(null);

const showJustSavedTemporaryFeedback = (moved_section: InternalArtidocSectionId): void => {
    just_moved_section.value = moved_section;
    section_being_moved.value = null;

    setTimeout(() => {
        just_moved_section.value = null;
    }, TEMPORARY_FLAG_DURATION_IN_MS);
};

const showSectionBeingMovedTemporaryFeedback = (moved_section: InternalArtidocSectionId): void => {
    section_being_moved.value = moved_section;
};

const handleReorderingFault = (fault: Fault): void => {
    section_being_moved.value = null;

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
        onDrop: (context: SuccessfulDropCallbackParameter): void => {
            if (context.dropped_element.dataset.internalId === undefined) {
                return;
            }

            const moved_section = {
                internal_id: context.dropped_element.dataset.internalId,
            };

            showSectionBeingMovedTemporaryFeedback(moved_section);

            if (context.next_sibling === null) {
                sections_reorderer.moveSectionAtTheEnd(document_id, moved_section).match(() => {
                    showJustSavedTemporaryFeedback(moved_section);
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
                showJustSavedTemporaryFeedback(moved_section);
            }, handleReorderingFault);
        },
        cleanupAfterDragCallback: noop,
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

.section-moved-with-success {
    animation: pulse-section 500ms ease-in-out;
    background-color: var(--tlp-success-color-lighter-90);
}

.section-being-moved {
    animation: blink-toc-item 1200ms ease-in-out alternate infinite;
}

.section-moved-with-success,
.section-being-moved,
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
    > .reorder-arrows {
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
}
</style>
