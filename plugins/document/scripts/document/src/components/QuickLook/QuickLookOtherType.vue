<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
  -->

<template>
    <div class="document-quick-look-document-action">
        <a
            v-if="should_display_open_button"
            class="tlp-button-primary tlp-button-small"
            v-bind:href="open_href"
            data-test="document-quick-look-document-cta-open"
        >
            {{ $gettext("Open") }}
            <i class="fa-solid fa-right-long tlp-button-icon" aria-hidden="true"></i>
        </a>
        <drop-down-quick-look v-bind:item="item" />
    </div>
</template>

<script setup lang="ts">
import DropDownQuickLook from "../Folder/DropDown/DropDownQuickLook.vue";
import type { OtherTypeItem } from "../../type";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const props = defineProps<{ item: OtherTypeItem }>();

const should_display_open_button = computed(
    (): boolean =>
        props.item.other_type_properties !== null &&
        Boolean(props.item.other_type_properties.open_href),
);

const open_href = computed((): string => props.item.other_type_properties?.open_href ?? "");
</script>
