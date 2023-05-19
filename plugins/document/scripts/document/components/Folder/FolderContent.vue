<!--
  - Copyright (c) Enalean, 2018-present. All Rights Reserved.
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
    <div class="document-folder-content-quicklook">
        <section class="tlp-pane document-folder-pane">
            <div class="tlp-pane-container tlp-pane-section">
                <table class="tlp-table">
                    <thead>
                        <tr>
                            <th class="document-tree-head-name">{{ $gettext("Name") }}</th>
                            <template v-if="!toggle_quick_look">
                                <th
                                    class="document-tree-head-owner"
                                    data-test="document-folder-owner-information"
                                >
                                    {{ $gettext("Owner") }}
                                </th>
                                <th class="document-tree-head-updatedate">
                                    {{ $gettext("Last update date") }}
                                </th>
                            </template>
                        </tr>
                    </thead>

                    <tbody data-test="document-tree-content" data-shortcut-table>
                        <folder-content-row
                            v-for="item of folder_content"
                            v-bind:key="item.id"
                            v-bind:item="item"
                            v-bind:is-quick-look-displayed="toggle_quick_look"
                        />
                    </tbody>
                </table>
            </div>
        </section>
        <div
            v-if="should_display_preview"
            class="document-folder-right-container"
            data-test="document-quick-look"
        >
            <section
                class="tlp-pane document-quick-look-pane"
                v-bind:class="quick_look_dropzone_class"
                v-bind:data-item-id="item_id"
            >
                <quicklook-global v-on:close-quick-look-event="closeQuickLook" />
            </section>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";
import FolderContentRow from "./FolderContentRow.vue";
import QuicklookGlobal from "../QuickLook/QuickLookGlobal.vue";
import { isFile, isFolder } from "../../helpers/type-check-helper";
import emitter from "../../helpers/emitter";

export default {
    name: "FolderContent",
    components: { QuicklookGlobal, FolderContentRow },
    computed: {
        ...mapState([
            "folder_content",
            "currently_previewed_item",
            "toggle_quick_look",
            "current_folder",
        ]),
        item_id() {
            if (this.currently_previewed_item === null) {
                return null;
            }

            return this.currently_previewed_item.id;
        },
        quick_look_dropzone_class() {
            if (this.currently_previewed_item === null) {
                return "";
            }

            return {
                "document-quick-look-folder-dropzone": isFolder(this.currently_previewed_item),
                "document-quick-look-file-dropzone": isFile(this.currently_previewed_item),
            };
        },
        should_display_preview() {
            return this.toggle_quick_look && this.currently_previewed_item;
        },
    },
    created() {
        emitter.on("toggle-quick-look", this.toggleQuickLook);
    },
    beforeUnmount() {
        emitter.off("toggle-quick-look", this.toggleQuickLook);
    },
    methods: {
        async toggleQuickLook(event) {
            if (!this.currently_previewed_item) {
                await this.displayQuickLook(event.details.item);
                return;
            }

            if (this.currently_previewed_item.id !== event.details.item.id) {
                await this.displayQuickLook(event.details.item);
                return;
            }

            if (!this.toggle_quick_look) {
                await this.displayQuickLook(event.details.item);
            } else {
                await this.closeQuickLook(event.details.item);
            }
        },
        async displayQuickLook(item) {
            await this.$router.replace({
                name: "preview",
                params: { preview_item_id: item.id },
            });

            this.$store.commit("updateCurrentlyPreviewedItem", item);
            this.$store.commit("toggleQuickLook", true);
        },
        async closeQuickLook() {
            if (this.current_folder.parent_id !== 0) {
                await this.$router.replace({
                    name: "folder",
                    params: { item_id: this.current_folder.id },
                });
            } else {
                await this.$router.replace({
                    name: "root_folder",
                });
            }
            this.$store.commit("toggleQuickLook", false);
        },
    },
};
</script>
