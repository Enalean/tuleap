<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
        <i class="fa-fw document-folder-content-icon" v-bind:class="icon" data-test="icon"></i>
        <a
            v-bind:href="url"
            class="document-folder-subitem-link"
            data-test="document-folder-subitem-link"
            draggable="false"
        >
            {{ item.title
            }}<i
                class="fa-solid document-action-icon"
                v-bind:class="ACTION_ICON_OTHER_TYPE"
                aria-hidden="true"
            ></i>
        </a>
        <span class="tlp-badge-warning document-badge-corrupted" v-if="is_corrupted">
            {{ $gettext("Corrupted") }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { ACTION_ICON_OTHER_TYPE, ICON_EMPTY } from "../../../constants";
import FakeCaret from "./FakeCaret.vue";
import type { OtherTypeItem } from "../../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { OTHER_ITEM_TYPES } from "../../../injection-keys";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{ item: OtherTypeItem }>();

const other_item_types = strictInject(OTHER_ITEM_TYPES);

const { $gettext } = useGettext();

const is_corrupted = computed((): boolean => {
    return !("other_type_properties" in props.item) || props.item.other_type_properties === null;
});

const url = computed((): string => {
    if (is_corrupted.value || !props.item.other_type_properties) {
        return "";
    }
    return props.item.other_type_properties.open_href;
});

const icon = computed((): string =>
    props.item.type in other_item_types ? other_item_types[props.item.type].icon : ICON_EMPTY,
);
</script>
