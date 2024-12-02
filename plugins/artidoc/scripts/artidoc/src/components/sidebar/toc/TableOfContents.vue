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
    <ol ref="list" data-is-container="true">
        <li
            data-test="section-in-toc"
            v-for="(section, index) in sections"
            v-bind:key="section.id"
            v-bind:draggable="section_being_moved === null"
            v-bind:data-internal-id="section.internal_id"
            v-bind:class="{
                'section-moved-with-success':
                    just_moved_section?.internal_id === section.internal_id,
                'section-being-moved': section_being_moved?.internal_id === section.internal_id,
            }"
        >
            <span
                class="dragndrop-grip"
                data-test="dragndrop-grip"
                v-if="is_reorder_allowed"
                v-bind:class="{ 'dragndrop-grip-when-sections-loading': is_sections_loading }"
            >
                <dragndrop-grip-illustration />
            </span>

            <span v-if="is_sections_loading" class="tlp-skeleton-text"></span>
            <a
                v-else-if="isArtifactSection(section)"
                v-bind:href="`#section-${section.id}`"
                class="table-of-content-section-title"
                data-not-drag-handle="true"
            >
                {{ section.display_title }}
            </a>
            <span v-else class="table-of-content-section-title" data-not-drag-handle="true">
                {{ section.display_title }}
            </span>

            <span
                class="reorder-arrows"
                data-test="reorder-arrows"
                v-if="is_reorder_allowed"
                v-bind:class="{ 'reorder-arrows-when-sections-loading': is_sections_loading }"
                data-not-drag-handle="true"
            >
                <reorder-arrows
                    v-bind:is_first="index === 0"
                    v-bind:is_last="index === (sections?.length || 0) - 1"
                    v-bind:section="section"
                    v-on:moved-section-up-or-down="showJustSavedTemporaryFeedback"
                    v-on:moving-section-up-or-down="showSectionBeingMovedTemporaryFeedback"
                />
            </span>
        </li>
    </ol>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { onMounted, onUnmounted, ref } from "vue";
import type { Ref } from "vue";
import { isArtifactSection } from "@/helpers/artidoc-section.type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import DragndropGripIllustration from "@/components/sidebar/toc/DragndropGripIllustration.vue";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import ReorderArrows from "@/components/sidebar/toc/ReorderArrows.vue";
import { init } from "@tuleap/drag-and-drop";
import type { Drekkenov } from "@tuleap/drag-and-drop";
import { noop } from "@/helpers/noop";
import type { SuccessfulDropCallbackParameter } from "@tuleap/drag-and-drop/src";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import type { InternalArtidocSectionId } from "@/stores/useSectionsStore";
import { TEMPORARY_FLAG_DURATION_IN_MS } from "@/composables/temporary-flag-duration";

const { $gettext } = useGettext();

const { sections, is_sections_loading, moveSectionAtTheEnd, moveSectionBefore } =
    strictInject(SECTIONS_STORE);
const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);
const document_id = strictInject(DOCUMENT_ID);

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
                moveSectionAtTheEnd(document_id, moved_section).then(() =>
                    showJustSavedTemporaryFeedback(moved_section),
                );
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
            moveSectionBefore(document_id, moved_section, sibling).map(() => {
                showJustSavedTemporaryFeedback(moved_section);
            });
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

ol {
    height: var(--artidoc-sidebar-content-height);
    padding: 0 0 var(--tlp-medium-spacing);
    overflow: hidden auto;
    list-style-position: inside;
    color: var(--tlp-dimmed-color);
}

li {
    position: relative;
    padding: calc(var(--tlp-small-spacing) / 2) var(--tlp-medium-spacing);

    &:first-child {
        padding-top: 0;
    }

    &:has(> .dragndrop-grip) {
        padding-left: var(--tlp-large-spacing);
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
        display: none;
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

.section-title,
.table-of-content-section-title {
    color: var(--tlp-dimmed-color);
    font-size: 0.875rem;
    font-weight: 600;
}

@media (max-width: 1024px) {
    .table-of-contents-container {
        padding-top: 0;
    }

    ol {
        height: fit-content;
    }
}
</style>
