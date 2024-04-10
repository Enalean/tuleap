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
    <div v-if="!is_loading">
        <div v-if="sections && sections.length > 0" class="document-layout">
            <section class="tlp-framed document-content">
                <document-content v-bind:sections="sections" />
            </section>
            <aside class="table-of-contents"></aside>
        </div>
        <div v-else class="tlp-framed">
            <empty-state />
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Ref } from "vue";
import { onMounted, ref } from "vue";
import EmptyState from "@/views/EmptyState.vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { getAllSections } from "@/helpers/rest-querier";
import DocumentContent from "@/views/DocumentContent.vue";

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
</style>
<style lang="scss" scoped>
.document-layout {
    display: grid;
    grid-template-columns: 80% 20%;
    border-top: 1px solid var(--tlp-neutral-normal-color);

    .document-content {
        padding: 1.5rem 3rem;
        background-color: var(--tlp-white-color);
    }

    .table-of-contents {
        padding: 1rem;
        border-left: 1px solid var(--tlp-neutral-normal-color);
        background: var(--tlp-fade-background-color);
    }
}
</style>
