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
        class="tlp-dropdown-menu-item"
        role="menuitem"
        data-test="download-as-zip-button"
        v-on:click="checkFolderSize"
    >
        <i
            class="fa fa-fw tlp-dropdown-menu-item-icon"
            v-bind:class="{
                'fa-tlp-zip-download': !is_retrieving_folder_size,
                'fa-spin fa-spinner': is_retrieving_folder_size,
            }"
        ></i>
        <translate>Download as zip</translate>
    </a>
</template>
<script>
import { mapState, mapActions } from "vuex";
import EventBus from "../../../../helpers/event-bus.js";

export default {
    name: "DownloadFolderAsZip",
    props: {
        item: Object,
    },
    data() {
        return {
            is_retrieving_folder_size: false,
        };
    },
    computed: {
        ...mapState(["project_name", "max_archive_size"]),
        folder_href() {
            return `/plugins/document/${this.project_name}/folders/${encodeURIComponent(
                this.item.id
            )}/download-folder-as-zip`;
        },
    },
    methods: {
        ...mapActions(["getFolderProperties"]),
        async checkFolderSize() {
            this.is_retrieving_folder_size = true;

            const folder_properties = await this.getFolderProperties([this.item]);
            this.is_retrieving_folder_size = false;

            if (folder_properties === null) {
                return;
            }

            // max_archive_size is in MB, total_size in Bytes. Let's covert it to Bytes first.
            const max_archive_size_in_Bytes = this.max_archive_size * Math.pow(10, 6);
            const { total_size } = folder_properties;

            if (total_size > max_archive_size_in_Bytes) {
                EventBus.$emit("show-max-archive-size-threshold-exceeded-modal", {
                    detail: { current_folder_size: total_size },
                });

                return;
            }

            window.location.assign(this.folder_href);
        },
    },
};
</script>
