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
    <article>
        <section-header
            class="section-header"
            v-bind:title="section.value.display_title"
            v-bind:is_print_mode="true"
            v-bind:is_freetext="!isArtifactSection(section.value)"
        />
        <section-description-read-only v-bind:readonly_value="readonly_description" />
    </article>
</template>

<script setup lang="ts">
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import { isArtifactSection } from "@/helpers/artidoc-section.type";
import SectionHeader from "@/components/section/header/SectionHeader.vue";
import { computed } from "vue";
import { useEditorSectionContent } from "@/composables/useEditorSectionContent";
import SectionDescriptionReadOnly from "@/components/section/description/SectionDescriptionReadOnly.vue";

const props = defineProps<{ section: ReactiveStoredArtidocSection }>();

const content = computed(() =>
    useEditorSectionContent(props.section, {
        showActionsButtons: noop,
        hideActionsButtons: noop,
    }),
);

const readonly_description = computed(() => content.value.getReadonlyDescription());

function noop(): void {}
</script>
