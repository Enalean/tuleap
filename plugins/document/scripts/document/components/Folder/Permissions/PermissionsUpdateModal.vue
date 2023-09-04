<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
  -
  -->
<template>
    <form
        class="tlp-modal"
        role="dialog"
        aria-labelledby="document-update-permissions-modal"
        enctype="multipart/form-data"
        v-on:submit.prevent="updatePermissions"
    >
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by" />
        <modal-feedback />
        <div class="tlp-modal-body document-item-modal-body">
            <div v-if="project_ugroups === null" class="document-permissions-modal-loading-state">
                <i class="fa-solid fa-spin fa-circle-notch"></i>
            </div>
            <div
                v-else-if="item.permissions_for_groups"
                class="document-permissions-update-container"
            >
                <permissions-for-groups-selector
                    v-bind:project_ugroups="project_ugroups ? project_ugroups : []"
                    v-model="updated_permissions"
                    v-bind:value="updated_permissions"
                />
                <permissions-update-folder-sub-items
                    v-bind:item="item"
                    v-model="updated_permissions.apply_permissions_on_children"
                />
            </div>
        </div>
        <modal-footer
            v-bind:is-loading="!can_be_submitted"
            v-bind:submit-button-label="submit_button_label"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-submit-button-class="'fa-solid fa-pencil'"
            data-test="document-modal-submit-update-permissions"
        />
    </form>
</template>
<script>
import { mapState } from "vuex";
import { createModal } from "@tuleap/tlp-modal";
import { sprintf } from "sprintf-js";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import PermissionsForGroupsSelector from "./PermissionsForGroupsSelector.vue";
import { handleErrors } from "../../../store/actions-helpers/handle-errors";
import PermissionsUpdateFolderSubItems from "./PermissionsUpdateFolderSubItems.vue";
import emitter from "../../../helpers/emitter";

export default {
    name: "PermissionsUpdateModal",
    components: {
        ModalHeader,
        ModalFeedback,
        ModalFooter,
        PermissionsForGroupsSelector,
        PermissionsUpdateFolderSubItems,
    },
    props: {
        item: Object,
    },
    data: () => {
        return {
            modal: null,
            aria_labelled_by: "document-update-permissions-modal",
            is_submitting_new_permissions: false,
            updated_permissions: {
                apply_permissions_on_children: false,
                can_read: [],
                can_write: [],
                can_manage: [],
            },
        };
    },
    computed: {
        ...mapState("configuration", ["project_id"]),
        ...mapState("error", ["has_modal_error"]),
        ...mapState("permissions", ["project_ugroups"]),
        modal_title() {
            return sprintf(this.$gettext('Edit "%s" permissions'), this.item.title);
        },
        submit_button_label() {
            return this.$gettext("Update permissions");
        },
        can_be_submitted() {
            return this.project_ugroups !== null && this.is_submitting_new_permissions === false;
        },
    },
    watch: {
        item: function () {
            this.setPermissionsToUpdateFromItem();
        },
    },
    beforeMount() {
        this.setPermissionsToUpdateFromItem();
    },
    mounted() {
        this.modal = createModal(this.$el);
        emitter.on("show-update-permissions-modal", this.show);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.show();
    },
    beforeUnmount() {
        emitter.off("show-update-permissions-modal", this.show);
        this.modal.removeEventListener("tlp-modal-hidden", this.reset);
    },
    methods: {
        setPermissionsToUpdateFromItem() {
            if (!this.item.permissions_for_groups) {
                return;
            }
            this.updated_permissions = {
                apply_permissions_on_children: false,
                can_read: JSON.parse(JSON.stringify(this.item.permissions_for_groups.can_read)),
                can_write: JSON.parse(JSON.stringify(this.item.permissions_for_groups.can_write)),
                can_manage: JSON.parse(JSON.stringify(this.item.permissions_for_groups.can_manage)),
            };
        },
        async show() {
            this.modal.show();
            try {
                await this.$store.dispatch(
                    "permissions/loadProjectUserGroupsIfNeeded",
                    this.project_id,
                );
            } catch (e) {
                await handleErrors(this.$store, e);
                this.modal.hide();
            }
        },
        reset() {
            this.setPermissionsToUpdateFromItem();
            this.$store.commit("error/resetModalError");
        },
        async updatePermissions() {
            this.is_submitting_new_permissions = true;
            this.$store.commit("error/resetModalError");
            await this.$store.dispatch("permissions/updatePermissions", {
                item: this.item,
                updated_permissions: this.updated_permissions,
            });
            this.is_submitting_new_permissions = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
            }
        },
    },
};
</script>
