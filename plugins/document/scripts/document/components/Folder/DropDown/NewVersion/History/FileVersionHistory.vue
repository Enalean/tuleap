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
    <template v-if="is_filename_pattern_enforced">
        <hr class="tlp-modal-separator document-file-version-separator" />
        <h2 class="tlp-modal-subtitle">
            {{ $gettext("Latest versions") }}
        </h2>
        <p v-if="display_latest_version_text">
            {{ $gettext("Only the last 5 versions are displayed.") }}
            <a v-bind:href="history_url" target="_blank" rel="noopener noreferrer">
                {{ $gettext("View all versions") }}
                <i class="fa-solid fa-right-long"></i>
            </a>
        </p>
        <table class="tlp-table" v-if="!get_has_error">
            <thead>
                <tr>
                    <th class="document-file-version-version">
                        {{ $gettext("Version") }}
                    </th>
                    <th class="document-file-version-name">
                        {{ $gettext("Version name") }}
                    </th>
                    <th class="document-file-version-filename">
                        {{ $gettext("Filename") }}
                    </th>
                </tr>
            </thead>
            <tbody v-if="!get_has_error">
                <file-version-history-content
                    v-for="version of displayed_versions"
                    v-bind:key="version.id"
                    v-bind:version="version"
                />
                <tr v-if="is_version_history_empty && !are_versions_loading">
                    <td colspan="3" class="tlp-table-cell-empty">
                        {{ $gettext("No version history") }}
                    </td>
                </tr>
                <template v-if="are_versions_loading">
                    <file-version-history-skeleton v-for="line in 5" v-bind:key="line" />
                </template>
            </tbody>
        </table>
        <div v-if="get_has_error" class="tlp-alert-danger">
            {{ error_message }}
        </div>
    </template>
</template>

<script setup lang="ts">
import type { FileHistory, ItemFile } from "../../../../../type";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import FileVersionHistoryContent from "./FileVersionHistoryContent.vue";
import { getVersionHistory } from "../../../../../helpers/version-history-retriever";
import { handleErrorForHistoryVersion } from "../../../../../helpers/properties-helpers/error-handler-helper";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../../store/configuration";
import FileVersionHistorySkeleton from "./FileVersionHistorySkeleton.vue";

const props = defineProps<{ item: ItemFile }>();

const { is_filename_pattern_enforced, project_id } = useNamespacedState<ConfigurationState>(
    "configuration",
    ["is_filename_pattern_enforced", "project_id"]
);

let versions = ref<ReadonlyArray<FileHistory>>([]);
let has_error = ref(false);
let error_message = ref("");
let is_loading = ref(false);

onMounted(async (): Promise<void> => {
    if (is_filename_pattern_enforced.value) {
        is_loading.value = true;
        try {
            versions.value = await getVersionHistory(props.item);
        } catch (exception) {
            has_error.value = true;
            error_message.value = await handleErrorForHistoryVersion(exception);
        } finally {
            is_loading.value = false;
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

const history_url = computed((): string => {
    return `/plugins/docman/?group_id=${encodeURIComponent(
        project_id.value
    )}&id=${encodeURIComponent(props.item.id)}&action=details&section=history`;
});

const display_latest_version_text = computed((): boolean => {
    return versions.value.length >= 5;
});

const displayed_versions = computed((): ReadonlyArray<FileHistory> => {
    return versions.value;
});

const are_versions_loading = computed((): boolean => {
    return is_loading.value;
});

const get_has_error = computed((): boolean => {
    return has_error.value;
});

defineExpose({
    versions,
    is_version_history_empty,
    are_versions_loading,
    get_has_error,
});
</script>
