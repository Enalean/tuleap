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
        <i class="fa-fw document-folder-content-icon" v-bind:class="icon_class"></i>
        <a
            v-bind:href="file_url"
            class="document-folder-subitem-link"
            data-test="document-folder-subitem-link"
            draggable="false"
        >
            {{ item.title
            }}<i class="fas document-action-icon" v-bind:class="action_icon" aria-hidden="true"></i>
        </a>
        <span class="tlp-badge-warning document-badge-corrupted" v-if="is_corrupted">
            {{ $gettext("Corrupted") }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { iconForMimeType } from "../../../helpers/icon-for-mime-type";
import { ICON_EMPTY, ACTION_ICON_FILE, ACTION_ICON_ONLYOFFICE } from "../../../constants";
import FakeCaret from "./FakeCaret.vue";
import type { ItemFile } from "../../../type";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{ item: ItemFile }>();

const { $gettext } = useGettext();

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
    return props.item.file_properties.open_href ?? props.item.file_properties.download_href;
});

const action_icon = computed((): string => {
    if (props.item.file_properties && props.item.file_properties.open_href) {
        return ACTION_ICON_ONLYOFFICE;
    }

    return ACTION_ICON_FILE;
});
</script>
