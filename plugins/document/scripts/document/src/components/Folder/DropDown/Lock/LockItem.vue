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
import { useStore } from "vuex-composition-helpers";
import type { DocumentLock } from "../../../../helpers/lock/document-lock";

const $store = useStore();

const props = defineProps<{
    item: Item;
    document_lock: DocumentLock;
}>();

const can_lock_document = computed((): boolean => {
    if (props.item.lock_info !== null) {
        return false;
    }

    return props.item.user_can_write;
});

async function lockDocumentItem(): Promise<void> {
    await props.document_lock.lockDocument($store, props.item);
}
</script>
