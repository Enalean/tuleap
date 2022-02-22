<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
        <create-pullrequest-button v-bind:show-modal="showModal" />
        <create-pullrequest-modal
            v-bind:display-parent-repository-warning="display_parent_repository_warning"
            ref="modal"
        />
        <create-pullrequest-error-modal ref="error_modal" />
    </div>
</template>

<script>
import { createModal } from "tlp";
import store from "../store/index.js";
import CreatePullrequestButton from "./CreatePullrequestButton.vue";
import CreatePullrequestModal from "./CreatePullrequestModal.vue";
import CreatePullrequestErrorModal from "./CreatePullrequestErrorModal.vue";

export default {
    name: "App",
    store,
    components: {
        CreatePullrequestButton,
        CreatePullrequestModal,
        CreatePullrequestErrorModal,
    },
    props: {
        repository_id: Number,
        project_id: Number,
        parent_repository_id: Number,
        parent_repository_name: String,
        parent_project_id: Number,
        user_can_see_parent_repository: Boolean,
    },
    data() {
        return {
            modal: null,
            error_modal: null,
        };
    },
    computed: {
        display_parent_repository_warning() {
            return !Number.isNaN(this.parent_repository_id) && !this.user_can_see_parent_repository;
        },
    },
    mounted() {
        this.$store.dispatch("init", {
            repository_id: this.repository_id,
            project_id: this.project_id,
            parent_repository_id: this.parent_repository_id,
            parent_repository_name: this.parent_repository_name,
            parent_project_id: this.parent_project_id,
            user_can_see_parent_repository: this.user_can_see_parent_repository,
        });

        const modal = this.$refs.modal.$el;
        this.modal = createModal(modal);
        this.modal.addEventListener("tlp-modal-hidden", this.resetModal);

        const error_modal = this.$refs.error_modal.$el;
        this.error_modal = createModal(error_modal);
    },
    methods: {
        showModal() {
            if (this.$store.state.has_error_while_loading_branches) {
                this.error_modal.toggle();
            } else {
                this.modal.toggle();
            }
        },
        resetModal() {
            this.$store.commit("resetSelection");
        },
    },
};
</script>
