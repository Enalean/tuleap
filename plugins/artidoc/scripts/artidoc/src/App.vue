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
    <section class="artidoc-app">
        <document-header />
        <div class="artidoc-container" ref="container">
            <document-view />
        </div>
        <global-error-message-modal v-if="has_error_message" v-bind:error="error_message" />
    </section>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, provide, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import DocumentView from "@/views/DocumentView.vue";
import DocumentHeader from "@/components/DocumentHeader.vue";
import useScrollToAnchor from "@/composables/useScrollToAnchor";
import GlobalErrorMessageModal from "@/components/GlobalErrorMessageModal.vue";
import type { GlobalErrorMessage } from "@/global-error-message-injection-key";
import { SET_GLOBAL_ERROR_MESSAGE } from "@/global-error-message-injection-key";
import {
    IS_LOADING_SECTIONS,
    IS_LOADING_SECTIONS_FAILED,
} from "@/is-loading-sections-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { getSectionsLoader } from "@/sections/SectionsLoader";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import { getSectionsNumberer } from "@/sections/levels/SectionsNumberer";
import { buildSectionsBelowArtifactsDetector } from "@/sections/levels/SectionsBelowArtifactsDetector";
import { SECTIONS_BELOW_ARTIFACTS } from "@/sections-below-artifacts-injection-key";
import { watchUpdateSectionsReadonlyFields } from "@/sections/readonly-fields/ReadonlyFieldsWatcher";
import { SELECTED_FIELDS } from "@/configuration/SelectedFieldsCollection";

const { scrollToAnchor } = useScrollToAnchor();

const error_message = ref<GlobalErrorMessage | null>(null);
const has_error_message = computed(() => error_message.value !== null);
const container = ref<HTMLElement>();
const is_loading_sections = ref(true);
const is_loading_failed = strictInject(IS_LOADING_SECTIONS_FAILED);
const sections_collection = strictInject(SECTIONS_COLLECTION);
const document_id = strictInject(DOCUMENT_ID);
const selected_fields = strictInject(SELECTED_FIELDS);
const sections_numberer = getSectionsNumberer(sections_collection);
const bad_sections_detector = buildSectionsBelowArtifactsDetector();
const bad_sections = ref<ReadonlyArray<string>>([]);

provide(IS_LOADING_SECTIONS, is_loading_sections);
provide(
    SET_GLOBAL_ERROR_MESSAGE,
    (message: GlobalErrorMessage | null) => (error_message.value = message),
);
provide(SECTIONS_BELOW_ARTIFACTS, bad_sections);

getSectionsLoader(document_id)
    .loadSections()
    .match(
        (collection) => {
            sections_collection.replaceAll(collection.map((section) => ref(section)));
            sections_numberer.updateSectionsLevels();
            bad_sections.value = bad_sections_detector.detect(sections_collection.sections.value);
            is_loading_sections.value = false;

            const hash = window.location.hash.slice(1);
            if (hash) {
                scrollToAnchor(hash);
            }
        },
        () => {
            sections_collection.replaceAll([]);

            is_loading_sections.value = false;
            is_loading_failed.value = true;
        },
    );

onMounted(() => {
    container.value?.addEventListener("scroll", onScroll);
});

onUnmounted(() => {
    container.value?.removeEventListener("scroll", onScroll);
});

watchUpdateSectionsReadonlyFields(
    sections_collection,
    selected_fields,
    document_id,
    is_loading_sections,
    is_loading_failed,
);

function onScroll(): void {
    // Magic value to wait that a few pixels have been scrolled down before applying a dropshadow
    // 16px ≃ --tlp-medium-spacing
    const threshold = 16;

    if (!container.value) {
        return;
    }

    if (container.value.scrollTop > threshold) {
        container.value.classList.add("artidoc-container-scrolled");
    } else {
        container.value.classList.remove("artidoc-container-scrolled");
    }
}
</script>

<style lang="scss">
@use "@/themes/artidoc";
@use "pkg:@tuleap/prose-mirror-editor";

html {
    scroll-behavior: smooth;
}

.artidoc-container {
    flex: 1 1 auto;
    height: var(--artidoc-container-height);
    overflow: auto;
    border-top: 1px solid var(--tlp-neutral-normal-color);
}
</style>
