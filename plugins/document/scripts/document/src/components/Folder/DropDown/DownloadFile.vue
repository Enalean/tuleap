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
    <a
        v-bind:href="file_url"
        v-if="!is_corrupted"
        class="tlp-dropdown-menu-item"
        type="button"
        role="menuitem"
    >
        <i class="fa-solid fa-download tlp-dropdown-menu-item-icon"></i>
        {{ $gettext("Download") }}
    </a>
</template>

<script setup lang="ts">
import type { ItemFile } from "../../../type";
import { computed } from "vue";

const props = defineProps<{ item: ItemFile }>();

const is_corrupted = computed((): boolean => {
    return !("file_properties" in props.item) || props.item.file_properties === null;
});

const file_url = computed((): string => {
    if (is_corrupted.value || !props.item.file_properties) {
        return "";
    }
    return props.item.file_properties.download_href;
});
</script>
