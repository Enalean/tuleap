<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <document-view
        v-bind:is_sections_loading="is_sections_loading"
        v-bind:sections="sections"
        class="artidoc-app-container"
    />
</template>
<script setup lang="ts">
import { onMounted } from "vue";
import DocumentView from "@/views/DocumentView.vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { getAllSections } from "@/helpers/rest-querier";
import { useInjectSectionsStore } from "@/stores/useSectionsStore";
const props = defineProps<{ item_id: number }>();
const store = useInjectSectionsStore();
const sections = store.sections;
const is_sections_loading = store.is_sections_loading;
onMounted(() => {
    getAllSections(props.item_id).match(
        (artidoc_sections: readonly ArtidocSection[]) => {
            store.setSections(artidoc_sections);
            store.setIsSectionsLoading(false);
        },
        () => {
            store.setSections(undefined);
            store.setIsSectionsLoading(false);
        },
    );
});
</script>

<style lang="scss">
@use "@/themes/artidoc";

html {
    scroll-behavior: smooth;
}

.artidoc-container,
.artidoc-mountpoint {
    height: 100%;
}
</style>
<style lang="scss" scoped>
.artidoc-app-container {
    height: inherit;
}
</style>
