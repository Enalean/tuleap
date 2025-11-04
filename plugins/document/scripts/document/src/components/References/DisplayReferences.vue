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
        <document-details-tabs v-bind:item="item" v-bind:active_tab="ReferencesTab" />
        <div class="tlp-framed-horizontally">
            <references-list v-bind:item="item" />
        </div>
    </section>
</template>

<script setup lang="ts">
import { onBeforeMount, ref } from "vue";
import type { Item } from "../../type";
import { useActions } from "vuex-composition-helpers";
import ReferencesList from "./ReferencesList.vue";
import DocumentDetailsTabs from "../Folder/DocumentDetailsTabs.vue";
import { ReferencesTab } from "../../helpers/details-tabs";

const props = defineProps<{ item_id: number }>();

const item = ref<Item | null>(null);

const { loadDocumentWithAscendentHierarchy } = useActions(["loadDocumentWithAscendentHierarchy"]);

onBeforeMount(async () => {
    item.value = await loadDocumentWithAscendentHierarchy(props.item_id);
});
</script>
