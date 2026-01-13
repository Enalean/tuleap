<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div>
        <display-embedded-content
            v-if="has_loaded_without_error"
            v-bind:embedded_file="embedded_file"
            v-bind:content_to_display="embedded_content"
            v-bind:specific_version_number="specific_version_number"
            v-bind:embedded_file_display_preference="embedded_file_display_preference"
            v-on:update_display_preference="(value) => (embedded_file_display_preference = value)"
            data-test="embedded_content"
        />
        <display-embedded-spinner
            v-else-if="!does_document_have_any_error && is_loading"
            data-test="embedded_spinner"
        />
    </div>
</template>

<script setup lang="ts">
import DisplayEmbeddedSpinner from "./DisplayEmbeddedSpinner.vue";
import DisplayEmbeddedContent from "./DisplayEmbeddedContent.vue";
import { computed, onBeforeMount, onMounted, onUnmounted, ref, watch } from "vue";
import type {
    Embedded,
    EmbeddedFileDisplayPreference,
    EmbeddedFileSpecificVersionContent,
} from "../../type";
import { EMBEDDED_FILE_DISPLAY_LARGE } from "../../type";
import { useActions, useMutations, useNamespacedGetters, useStore } from "vuex-composition-helpers";
import type { ErrorGetters } from "../../store/error/error-getters";
import { isEmbedded } from "../../helpers/type-check-helper";
import { getEmbeddedFileVersionContent } from "../../api/version-rest-querier";
import type { ItemHasJustBeenUpdatedEvent } from "../../helpers/emitter";
import emitter from "../../helpers/emitter";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT, USER_ID } from "../../configuration-keys";
import { getEmbeddedFileDisplayPreference } from "../../helpers/preferences/embedded-file-display-preferences";
import { SHOW_DOCUMENT_IN_TITLE } from "../../injection-keys";

const $store = useStore();

const props = withDefaults(defineProps<{ item_id: number; version_id?: number | null }>(), {
    version_id: null,
});

const embedded_file = ref<Embedded | null>(null);
const embedded_content = ref("");
const is_loading = ref(false);
const is_loading_specific_version_in_error = ref(false);
const specific_version_number = ref<number | null>(null);
const embedded_file_display_preference = ref<EmbeddedFileDisplayPreference>(
    EMBEDDED_FILE_DISPLAY_LARGE,
);

const user_id = strictInject(USER_ID);
const project = strictInject(PROJECT);

const { does_document_have_any_error } = useNamespacedGetters<
    Pick<ErrorGetters, "does_document_have_any_error">
>("error", ["does_document_have_any_error"]);

const has_an_error = computed(
    (): boolean => does_document_have_any_error.value || is_loading_specific_version_in_error.value,
);

const has_loaded_without_error = computed((): boolean => !has_an_error.value && !is_loading.value);

const { loadDocumentWithAscendentHierarchy } = useActions(["loadDocumentWithAscendentHierarchy"]);
const { updateCurrentlyPreviewedItem } = useMutations(["updateCurrentlyPreviewedItem"]);

const show_document_in_title = strictInject(SHOW_DOCUMENT_IN_TITLE);

onMounted(() => {
    show_document_in_title.value = true;
});

watch(() => props.version_id, loadContent);

function loadContent(): void {
    is_loading.value = true;
    specific_version_number.value = null;
    if (props.version_id) {
        getEmbeddedFileVersionContent(props.version_id).match(
            (specific_version: EmbeddedFileSpecificVersionContent) => {
                specific_version_number.value = specific_version.version_number;
                embedded_content.value = specific_version.content;
                is_loading.value = false;
            },
            () => {
                is_loading_specific_version_in_error.value = true;
                is_loading.value = false;
            },
        );
    } else {
        if (!embedded_file.value) {
            embedded_content.value = "";
        } else if (!embedded_file.value.embedded_file_properties) {
            embedded_content.value = "";
        } else if (!embedded_file.value.embedded_file_properties.content) {
            embedded_content.value = "";
        } else {
            embedded_content.value = embedded_file.value.embedded_file_properties.content;
        }
        is_loading.value = false;
    }
}

onBeforeMount(async () => {
    is_loading.value = true;
    const item = await loadDocumentWithAscendentHierarchy(props.item_id);
    if (!item || !isEmbedded(item)) {
        return;
    }

    embedded_file.value = item;

    updateCurrentlyPreviewedItem(embedded_file.value);
    const preference = await getEmbeddedFileDisplayPreference(
        $store,
        embedded_file.value,
        user_id,
        project.id,
    );
    preference.apply(
        (value: EmbeddedFileDisplayPreference) => (embedded_file_display_preference.value = value),
    );
    loadContent();
    emitter.on("item-has-just-been-updated", updateDisplayedContent);
});

onUnmounted(() => {
    emitter.off("item-has-just-been-updated", updateDisplayedContent);
    updateCurrentlyPreviewedItem(null);
});

function updateDisplayedContent(item: ItemHasJustBeenUpdatedEvent): void {
    if (isEmbedded(item.item)) {
        embedded_content.value = item.item.embedded_file_properties?.content;
    }
}
</script>
