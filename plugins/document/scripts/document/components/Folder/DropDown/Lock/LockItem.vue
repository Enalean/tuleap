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
    <button
        v-if="can_lock_document"
        class="tlp-dropdown-menu-item"
        type="button"
        role="menuitem"
        data-test="document-dropdown-menu-lock-item"
        v-on:click.prevent="lockDocumentItem"
        data-shortcut-lock-document
    >
        <i class="fa-solid fa-fw fa-lock tlp-dropdown-menu-item-icon"></i>
        {{ $gettext("Lock") }}
    </button>
</template>
<script setup lang="ts">
import type { Item } from "../../../../type";
import { computed } from "vue";
import { useNamespacedActions } from "vuex-composition-helpers";
import type { LockActions } from "../../../../store/lock/lock-actions";

const props = defineProps<{ item: Item }>();

const { lockDocument } = useNamespacedActions<LockActions>("lock", ["lockDocument"]);

const can_lock_document = computed((): boolean => {
    if (props.item.lock_info !== null) {
        return false;
    }

    return props.item.user_can_write;
});

async function lockDocumentItem(): Promise<void> {
    await lockDocument(props.item);
}
</script>
