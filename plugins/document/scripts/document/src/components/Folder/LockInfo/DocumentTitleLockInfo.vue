<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <span
        v-bind:class="{ 'document-display-lock': isDisplayingInHeader }"
        v-if="is_locked"
        v-bind:title="document_lock_info_title"
        data-test="document-lock-information"
    >
        <i class="fa-solid fa-lock" v-bind:class="get_icon_additional_classes"></i>
    </span>
</template>

<script setup lang="ts">
import type { Item } from "../../../type";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const { interpolate, $gettext } = useGettext();

const props = defineProps<{
    item: Item;
    isDisplayingInHeader: boolean;
}>();

const is_locked = computed((): boolean => {
    return Boolean(props.item.lock_info);
});

const document_lock_info_title = computed((): string => {
    if (!props.item || !props.item.lock_info || !props.item.lock_info.lock_by) {
        return "";
    }

    return interpolate($gettext("Document locked by %{username}."), {
        username: props.item.lock_info.lock_by.display_name,
    });
});

const get_icon_additional_classes = computed((): string => {
    return props.isDisplayingInHeader
        ? "document-display-lock-icon"
        : "document-tree-item-toggle-quicklook-lock-icon";
});
</script>
