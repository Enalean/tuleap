<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <section v-if="item">
        <div class="document-header tlp-framed-horizontally">
            <document-title-lock-info v-bind:item="item" v-bind:is-displaying-in-header="true" />

            <h1 class="document-header-title">
                {{ item.title }}
            </h1>
        </div>
        <nav class="tlp-tabs">
            <router-link
                class="tlp-tab"
                v-bind:to="{ name: 'versions', params: { item_id: item.id } }"
                v-if="item_has_versions"
                data-test="versions-link"
            >
                {{ $gettext("Versions") }}
            </router-link>
            <router-link
                class="tlp-tab"
                v-bind:to="{ name: 'history', params: { item_id: item.id } }"
            >
                {{ $gettext("Logs") }}
            </router-link>
            <span class="tlp-tab tlp-tab-active">{{ $gettext("References") }}</span>
        </nav>
        <div class="tlp-framed-horizontally">
            <references-list v-bind:item="item" />
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, ref } from "vue";
import type { Item } from "../../type";
import { useActions } from "vuex-composition-helpers";
import DocumentTitleLockInfo from "../Folder/LockInfo/DocumentTitleLockInfo.vue";
import { isEmbedded, isFile, isLink } from "../../helpers/type-check-helper";
import { useGettext } from "vue3-gettext";
import ReferencesList from "./ReferencesList.vue";

const { $gettext } = useGettext();

const props = defineProps<{ item_id: number }>();

const item = ref<Item | null>(null);

const { loadDocumentWithAscendentHierarchy } = useActions(["loadDocumentWithAscendentHierarchy"]);

const item_has_versions = computed(
    (): boolean =>
        item.value !== null && (isFile(item.value) || isLink(item.value) || isEmbedded(item.value)),
);

onBeforeMount(async () => {
    item.value = await loadDocumentWithAscendentHierarchy(props.item_id);
});
</script>
