<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <label class="tlp-label document-label">{{ static_list_field.label }}</label>
    <p v-if="static_list_field.value.length > 0" class="static-items-list document-item-list">
        <span
            v-for="value in static_list_field.value"
            v-bind:key="value.label"
            class="static-list-item document-list-item-inline document-list-item-with-color-bubble"
            data-test="static-list-item"
        >
            <tlp-color-bubble v-if="value.tlp_color !== ''" v-bind:tlp_color="value.tlp_color" />
            {{ value.label }}
        </span>
    </p>
    <p v-else class="tlp-property-empty" data-test="empty-state">{{ $gettext("Empty") }}</p>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { ReadonlyFieldStaticList } from "@/sections/readonly-fields/ReadonlyFields";
import TlpColorBubble from "@/components/section/readonly-fields/TlpColorBubble.vue";

const gettext_provider = useGettext();
const { $gettext } = gettext_provider;
defineProps<{
    static_list_field: ReadonlyFieldStaticList;
}>();
</script>

<style scoped lang="scss">
.static-items-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
}

.static-list-item {
    display: inline-flex;
    align-items: center;
    align-self: center;
}

.static-list-item:not(:last-child)::after {
    content: ",\0000A0";
}
</style>
