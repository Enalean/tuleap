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
    <a
        v-if="can_lock_document"
        class="tlp-dropdown-menu-item"
        role="menuitem"
        data-test="document-dropdown-menu-lock-item"
        v-on:click.prevent="lockDocument"
    >
        <i class="fa fa-fw fa-lock tlp-dropdown-menu-item-icon"></i>
        <translate>Lock</translate>
    </a>
</template>
<script>
export default {
    name: "LockItem",
    props: {
        item: Object,
    },
    computed: {
        can_lock_document() {
            if (this.item.lock_info !== null) {
                return false;
            }

            return this.item.user_can_write;
        },
    },
    methods: {
        async lockDocument() {
            await this.$store.dispatch("lockDocument", this.item);
        },
    },
};
</script>
