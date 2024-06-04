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
    <section-description-skeleton v-if="is_sections_loading" />
    <template v-else-if="is_edit_mode">
        <component
            v-bind:is="async_editor"
            v-bind:artifact_id="artifact_id"
            v-bind:editable_description="editable_description"
            v-bind:input_current_description="input_current_description"
            v-bind:readonly_value="readonly_description"
            data-test="editor"
        />
    </template>
    <section-description-read-only v-else v-bind:readonly_value="readonly_description" />
</template>
<script setup lang="ts">
import type { SectionEditor } from "@/composables/useSectionEditor";
import { defineAsyncComponent, onMounted } from "vue";
import { loadTooltips } from "@tuleap/tooltip";
import SectionDescriptionSkeleton from "@/components/SectionDescriptionSkeleton.vue";
import { useInjectSectionsStore } from "@/stores/useSectionsStore";
import SectionDescriptionReadOnly from "@/components/description/SectionDescriptionReadOnly.vue";

defineProps<{
    artifact_id: number;
    editable_description: string;
    readonly_description: string;
    is_edit_mode: boolean;
    input_current_description: SectionEditor["inputCurrentDescription"];
}>();

const { is_sections_loading } = useInjectSectionsStore();

const async_editor = defineAsyncComponent({
    loader: () => import("@/components/SectionDescriptionEditor.vue"),
    loadingComponent: SectionDescriptionReadOnly,
    delay: 0,
});

onMounted(() => {
    loadTooltips();
});
</script>
