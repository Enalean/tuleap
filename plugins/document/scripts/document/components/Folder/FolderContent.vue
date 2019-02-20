<!--
  - Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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
                            <th class="document-tree-head-name" v-translate>
                                Name
                            </th>
                            <template v-if="! toggle_quick_look">
                                <th class="document-tree-head-owner" v-translate>
                                    Owner
                                </th>
                                <th class="document-tree-head-updatedate" v-translate>
                                    Last update date
                                </th>
                            </template>
                        </tr>
                    </thead>

                    <tbody>
                        <folder-content-row v-for="item of folder_content"
                                            v-bind:key="item.id"
                                            v-bind:item="item"
                                            v-bind:is-quick-look-displayed="toggle_quick_look"
                                            v-on:displayQuickLook="displayQuickLook(item)"
                        />
                    </tbody>
                </table>
            </div>
        </section>
        <div v-if="toggle_quick_look" class="document-folder-right-container">
            <section class="tlp-pane document-quick-look-pane">
                <quicklook-global v-on:closeQuickLookEvent="closeQuickLook" v-bind:item="quick_look_item"/>
            </section>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";
import FolderContentRow from "./FolderContentRow.vue";
import QuicklookGlobal from "./QuickLook/QuickLookGlobal.vue";

export default {
    name: "FolderContent",
    components: { QuicklookGlobal, FolderContentRow },
    data() {
        return {
            quick_look_item: null,
            toggle_quick_look: false
        };
    },
    computed: {
        ...mapState(["folder_content"])
    },
    methods: {
        displayQuickLook(item) {
            this.quick_look_item = item;
            this.toggle_quick_look = true;
        },
        closeQuickLook() {
            this.toggle_quick_look = false;
        }
    }
};
</script>
