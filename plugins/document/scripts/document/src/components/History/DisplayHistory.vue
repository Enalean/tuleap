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
    <section v-if="item">
        <document-details-tabs v-bind:item="item" v-bind:active_tab="LogsTab" />
        <div class="tlp-framed-horizontally">
            <history-logs v-bind:item="item" />
        </div>
    </section>
</template>

<script setup lang="ts">
import { useActions } from "vuex-composition-helpers";
import { onBeforeMount, ref } from "vue";
import type { Item } from "../../type";
import HistoryLogs from "./HistoryLogs.vue";
import { isEmbedded, isFile, isLink } from "../../helpers/type-check-helper";
import DocumentDetailsTabs from "../Folder/DocumentDetailsTabs.vue";
import { LogsTab } from "../../helpers/details-tabs";

const props = defineProps<{ item_id: number }>();

const item = ref<Item | null>(null);
const item_type_has_versions = ref(false);

const { loadDocumentWithAscendentHierarchy } = useActions(["loadDocumentWithAscendentHierarchy"]);

onBeforeMount(async () => {
    item.value = await loadDocumentWithAscendentHierarchy(props.item_id);
    if (item.value) {
        item_type_has_versions.value =
            isFile(item.value) || isLink(item.value) || isEmbedded(item.value);
    }
});
</script>
