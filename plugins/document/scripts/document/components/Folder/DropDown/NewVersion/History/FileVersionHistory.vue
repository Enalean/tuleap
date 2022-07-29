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
        <p v-if="display_latest_version_text">
            <translate>Only the last 5 versions are displayed.</translate>
            <a v-bind:href="history_url" target="_blank" rel="noopener noreferrer">
                <translate>View all versions</translate>
                <i class="fas fa-long-arrow-alt-right"></i>
            </a>
        </p>
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
                <tr v-if="is_version_history_empty && !is_loading">
                    <td colspan="3" class="tlp-table-cell-empty" v-translate>No version history</td>
                </tr>
                <template v-if="is_loading">
                    <file-version-history-skeleton v-for="line in 5" v-bind:key="line" />
                </template>
            </tbody>
        </table>
        <div v-if="has_error" class="tlp-alert-danger">
            {{ error_message }}
        </div>
    </fragment>
</template>

<script setup lang="ts">
import type { FileHistory, ItemFile } from "../../../../../type";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import FileVersionHistoryContent from "./FileVersionHistoryContent.vue";
import { Fragment } from "vue-frag";
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
</script>
<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({});
</script>
