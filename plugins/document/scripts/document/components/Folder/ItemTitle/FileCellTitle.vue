<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -
  -->

<template>
    <div>
        <fake-caret v-bind:item="item" />
        <i class="fa fa-fw document-folder-content-icon" v-bind:class="icon_class"></i>
        <a v-bind:href="file_url" class="document-folder-subitem-link" draggable="false">
            {{ item.title }}
        </a>
        <span class="tlp-badge-warning document-badge-corrupted" v-translate v-if="is_corrupted">
            Corrupted
        </span>
    </div>
</template>

<script setup lang="ts">
import { iconForMimeType } from "../../../helpers/icon-for-mime-type";
import { ICON_EMPTY } from "../../../constants";
import FakeCaret from "./FakeCaret.vue";
import type { ItemFile } from "../../../type";
import { computed } from "vue";

const props = defineProps<{ item: ItemFile }>();

const is_corrupted = computed((): boolean => {
    return !("file_properties" in props.item) || props.item.file_properties === null;
});

const icon_class = computed((): string => {
    if (is_corrupted.value || !props.item.file_properties) {
        return ICON_EMPTY;
    }

    return iconForMimeType(props.item.file_properties.file_type);
});

const file_url = computed((): string => {
    if (is_corrupted.value || !props.item.file_properties) {
        return "";
    }
    return props.item.file_properties.open_href;
});
</script>
