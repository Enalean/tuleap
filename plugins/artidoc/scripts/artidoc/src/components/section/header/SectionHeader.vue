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
    <div v-if="!can_header_be_edited">
        <h1 v-if="section.value.level === LEVEL_1" v-bind:class="classes">{{ display_title }}</h1>
        <h2 v-if="section.value.level === LEVEL_2" v-bind:class="classes">{{ display_title }}</h2>
        <h3 v-if="section.value.level === LEVEL_3" v-bind:class="classes">{{ display_title }}</h3>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { LEVEL_1, LEVEL_2, LEVEL_3 } from "@/sections/levels/SectionsNumberer";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";

const props = withDefaults(
    defineProps<{
        section: ReactiveStoredArtidocSection;
        is_print_mode?: boolean;
    }>(),
    {
        is_print_mode: false,
    },
);

const classes = computed(() => ({
    "section-title": true,
    "section-title-with-delegated-numbering": props.is_print_mode,
}));

const display_title = computed(() => {
    if (!props.is_print_mode) {
        return props.section.value.title;
    }

    return props.section.value.display_level + props.section.value.title;
});
const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);
const can_header_be_edited = computed(() => props.is_print_mode !== true && can_user_edit_document);
</script>

<style lang="scss" scoped>
h1 {
    margin: 0;
    padding-bottom: var(--tlp-small-spacing);
    color: var(--tlp-dark-color);
}
</style>
