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
        <h1 v-if="is_print_mode" class="section-title">{{ display_level + title }}</h1>
        <h1 v-else class="section-title">{{ title }}</h1>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";

const props = withDefaults(
    defineProps<{
        display_level: string;
        title: string;
        is_print_mode?: boolean;
    }>(),
    {
        is_print_mode: false,
    },
);

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
