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
import DocumentView from "@/views/DocumentView.vue";
import DocumentHeader from "@/components/DocumentHeader.vue";
import useScrollToAnchor from "@/composables/useScrollToAnchor";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import GlobalErrorMessageModal from "@/components/GlobalErrorMessageModal.vue";
import type { GlobalErrorMessage } from "@/global-error-message-injection-key";
import { SET_GLOBAL_ERROR_MESSAGE } from "@/global-error-message-injection-key";

const item_id = strictInject(DOCUMENT_ID);
const store = strictInject(SECTIONS_STORE);

const configuration = strictInject(CONFIGURATION_STORE);
const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);

const { scrollToAnchor } = useScrollToAnchor();

const error_message = ref<GlobalErrorMessage | null>(null);
const has_error_message = computed(() => error_message.value !== null);
const container = ref<HTMLElement>();

provide(
    SET_GLOBAL_ERROR_MESSAGE,
    (message: GlobalErrorMessage | null) => (error_message.value = message),
);

onMounted(() => {
    store
        .loadSections(
            item_id,
            configuration.selected_tracker.value,
            can_user_edit_document,
            configuration.current_project.value,
        )
        .then(() => {
            const hash = window.location.hash.slice(1);
            if (hash) {
                scrollToAnchor(hash);
            }
        });

    container.value?.addEventListener("scroll", onScroll);
});

onUnmounted(() => {
    container.value?.removeEventListener("scroll", onScroll);
});

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
