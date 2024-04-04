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
    <div data-test="empty-file-title">
        <fake-caret v-bind:item="item" />
        <i class="fa-fw document-folder-content-icon" v-bind:class="icon"></i>
        <span tabindex="0" class="document-folder-subitem-link">
            {{ item.title }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { ICON_EMPTY } from "../../../constants";
import FakeCaret from "./FakeCaret.vue";
import type { Item } from "../../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { OTHER_ITEM_TYPES } from "../../../injection-keys";
import { computed } from "vue";

const props = defineProps<{ item: Item }>();

const other_item_types = strictInject(OTHER_ITEM_TYPES);

const icon = computed((): string =>
    props.item.type in other_item_types ? other_item_types[props.item.type].icon : ICON_EMPTY,
);
</script>
