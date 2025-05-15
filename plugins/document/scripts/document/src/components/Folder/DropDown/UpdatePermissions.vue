<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <button
        v-if="item.can_user_manage"
        v-on:click.prevent="showUpdateModal"
        class="tlp-dropdown-menu-item"
        type="button"
        role="menuitem"
        data-shortcut-permissions
        data-test="document-permissions"
    >
        <i class="fa-solid fa-fw fa-lock tlp-dropdown-menu-item-icon"></i>
        {{ $gettext("Permissions") }}
    </button>
</template>

<script setup lang="ts">
import type { Item } from "../../../type";
import emitter from "../../../helpers/emitter";

const props = defineProps<{ item: Item }>();

function showUpdateModal(): void {
    emitter.emit("show-update-permissions-modal", {
        detail: { current_item: props.item },
    });
}
</script>
