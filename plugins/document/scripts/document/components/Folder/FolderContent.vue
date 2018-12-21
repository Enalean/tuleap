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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-pane">
        <div class="tlp-pane-container">
            <section class="tlp-pane-section">
                <table class="tlp-table">
                    <thead>
                        <tr>
                            <th class="document-tree-head-name" v-translate>Name</th>
                            <th class="document-tree-head-owner" v-translate>Owner</th>
                            <th class="document-tree-head-updatedate" v-translate>Last update date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <folder-content-row v-for="item of folder_content"
                                            v-bind:key="item.id"
                                            v-bind:item="item"
                        />
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";
import FolderContentRow from "./FolderContentRow.vue";

export default {
    name: "FolderContent",
    components: { FolderContentRow },
    computed: {
        ...mapState(["folder_content"])
    }
};
</script>
