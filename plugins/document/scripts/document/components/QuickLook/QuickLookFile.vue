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
        <button
            type="button"
            class="tlp-button-primary tlp-button-small document-quick-look-action-button-margin"
            v-on:click="downloadFile"
        >
            <i class="fa fa-download tlp-button-icon"></i>
            <translate>Download</translate>
        </button>
        <drop-down-quick-look v-bind:item="item" />
        <div class="document-header-spacer"></div>
        <quick-look-delete-button v-bind:item="item" />
    </div>
</template>

<script setup lang="ts">
import DropDownQuickLook from "../Folder/DropDown/DropDownQuickLook.vue";
import QuickLookDeleteButton from "../Folder/ActionsQuickLookButton/QuickLookDeleteButton.vue";
import type { Item } from "../../type";
import { isFile } from "../../helpers/type-check-helper";

const props = defineProps<{ item: Item }>();

function downloadFile(): void {
    const item = props.item;
    if (!item || !isFile(item) || !item.file_properties) {
        return;
    }
    window.location.assign(encodeURI(item.file_properties.download_href));
}
</script>
