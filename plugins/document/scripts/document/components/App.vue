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
    <div>
        <document-breadcrumb/>
        <div class="tlp-framed">
            <error-message/>
            <folder-view/>
        </div>
    </div>
</template>
<script>
import { mapState } from "vuex";

import DocumentBreadcrumb from "./DocumentBreadcrumb.vue";
import ErrorMessage from "./ErrorMessage.vue";
import FolderView from "./FolderView.vue";

export default {
    name: "App",
    components: { DocumentBreadcrumb, ErrorMessage, FolderView },
    props: {
        projectId: Number,
        projectName: String,
        userIsAdmin: Boolean
    },
    computed: {
        ...mapState({
            has_loaded_without_error: (state, getters) =>
                !state.is_loading_root_document && !getters.hasError
        })
    },
    mounted() {
        this.$store.commit("initDocumentTree", [
            this.projectId,
            this.projectName,
            this.userIsAdmin
        ]);
        this.$store.dispatch("loadRootDocumentId");
    }
};
</script>
