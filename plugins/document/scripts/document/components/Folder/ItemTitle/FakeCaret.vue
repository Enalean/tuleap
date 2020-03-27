<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <i
        class="fa fa-fw document-folder-toggle document-folder-content-fake-caret"
        v-if="can_be_displayed"
    ></i>
</template>
<script>
import { mapState } from "vuex";
import { TYPE_FOLDER } from "../../../constants";

export default {
    props: {
        item: Object,
    },
    computed: {
        ...mapState(["current_folder", "folder_content"]),
        is_item_in_current_folder() {
            return this.item.parent_id === this.current_folder.id;
        },
        is_item_sibling_of_a_folder() {
            return Boolean(
                this.folder_content.find(
                    (item) => item.parent_id === this.current_folder.id && item.type === TYPE_FOLDER
                )
            );
        },
        can_be_displayed() {
            return !this.is_item_in_current_folder || this.is_item_sibling_of_a_folder;
        },
    },
};
</script>
