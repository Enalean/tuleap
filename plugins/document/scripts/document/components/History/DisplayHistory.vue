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
    <section v-if="item && should_display_history_in_document">
        <div class="document-header tlp-framed-horizontally">
            <document-title-lock-info v-bind:item="item" v-bind:is-displaying-in-header="true" />

            <h1 class="document-header-title">{{ item.title }}</h1>
        </div>
        <nav class="tlp-tabs">
            <span class="tlp-tab tlp-tab-active">{{ $gettext("History") }}</span>
            <router-link
                class="tlp-tab"
                v-bind:to="{ name: 'versions', params: { item_id: item.id } }"
                v-if="item_type_has_versions"
                data-test="versions-link"
                >{{ $gettext("Versions") }}</router-link
            >
        </nav>
        <div class="tlp-framed-horizontally">
            <history-logs v-bind:item="item" />
        </div>
    </section>
</template>

<script setup lang="ts">
import DocumentTitleLockInfo from "../Folder/LockInfo/DocumentTitleLockInfo.vue";
import { useRoute } from "../../helpers/use-router";
import { useActions } from "vuex-composition-helpers";
import { inject, onBeforeMount, ref } from "vue";
import type { Item } from "../../type";
import HistoryLogs from "./HistoryLogs.vue";
import { isEmbedded, isFile, isLink } from "../../helpers/type-check-helper";

const should_display_history_in_document = inject("should_display_history_in_document", false);

const item = ref<Item | null>(null);
const item_type_has_versions = ref(false);

const { loadDocumentWithAscendentHierarchy } = useActions(["loadDocumentWithAscendentHierarchy"]);

const route = useRoute();
onBeforeMount(async () => {
    item.value = await loadDocumentWithAscendentHierarchy(parseInt(route.params.item_id, 10));
    if (item.value) {
        item_type_has_versions.value =
            isFile(item.value) || isLink(item.value) || isEmbedded(item.value);
    }
});
</script>
