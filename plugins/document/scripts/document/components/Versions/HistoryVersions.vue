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
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <section class="tlp-pane-section">
                    <table class="tlp-table">
                        <thead>
                            <tr>
                                <th class="tlp-table-cell-numeric">{{ $gettext("Version") }}</th>
                                <th>{{ $gettext("Date") }}</th>
                                <th>{{ $gettext("Author") }}</th>
                                <th>{{ $gettext("Version name") }}</th>
                                <th>{{ $gettext("Change Log") }}</th>
                                <th v-if="!is_link">{{ $gettext("Approval") }}</th>
                                <th v-if="should_display_source_column">
                                    {{ $gettext("Source") }}
                                </th>
                                <th v-if="!is_link"></th>
                            </tr>
                        </thead>

                        <history-versions-loading-state v-if="is_loading" v-bind:item="item" />
                        <history-versions-error-state
                            v-else-if="is_in_error"
                            v-bind:colspan="colspan"
                        />
                        <history-versions-empty-state
                            v-else-if="is_empty"
                            v-bind:colspan="colspan"
                        />
                        <history-versions-content-for-link
                            v-else-if="is_link"
                            v-bind:versions="link_versions"
                        />
                        <history-versions-content
                            v-else
                            v-bind:item="item"
                            v-bind:versions="file_versions"
                            v-bind:load-versions="loadVersions"
                        />
                    </table>
                </section>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import HistoryVersionsLoadingState from "./HistoryVersionsLoadingState.vue";
import HistoryVersionsErrorState from "./HistoryVersionsErrorState.vue";
import HistoryVersionsEmptyState from "./HistoryVersionsEmptyState.vue";
import HistoryVersionsContent from "./HistoryVersionsContent.vue";
import type { EmbeddedFileVersion, FileHistory, Item, LinkVersion } from "../../type";
import { computed, onMounted, ref } from "vue";
import {
    getAllEmbeddedFileVersionHistory,
    getAllFileVersionHistory,
    getAllLinkVersionHistory,
} from "../../api/version-rest-querier";
import { isEmbedded, isLink } from "../../helpers/type-check-helper";
import HistoryVersionsContentForLink from "./HistoryVersionsContentForLink.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SHOULD_DISPLAY_SOURCE_COLUMN_FOR_VERSIONS } from "../../injection-keys";

const props = defineProps<{ item: Item }>();

const is_loading = ref(true);
const is_in_error = ref(false);
const file_versions = ref<readonly FileHistory[] | readonly EmbeddedFileVersion[]>([]);
const link_versions = ref<readonly LinkVersion[]>([]);
const is_empty = computed(
    (): boolean => file_versions.value.length === 0 && link_versions.value.length === 0,
);

const should_display_source_column_for_versions = strictInject(
    SHOULD_DISPLAY_SOURCE_COLUMN_FOR_VERSIONS,
);

const is_link = computed((): boolean => isLink(props.item));
const is_embedded = computed((): boolean => isEmbedded(props.item));

const should_display_source_column = computed(
    (): boolean =>
        should_display_source_column_for_versions && !is_embedded.value && !is_link.value,
);
const colspan = computed((): number => {
    if (is_link.value) {
        return 5;
    }

    if (should_display_source_column.value) {
        return 8;
    }

    return 7;
});

onMounted(() => {
    loadVersions();
});

function loadVersions(): void {
    is_loading.value = true;

    if (is_link.value) {
        getAllLinkVersionHistory(props.item.id).match(
            (versions: readonly LinkVersion[]): void => {
                link_versions.value = versions;
                is_loading.value = false;
            },
            (): void => {
                is_in_error.value = true;
                is_loading.value = false;
            },
        );
        return;
    }

    if (is_embedded.value) {
        getAllEmbeddedFileVersionHistory(props.item.id).match(
            (versions: readonly EmbeddedFileVersion[]): void => {
                file_versions.value = versions;
                is_loading.value = false;
            },
            (): void => {
                is_in_error.value = true;
                is_loading.value = false;
            },
        );
        return;
    }

    getAllFileVersionHistory(props.item.id).match(
        (versions: readonly FileHistory[]): void => {
            file_versions.value = versions;
            is_loading.value = false;
        },
        (): void => {
            is_in_error.value = true;
            is_loading.value = false;
        },
    );
}
</script>
