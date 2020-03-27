<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <div>
        <fake-caret v-bind:item="item" />
        <i class="fa fa-fw document-folder-content-icon" v-bind:class="icon_class"></i>
        <a v-bind:href="document_link_url" class="document-folder-subitem-link">
            {{ title }}
        </a>
        <span class="tlp-badge-warning document-badge-corrupted" v-translate v-if="is_corrupted">
            Corrupted
        </span>
    </div>
</template>

<script>
import { mapState } from "vuex";
import { ICON_EMBEDDED } from "../../../constants.js";
import FakeCaret from "./FakeCaret.vue";
import { getTitleWithElipsisIfNeeded } from "../../../helpers/cell-title-formatter.js";

export default {
    name: "EmbeddedCellTitle",
    components: { FakeCaret },
    props: {
        item: Object,
    },
    computed: {
        ...mapState(["project_id", "current_folder"]),
        icon_class() {
            return ICON_EMBEDDED;
        },
        title() {
            return getTitleWithElipsisIfNeeded(this.item);
        },
        is_corrupted() {
            return !this.item.embedded_file_properties;
        },
        document_link_url() {
            const { href } = this.$router.resolve({
                name: "item",
                params: {
                    folder_id: this.current_folder.id,
                    item_id: this.item.id,
                },
            });

            return href;
        },
    },
};
</script>
