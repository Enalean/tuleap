<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <fragment v-if="is_filename_pattern_enforced">
        <hr class="tlp-modal-separator document-file-version-separator" />
        <h2 class="tlp-modal-subtitle" v-translate>Latest versions</h2>
        <p v-translate>Only the last 5 versions are displayed.</p>
        <table class="tlp-table" v-if="!has_error">
            <thead>
                <tr>
                    <th class="document-file-version-version" v-translate>Version</th>
                    <th class="document-file-version-name" v-translate>Version name</th>
                    <th class="document-file-version-filename" v-translate>Filename</th>
                </tr>
            </thead>
            <tbody v-if="!has_error">
                <file-version-history-content
                    v-for="version of versions"
                    v-bind:key="version.id"
                    v-bind:version="version"
                />
                <tr v-if="is_version_history_empty">
                    <td colspan="3" class="tlp-table-cell-empty" v-translate>No version history</td>
                </tr>
            </tbody>
        </table>
        <div v-if="has_error" class="tlp-alert-danger">
            {{ error_message }}
        </div>
    </fragment>
</template>

<script setup lang="ts">
import type { FileHistory, ItemFile } from "../../../../../type";
import { computed, onBeforeUnmount, onMounted, ref } from "@vue/composition-api";
import FileVersionHistoryContent from "./FileVersionHistoryContent.vue";
import { Fragment } from "vue-frag";
import { getVersionHistory } from "../../../../../helpers/version-history-retriever";
import { handleErrorForHistoryVersion } from "../../../../../helpers/properties-helpers/error-handler-helper";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../../store/configuration";

const props = defineProps<{ item: ItemFile }>();

const { is_filename_pattern_enforced } = useNamespacedState<ConfigurationState>("configuration", [
    "is_filename_pattern_enforced",
]);

let versions = ref<ReadonlyArray<FileHistory>>([]);
let has_error = ref(false);
let error_message = ref("");

onMounted(async (): Promise<void> => {
    if (is_filename_pattern_enforced.value) {
        try {
            versions.value = await getVersionHistory(props.item);
        } catch (exception) {
            has_error.value = true;
            error_message.value = await handleErrorForHistoryVersion(exception);
        }
    }
});

onBeforeUnmount((): void => {
    has_error.value = false;
    error_message.value = "";
});

const is_version_history_empty = computed((): boolean => {
    return versions.value.length === 0;
});
</script>
<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>
