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
    <div class="modal fade"
         id="move-artifact-modal"
         tabindex="-1"
         role="dialog"
         aria-labelledby="modal-move-artifact-choose-trackers"
         aria-hidden="true"
         ref="vuemodal"
    >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="tuleap-modal-close close" data-dismiss="modal">×</i>
                    <move-modal-title />
                </div>
                <div class="modal-body">
                    <div v-if="isLoadingInitial" class="move-artifact-loader"></div>
                    <div v-if="hasError" class="alert alert-error">{{ getErrorMessage }}</div>
                    <move-modal-selectors />
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-dismiss="modal"><translate>Close</translate></button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import MoveModalTitle from "./MoveModalTitle.vue";
import MoveModalSelectors from "./MoveModalSelectors.vue";
import store from "../store/index.js";
import { mapGetters, mapState } from "vuex";
import $ from "jquery";

export default {
    name: "MoveModal",
    store,
    components: {
        MoveModalTitle,
        MoveModalSelectors
    },
    computed: {
        ...mapState({
            isLoadingInitial: state => state.is_loading_initial,
            getErrorMessage: state => state.error_message
        }),
        ...mapGetters(["hasError"])
    },
    mounted() {
        $(this.$refs.vuemodal).on("show", () => {
            this.$store.dispatch("loadProjectList");
        });
        $(this.$refs.vuemodal).on("hidden", () => {
            this.$store.commit("resetState");
        });
    }
};
</script>
