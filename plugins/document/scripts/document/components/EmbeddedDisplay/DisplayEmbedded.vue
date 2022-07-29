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
        <display-embedded-content v-if="has_loaded_without_error" data-test="embedded_content" />
        <display-embedded-spinner
            v-else-if="!does_document_have_any_error && is_loading"
            data-test="embedded_spinner"
        />
    </div>
</template>

<script setup lang="ts">
import DisplayEmbeddedSpinner from "./DisplayEmbeddedSpinner.vue";
import DisplayEmbeddedContent from "./DisplayEmbeddedContent.vue";
import { computed, onBeforeMount, onUnmounted, ref } from "vue";
import type { Item } from "../../type";
import {
    useActions,
    useMutations,
    useNamespacedActions,
    useNamespacedGetters,
    useNamespacedMutations,
} from "vuex-composition-helpers";
import type { ErrorGetters } from "../../store/error/error-getters";
import type { PreferenciesActions } from "../../store/preferencies/preferencies-actions";
import { useRoute } from "../../helpers/use-router";

const embedded_file = ref<Item | null>(null);
const is_loading = ref(false);

const { does_document_have_any_error } = useNamespacedGetters<
    Pick<ErrorGetters, "does_document_have_any_error">
>("error", ["does_document_have_any_error"]);

const has_loaded_without_error = computed((): boolean => {
    return !does_document_have_any_error.value && !is_loading.value;
});

const { loadDocumentWithAscendentHierarchy } = useActions(["loadDocumentWithAscendentHierarchy"]);
const { updateCurrentlyPreviewedItem } = useMutations(["updateCurrentlyPreviewedItem"]);
const { getEmbeddedFileDisplayPreference } = useNamespacedActions<PreferenciesActions>(
    "preferencies",
    ["getEmbeddedFileDisplayPreference"]
);
const { shouldDisplayEmbeddedInLargeMode } = useNamespacedMutations("preferencies", [
    "shouldDisplayEmbeddedInLargeMode",
]);

const route = useRoute();

onBeforeMount(async () => {
    is_loading.value = true;
    embedded_file.value = await loadDocumentWithAscendentHierarchy(
        parseInt(route.params.item_id, 10)
    );

    if (!embedded_file.value) {
        return;
    }

    updateCurrentlyPreviewedItem(embedded_file.value);
    const preference = await getEmbeddedFileDisplayPreference(embedded_file.value);
    shouldDisplayEmbeddedInLargeMode(!preference);
    is_loading.value = false;
});

onUnmounted(() => {
    updateCurrentlyPreviewedItem(null);
});
</script>

<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({});
</script>
