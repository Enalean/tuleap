<!--
  - Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
    <tuleap-prose-mirror-toolbar
        ref="toolbar"
        class="artidoc-toolbar"
        v-bind:controller="controller"
        v-bind:text_elements="{
            bold: true,
            italic: true,
            code: true,
            quote: true,
        }"
        v-bind:script_elements="{
            subscript: true,
            superscript: true,
        }"
        v-bind:link_elements="{ link: true, unlink: true, image: true }"
        v-bind:list_elements="{
            ordered_list: true,
            bullet_list: true,
        }"
        v-bind:style_elements="{ subtitles: true, text: true, preformatted: true }"
        v-bind:additional_elements="[
            {
                position: 'at_the_end',
                target_name: TEXT_STYLES_ITEMS_GROUP,
                item_element: headings_button,
            },
        ]"
    />
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import {
    buildToolbarController,
    TEXT_STYLES_ITEMS_GROUP,
} from "@tuleap/prose-mirror-editor-toolbar";
import { TOOLBAR_BUS } from "@/toolbar-bus-injection-key";
import { strictInject } from "@tuleap/vue-strict-inject";
import { onClickActivateOrDeactivateToolbar } from "@/helpers/toolbar-activator";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import { createHeadingButton } from "@/toolbar/create-heading-button";
import { HEADINGS_BUTTON_STATE } from "@/headings-button-state-injection-key";
import { isUpdateSectionLevelEvent } from "@/toolbar/HeadingsButton";
import { IS_FREETEXT_ALLOWED } from "@/is-freetext-allowed";
import { getSectionsNumberer } from "@/sections/levels/SectionsNumberer";

const toolbar_bus = strictInject(TOOLBAR_BUS);
const headings_button_state = strictInject(HEADINGS_BUTTON_STATE);
const controller = buildToolbarController(toolbar_bus);
const is_freetext_allowed = strictInject(IS_FREETEXT_ALLOWED);

const toolbar = ref<HTMLElement | undefined>();

const props = defineProps<{
    sections: SectionsCollection;
    states_collection: SectionsStatesCollection;
}>();
const sections_numberer = getSectionsNumberer(props.sections);

const headings_button = is_freetext_allowed
    ? createHeadingButton(headings_button_state.active_section.value)
    : undefined;

if (headings_button) {
    headings_button.addEventListener("update-section-level", (event): void => {
        if (!isUpdateSectionLevelEvent(event)) {
            return;
        }

        const level = event.detail.level;
        const section = headings_button_state.active_section.value;

        if (section === undefined || section.value.level === level) {
            return;
        }

        const section_state = props.states_collection.getSectionState(section.value);
        section_state.is_section_in_edit_mode.value = section_state.initial_level.value !== level;

        sections_numberer.setSectionLevel(section, level);

        headings_button.section = section.value;
    });

    watch(
        () => headings_button_state.is_button_active.value,
        (is_button_active) => {
            headings_button.is_disabled = !is_button_active;
        },
    );

    watch(
        () => headings_button_state.active_section.value,
        (active_section) => {
            headings_button.section =
                active_section !== undefined ? active_section.value : undefined;
        },
    );
}

onMounted(() => {
    if (toolbar.value) {
        onClickActivateOrDeactivateToolbar(
            document,
            toolbar.value,
            toolbar_bus,
            headings_button_state,
        );
    }
});
</script>

<style lang="scss">
@use "@/themes/includes/zindex";
@use "@tuleap/burningparrot-theme/css/includes/global-variables";

.artidoc-toolbar {
    // Display block|flex is mandatory to avoid flickering with the toolbar
    display: flex;
    position: sticky;
    z-index: zindex.$toolbar;
    top: var(--artidoc-sticky-top-position);
    justify-content: center;
    width: 100%;
    border-bottom: 1px solid var(--tlp-neutral-normal-color);
    background: var(--tlp-white-color);
}

.artidoc-container-scrolled .artidoc-toolbar {
    border-bottom: 0;
    box-shadow: var(--tlp-sticky-header-shadow);
}

.artidoc-selected-level {
    color: var(--tlp-dimmed-color-lighter-50);
    cursor: default;

    &:hover {
        background: var(--tlp-white-color);
        color: var(--tlp-dimmed-color-lighter-50);
    }
}

.artidoc-menuitem-level {
    display: flex;
    flex-direction: row;
    gap: var(--tlp-small-spacing);
    align-items: baseline;
}

.artidoc-heading-icon {
    font-size: 0.625rem;
    font-weight: 700;
}
</style>
