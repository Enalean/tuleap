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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <div class="document-history-versions">
        <h2 class="document-history-section-title">{{ $gettext("Versions") }}</h2>
        <table class="tlp-table">
            <thead>
                <tr>
                    <th class="tlp-table-cell-numeric">{{ $gettext("Version") }}</th>
                    <th>{{ $gettext("Date") }}</th>
                    <th>{{ $gettext("Author") }}</th>
                    <th>{{ $gettext("Version name") }}</th>
                    <th>{{ $gettext("Change Log") }}</th>
                    <th>{{ $gettext("Approval") }}</th>
                    <th></th>
                </tr>
            </thead>

            <history-versions-loading-state v-if="is_loading" />
            <history-versions-error-state v-else-if="is_in_error" v-bind:colspan="7" />
            <history-versions-empty-state v-else-if="is_empty" v-bind:colspan="7" />
            <history-versions-content v-else v-bind:item="item" v-bind:versions="versions" />
        </table>
    </div>
</template>

<script setup lang="ts">
import HistoryVersionsLoadingState from "./HistoryVersionsLoadingState.vue";
import HistoryVersionsErrorState from "./HistoryVersionsErrorState.vue";
import HistoryVersionsEmptyState from "./HistoryVersionsEmptyState.vue";
import HistoryVersionsContent from "./HistoryVersionsContent.vue";
import type { FileHistory, Item } from "../../type";
import { computed, onMounted, ref } from "vue";
import { getAllFileVersionHistory } from "../../api/version-rest-querier";
import { isEmbedded, isLink } from "../../helpers/type-check-helper";

const props = defineProps<{ item: Item }>();

const is_loading = ref(true);
const is_in_error = ref(false);
const versions = ref<readonly FileHistory[]>([]);
const is_empty = computed((): boolean => versions.value.length === 0);

onMounted(() => {
    if (isEmbedded(props.item) || isLink(props.item)) {
        return;
    }

    getAllFileVersionHistory(props.item.id).match(
        (file_versions: readonly FileHistory[]): void => {
            versions.value = file_versions;
            is_loading.value = false;
        },
        (): void => {
            is_in_error.value = true;
            is_loading.value = false;
        }
    );
});
</script>
