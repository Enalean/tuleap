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
    <section
        v-bind:data-test="
            section_state.is_section_in_edit_mode.value ? 'section-edition' : undefined
        "
    >
        <div class="artidoc-dropdown-container">
            <section-dropdown
                v-bind:editor="editor"
                v-bind:section="section.value"
                v-bind:section_state="section_state"
                v-if="!is_loading_sections"
            />
        </div>

        <article
            class="document-section"
            v-bind:class="{
                'document-section-is-being-saved': section_state.is_being_saved.value === true,
                'document-section-is-just-saved': section_state.is_just_saved.value === true,
                'document-section-is-just-refreshed':
                    section_state.is_just_refreshed.value === true,
                'document-section-is-in-error': is_in_error,
                'document-section-is-outdated': is_outdated,
            }"
        >
            <section-header
                class="section-header"
                v-if="!is_loading_sections"
                v-bind:title="section.value.display_title"
            />
            <section-header-skeleton v-if="is_loading_sections" class="section-header" />
            <section-description
                v-bind:editable_description="editable_description"
                v-bind:readonly_description="getReadonlyDescription()"
                v-bind:is_edit_mode="section_state.is_section_in_edit_mode.value"
                v-bind:post_information="section_attachments_manager.getPostInformation()"
                v-bind:upload_file="upload_file"
                v-bind:project_id="getProjectId()"
                v-bind:title="section.value.display_title"
                v-bind:input_section_content="inputSectionContent"
                v-bind:is_there_any_change="is_there_any_change"
            />
            <section-footer
                v-bind:editor="editor"
                v-bind:section="section.value"
                v-bind:section_state="section_state"
            />
        </article>
    </section>
</template>

<script setup lang="ts">
import { watch } from "vue";
import type { Ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";

import { isPendingArtifactSection, isArtifactSection } from "@/helpers/artidoc-section.type";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import SectionHeader from "./header/SectionHeader.vue";
import SectionDescription from "./description/SectionDescription.vue";
import SectionDropdown from "./header/SectionDropdown.vue";
import SectionHeaderSkeleton from "./header/SectionHeaderSkeleton.vue";
import SectionFooter from "./footer/SectionFooter.vue";

import { SET_GLOBAL_ERROR_MESSAGE } from "@/global-error-message-injection-key";
import { IS_LOADING_SECTIONS } from "@/is-loading-sections-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { SECTIONS_STATES_COLLECTION } from "@/sections/sections-states-collection-injection-key";
import { TEMPORARY_FLAG_DURATION_IN_MS } from "@/composables/temporary-flag-duration";
import { SECTIONS_COLLECTION } from "@/sections/sections-collection-injection-key";

import { useSectionEditor } from "@/composables/useSectionEditor";
import { useUploadFile } from "@/composables/useUploadFile";

import { getPendingSectionsReplacer } from "@/sections/PendingSectionsReplacer";
import { getSectionsUpdater } from "@/sections/SectionsUpdater";
import { getSectionsRemover } from "@/sections/SectionsRemover";
import { getSectionsPositionsForSaveRetriever } from "@/sections/SectionsPositionsForSaveRetriever";
import { getSectionErrorManager } from "@/sections/SectionErrorManager";
import { getSectionAttachmentFilesManager } from "@/sections/SectionAttachmentFilesManager";

const props = defineProps<{ section: ReactiveStoredArtidocSection }>();
const setGlobalErrorMessage = strictInject(SET_GLOBAL_ERROR_MESSAGE);
const is_loading_sections = strictInject(IS_LOADING_SECTIONS);
const sections_collection = strictInject(SECTIONS_COLLECTION);
const document_id = strictInject(DOCUMENT_ID);
const states_collection = strictInject(SECTIONS_STATES_COLLECTION);
const section_state = states_collection.getSectionState(props.section.value);

function addTemporaryFlag(flag: Ref<boolean>): void {
    setTimeout(() => {
        flag.value = false;
    }, TEMPORARY_FLAG_DURATION_IN_MS);
}

watch(
    () => section_state.is_being_saved.value,
    () => {
        if (!section_state.is_being_saved.value) {
            return;
        }
        addTemporaryFlag(section_state.is_being_saved);
    },
);

watch(
    () => section_state.is_just_saved.value,
    () => {
        if (!section_state.is_just_saved.value) {
            return;
        }
        addTemporaryFlag(section_state.is_just_saved);
    },
);

watch(
    () => section_state.is_just_refreshed.value,
    () => {
        if (!section_state.is_just_refreshed.value) {
            return;
        }
        addTemporaryFlag(section_state.is_just_refreshed);
    },
);

const section_attachments_manager = getSectionAttachmentFilesManager(props.section, document_id);

const upload_file = useUploadFile(props.section.value.id, section_attachments_manager);

const { $gettext } = useGettext();

const editor = useSectionEditor(
    document_id,
    props.section,
    section_state,
    getSectionErrorManager(section_state),
    section_attachments_manager,
    getPendingSectionsReplacer(sections_collection),
    getSectionsUpdater(sections_collection),
    getSectionsRemover(sections_collection, states_collection),
    getSectionsPositionsForSaveRetriever(sections_collection),
    (error: string) => {
        setGlobalErrorMessage({
            message: $gettext("An error occurred while removing the section."),
            details: error,
        });
    },
);

const { is_in_error, is_outdated } = section_state;

const { inputSectionContent, is_there_any_change, editable_description, getReadonlyDescription } =
    editor.editor_section_content;

function getProjectId(): number {
    if (isArtifactSection(props.section.value)) {
        return props.section.value.artifact.tracker.project.id;
    }

    return isPendingArtifactSection(props.section.value)
        ? props.section.value.tracker.project.id
        : 0;
}
</script>

<style lang="scss" scoped>
@use "@/themes/includes/whitespace";

section {
    display: grid;
    grid-template-columns: auto whitespace.$section-right-padding;
}

.artidoc-dropdown-container {
    display: flex;
    justify-content: center;
    order: 1;

    @media print {
        display: none;
    }
}

.document-section {
    display: flex;
    flex-direction: column;
}

.section-header {
    margin-bottom: var(--tlp-medium-spacing);
    border-bottom: 1px solid var(--tlp-neutral-normal-color);
    background: var(--tuleap-artidoc-section-background);
}
</style>
