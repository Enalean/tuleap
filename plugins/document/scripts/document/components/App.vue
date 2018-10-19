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
    <div class="tlp-framed">
        <error-message />
        <document-breadcrumb />
        <spinner />
        <div class="empty-page">
            <p class="empty-page-text" v-if="has_loaded_without_error">
                <translate>Project has no documentation yet.</translate>
            </p>
        </div>
    </div>

</template>
<script>
import { mapState } from "vuex";

import ErrorMessage from "./ErrorMessage.vue";
import Spinner from "./Spinner.vue";
import DocumentBreadcrumb from "./DocumentBreadcrumb.vue";

export default {
    name: "App",
    components: { ErrorMessage, Spinner, DocumentBreadcrumb },
    props: {
        projectId: Number,
        projectName: String,
        userIsAdmin: Boolean
    },
    mounted() {
        this.$store.commit("initDocumentTree", [
            this.projectId,
            this.projectName,
            this.userIsAdmin
        ]);
        this.$store.dispatch("loadRootDocumentId");
    },
    computed: {
        ...mapState({
            has_loaded_without_error: (state, getters) =>
                !state.is_loading_root_document && !getters.hasError
        })
    }
};
</script>
