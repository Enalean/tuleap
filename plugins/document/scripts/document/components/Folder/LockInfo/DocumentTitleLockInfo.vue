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
    <span class="document-display-lock" v-if="is_locked" v-bind:title="document_lock_info_title">
        <i class="fa fa-lock document-display-lock-icon"></i>
    </span>
</template>

<script>
import { sprintf } from "sprintf-js";
export default {
    name: "DocumentTitleLockInfo",
    props: {
        item: Object
    },
    computed: {
        is_locked() {
            return this.item.lock_info;
        },
        document_lock_info_title() {
            if (!this.item || !this.item.lock_info || !this.item.lock_info.locked_by) {
                return "";
            }

            return sprintf(
                this.$gettext("Document locked by %s."),
                this.item.lock_info.locked_by.display_name
            );
        }
    }
};
</script>
