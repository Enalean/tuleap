<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <section v-if="item && folder_statistics">
        <document-details-tabs v-bind:item="item" v-bind:active_tab="StatisticsTab" />
        <div class="tlp-framed-horizontally">
            <div class="tlp-alert-info">
                <ul>
                    <li>
                        {{
                            $gettext(
                                "The whole folder sub-tree is taken in account for these statistics.",
                            )
                        }}
                    </li>
                    <li>
                        {{
                            $gettext(
                                "To compute the size, only the last versions of 'file' and 'embedded' file documents are taken in account.",
                            )
                        }}
                    </li>
                </ul>
            </div>
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">{{ $gettext("Summary") }}</h1>
                    </div>
                    <section class="tlp-pane-section">
                        <div class="tlp-property">
                            <label class="tlp-label">{{ $gettext("Size") }}</label>
                            <p>
                                {{ folder_statistics.size }}
                            </p>
                        </div>
                        <div class="tlp-property">
                            <label class="tlp-label">{{
                                $gettext("Number of items in this folder")
                            }}</label>
                            <p>{{ folder_statistics.count }}</p>
                        </div>
                    </section>
                </div>
            </section>
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">{{ $gettext("Details") }}</h1>
                    </div>
                    <section class="tlp-pane-section">
                        <p>
                            {{ $gettext("This table shows the number of elements of each type.") }}
                        </p>
                        <table class="tlp-table">
                            <thead>
                                <tr>
                                    <th>{{ $gettext("Item type") }}</th>
                                    <th>{{ $gettext("Number of item") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="folder_statistics.types.length === 0">
                                    <td colspan="7" class="tlp-table-cell-empty">
                                        {{ $gettext("There isn't any item") }}
                                    </td>
                                </tr>
                                <tr
                                    v-for="item_type in folder_statistics.types"
                                    v-bind:key="item_type.type_name"
                                >
                                    <td>{{ item_type.type_name }}</td>
                                    <td>{{ item_type.count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>
                </div>
            </section>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, onBeforeMount } from "vue";
import { useGettext } from "vue3-gettext";
import { useActions } from "vuex-composition-helpers";
import { StatisticsTab } from "../../helpers/details-tabs";
import DocumentDetailsTabs from "../Folder/DocumentDetailsTabs.vue";
import type { Folder } from "../../type";
import type { FolderStatistics } from "../../api/statistics-rest-querier";
import { getStatistics } from "../../api/statistics-rest-querier";

const { $gettext } = useGettext();

const props = defineProps<{ item_id: number }>();

const item = ref<Folder | null>(null);
const folder_statistics = ref<FolderStatistics | null>(null);

const { loadDocumentWithAscendentHierarchy } = useActions(["loadDocumentWithAscendentHierarchy"]);

onBeforeMount(async () => {
    item.value = await loadDocumentWithAscendentHierarchy(props.item_id);
    getStatistics(props.item_id).match(
        (statistics: FolderStatistics) => {
            folder_statistics.value = statistics;
        },
        () => {
            folder_statistics.value = null;
        },
    );
});
</script>
