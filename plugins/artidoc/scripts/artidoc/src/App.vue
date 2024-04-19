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
    <document-view-skeleton v-if="is_loading" class="artidoc-app-container" />
    <document-view v-else v-bind:sections="sections" class="artidoc-app-container" />
</template>

<script setup lang="ts">
import type { Ref } from "vue";
import { onMounted, ref } from "vue";
import DocumentView from "@/views/DocumentView.vue";
import DocumentViewSkeleton from "@/views/DocumentViewSkeleton.vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { getAllSections } from "@/helpers/rest-querier";

const props = defineProps<{ item_id: number }>();

const sections: Ref<readonly ArtidocSection[] | undefined> = ref(undefined);
const is_loading = ref(true);

onMounted(() => {
    getAllSections(props.item_id).match(
        (artidoc_sections: readonly ArtidocSection[]) => {
            sections.value = artidoc_sections;
            is_loading.value = false;
        },
        () => {
            is_loading.value = false;
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
