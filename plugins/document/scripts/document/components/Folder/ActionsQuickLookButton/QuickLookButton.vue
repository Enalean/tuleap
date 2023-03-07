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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <button
        type="button"
        class="tlp-button-primary tlp-button-outline tlp-button-small tlp-dropdown-split-button-main"
        data-test="document-quick-look-button"
        v-on:click="toggleQuickLook()"
    >
        <i class="fa-solid fa-eye tlp-button-icon"></i>
        {{ $gettext("Quick look") }}
    </button>
</template>

<script setup lang="ts">
import type { Item } from "../../../type";
import emitter from "../../../helpers/emitter";

const props = defineProps<{ item: Item }>();

function toggleQuickLook(): void {
    emitter.emit("toggle-quick-look", { details: { item: props.item } });
}
</script>
