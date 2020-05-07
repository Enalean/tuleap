<!--
  - Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
  -
  - This item is a part of Tuleap.
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
        type="button"
        v-bind:href="folder_href"
        class="tlp-dropdown-menu-item"
        role="menuitem"
        data-test="download-as-zip-button"
    >
        <i class="fa fa-fw fa-tlp-zip-download tlp-dropdown-menu-item-icon"></i>
        <translate>Download as zip</translate>
    </a>
</template>
<script>
import { mapState } from "vuex";

export default {
    name: "DownloadFolderAsZip",
    props: {
        item: Object,
    },
    computed: {
        ...mapState(["project_name"]),
        folder_href() {
            return `/plugins/document/${this.project_name}/folders/${encodeURIComponent(
                this.item.id
            )}/download-folder-as-zip`;
        },
    },
};
</script>
