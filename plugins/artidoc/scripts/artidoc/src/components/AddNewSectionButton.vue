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
    <div class="tlp-dropdown artidoc-add-new-section-container">
        <button
            type="button"
            class="tlp-button-primary artidoc-add-new-section-solo-button"
            v-bind:title="add_new_section_label"
            ref="trigger_element"
            data-test="artidoc-add-new-section-trigger"
        >
            <i class="fa-solid fa-plus" role="img"></i>
        </button>
        <div
            ref="dropdown_element"
            role="menu"
            class="tlp-dropdown-menu artidoc-add-new-section-submenu"
        >
            <button
                type="button"
                class="tlp-dropdown-menu-item"
                v-on:click="addNewFreetextSection"
                data-test="add-freetext-section"
            >
                {{ $gettext("Add freetext") }}
            </button>
            <button
                type="button"
                class="tlp-dropdown-menu-item"
                v-on:click="addNewArtifactSection"
                data-test="add-new-section"
            >
                {{ add_new_requirement_label }}
            </button>
            <button
                type="button"
                class="tlp-dropdown-menu-item"
                v-on:click="addExistingSection"
                data-test="add-existing-section"
            >
                {{ add_existing_section_label }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { OPEN_CONFIGURATION_MODAL_BUS } from "@/stores/useOpenConfigurationModalBusStore";
import { OPEN_ADD_EXISTING_SECTION_MODAL_BUS } from "@/composables/useOpenAddExistingSectionModalBus";
import { isTrackerWithSubmittableSection } from "@/configuration/AllowedTrackersCollection";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import type { PositionForSection } from "@/sections/save/SectionsPositionsForSaveRetriever";
import type { ArtidocSection, PendingArtifactSection } from "@/helpers/artidoc-section.type";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { computed, onMounted, onUnmounted, ref } from "vue";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import type { InsertSections } from "@/sections/insert/SectionsInserter";

const props = defineProps<{
    position: PositionForSection;
    sections_inserter: InsertSections;
}>();

const selected_tracker = strictInject(SELECTED_TRACKER);

const { $gettext } = useGettext();

const add_new_section_label = $gettext("Add new section");
const add_new_requirement_label = computed((): string =>
    selected_tracker.value.mapOr(
        (tracker) =>
            $gettext("Add new %{tracker_label}", {
                tracker_label: tracker.item_name,
            }),
        add_new_section_label,
    ),
);
const add_existing_section_label = computed((): string =>
    selected_tracker.value.mapOr(
        (tracker) =>
            $gettext("Import existing %{tracker_label}", {
                tracker_label: tracker.item_name,
            }),
        $gettext("Import existing section"),
    ),
);

const configuration_bus = strictInject(OPEN_CONFIGURATION_MODAL_BUS);
const add_existing_section_bus = strictInject(OPEN_ADD_EXISTING_SECTION_MODAL_BUS);

const trigger_element = ref<HTMLElement>();
const dropdown_element = ref<HTMLElement>();
let dropdown: Dropdown | null = null;

const is_tracker_with_submittable_section = computed(() =>
    selected_tracker.value.mapOr(isTrackerWithSubmittableSection, false),
);

onMounted(() => {
    if (trigger_element.value && dropdown_element.value) {
        dropdown = createDropdown(trigger_element.value, {
            trigger: "click",
            dropdown_menu: dropdown_element.value,
            keyboard: true,
        });
    }
});

onUnmounted(() => {
    dropdown?.destroy();
});

function addExistingSection(): void {
    dropdown?.hide();

    selected_tracker.value.match(
        openAddExistingSectionModal,
        openConfigurationModalBeforeInsertingExistingSection,
    );
}

function openAddExistingSectionModal(): void {
    add_existing_section_bus.openModal(props.position, (section: ArtidocSection): void => {
        props.sections_inserter.insertSection(section, props.position);
    });
}

function openConfigurationModalBeforeInsertingExistingSection(): void {
    configuration_bus.openModal(openAddExistingSectionModal);
}

function addNewArtifactSection(): void {
    dropdown?.hide();
    if (!is_tracker_with_submittable_section.value) {
        openConfigurationModalBeforeInsertingNewSection();
        return;
    }

    insertNewSection();
}

function addNewFreetextSection(): void {
    dropdown?.hide();

    props.sections_inserter.insertSection(FreetextSectionFactory.pending(), props.position);
}

function openConfigurationModalBeforeInsertingNewSection(): void {
    configuration_bus.openModal(insertNewSection);
}

function insertNewSection(): void {
    selected_tracker.value.apply((tracker) => {
        if (!isTrackerWithSubmittableSection(tracker)) {
            return;
        }
        const section: PendingArtifactSection =
            PendingArtifactSectionFactory.overrideFromTracker(tracker);

        props.sections_inserter.insertSection(section, props.position);
    });
}
</script>

<style lang="scss">
@use "@/themes/includes/viewport-breakpoint";
@use "@/themes/includes/add-buttons-reveal";

.is-aside-expanded + .document-content {
    .artidoc-add-new-section-container {
        @media screen and (max-width: #{viewport-breakpoint.$medium-screen-size-when-document-sidebar-is-expanded}) {
            margin: 0;
        }
    }
}

.is-aside-collapsed + .document-content {
    .artidoc-add-new-section-container {
        @media screen and (max-width: #{viewport-breakpoint.$medium-screen-size-when-document-sidebar-is-collapsed}) {
            margin: 0;
        }
    }
}

li:first-child > .artidoc-add-new-section-container {
    padding-top: var(--tlp-small-spacing);
}
</style>

<style scoped lang="scss">
@use "@/themes/includes/whitespace";
@use "@/themes/includes/size";
@use "@/themes/includes/zindex";

.artidoc-add-new-section-container {
    --add-new-section-button-background-color: var(--tlp-neutral-light-color);
    --add-new-section-button-text-color: var(--tlp-typo-default-text-color);

    margin: 0 0 0 calc(-1 * #{size.$add-section-button-container-width});
    padding: whitespace.$add-section-button-container-vertical-padding
        whitespace.$add-section-button-container-horizontal-padding;
    transition: opacity ease-in-out 250ms 250ms;

    &:has(button:hover, button:focus-within) {
        z-index: zindex.$dropdown;

        --add-new-section-button-background-color: var(--tlp-main-color);
        --add-new-section-button-text-color: var(--tlp-white-color);
    }

    @media print {
        display: none;
    }
}

.artidoc-add-new-section-solo-button {
    width: size.$add-section-button-size;
    height: size.$add-section-button-size;
    padding: 0;
    transition: all ease-in-out 150ms;
    border-radius: 50%;
    border-color: var(--add-new-section-button-background-color);
    background: var(--add-new-section-button-background-color);
    color: var(--add-new-section-button-text-color);

    &:focus-within,
    &:hover {
        border-color: var(--add-new-section-button-background-color);
        background: var(--add-new-section-button-background-color);
        color: var(--add-new-section-button-text-color);
    }
}

.artidoc-add-new-section-submenu {
    transform: translate(-8px, 8px);
}
</style>
