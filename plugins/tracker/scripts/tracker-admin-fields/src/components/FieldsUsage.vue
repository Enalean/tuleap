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
  -->

<template>
    <section>
        <palette-container />
        <div class="tlp-framed">
            <h2>{{ $gettext("Fields usage") }}</h2>

            <tracker-structure v-bind:root="root" v-if="root.children.length > 0" />

            <empty-state v-if="root.children.length === 0" />
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useGettext } from "vue3-gettext";
import type { StructureFields, StructureFormat } from "@tuleap/plugin-tracker-rest-api-types";
import EmptyState from "./EmptyState.vue";
import TrackerStructure from "./TrackerStructure.vue";
import { mapContentStructureToFields } from "../helpers/map-content-structure-to-fields";
import type { ElementWithChildren } from "../type";
import PaletteContainer from "./Palette/PaletteContainer.vue";

const { $gettext } = useGettext();

const props = defineProps<{
    tracker_id: number;
    fields: ReadonlyArray<StructureFields>;
    structure: ReadonlyArray<StructureFormat>;
}>();

const root = ref<ElementWithChildren>(mapContentStructureToFields(props.structure, props.fields));
</script>

<style lang="scss" scoped>
section {
    display: grid;
    grid-template-columns: 250px 1fr;
}
</style>
