<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <form
        class="tlp-modal"
        role="dialog"
        v-bind:aria-labelled-by="aria_labelled_by"
        v-on:submit.prevent="uploadNewVersion"
    >
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by" />
        <modal-feedback />
        <div class="tlp-modal-body">
            <item-update-properties
                v-bind:version="version"
                v-bind:item="updatedFile"
                v-bind:is-open-after-dnd="true"
                v-on:approval-table-action-change="setApprovalUpdateAction"
            />
            <file-version-history v-bind:item="updatedFile" />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Create new version')"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-version-changelog"
        />
    </form>
</template>

<script>
import { createModal } from "@tuleap/tlp-modal";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import ItemUpdateProperties from "./PropertiesForUpdate/ItemUpdateProperties.vue";
import { sprintf } from "sprintf-js";
import { mapState } from "vuex";
import emitter from "../../../../helpers/emitter";
import { TYPE_FILE } from "../../../../constants";
import { getItemStatus } from "../../../../helpers/properties-helpers/value-transformer/status-property-helper";
import { getStatusProperty } from "../../../../helpers/properties-helpers/hardcoded-properties-mapping-helper";
import FileVersionHistory from "./History/FileVersionHistory.vue";

export default {
    name: "FileVersionChangelogModal",
    components: {
        FileVersionHistory,
        ModalHeader,
        ModalFeedback,
        ModalFooter,
        ItemUpdateProperties,
    },
    props: {
        updatedFile: {
            default: () => ({}),
            type: Object,
        },
        droppedFile: {
            default: () => ({}),
            type: File,
        },
    },
    data() {
        return {
            modal: null,
            is_loading: false,
            version: { title: "", changelog: "" },
            approval_table_action: null,
            new_version_item: {
                id: 0,
                title: "",
                description: "",
                type: "file",
                file_properties: { file: {} },
                status: "none",
            },
        };
    },
    computed: {
        ...mapState("error", ["has_modal_error"]),
        modal_title() {
            return sprintf(this.$gettext('New version for "%s"'), this.updatedFile.title);
        },
        aria_labelled_by() {
            return "document-file-changelog-modal";
        },
    },
    mounted() {
        this.new_version_item = {
            id: this.updatedFile.id,
            title: this.updatedFile.title,
            description: this.updatedFile.description,
            type: TYPE_FILE,
            file_properties: { file: this.droppedFile },
            status: getItemStatus(getStatusProperty(this.updatedFile.properties)),
        };
        this.modal = createModal(this.$el, { destroy_on_hide: true });
        this.modal.addEventListener("tlp-modal-hidden", this.close);
        this.modal.show();
        emitter.on("update-version-title", this.updateTitleValue);
        emitter.on("update-changelog-property", this.updateChangelogValue);
        emitter.on("update-lock", this.updateLock);
    },
    beforeUnmount() {
        emitter.off("update-version-title", this.updateTitleValue);
        emitter.off("update-changelog-property", this.updateChangelogValue);
        emitter.off("update-lock", this.updateLock);
    },
    methods: {
        setApprovalUpdateAction(value) {
            this.approval_table_action = value;
        },
        async uploadNewVersion() {
            this.is_loading = true;
            this.$store.commit("error/resetModalError");

            await this.$store.dispatch("createNewFileVersionFromModal", [
                this.updatedFile,
                this.droppedFile,
                this.version.title,
                this.version.changelog,
                false,
                this.approval_table_action,
            ]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.uploaded_item = {};
                this.close();
            }
        },
        close() {
            this.modal.removeBackdrop();
            this.$emit("close-changelog-modal");
        },
        updateTitleValue(title) {
            this.version.title = title;
        },
        updateChangelogValue(changelog) {
            this.version.changelog = changelog;
        },
        updateLock(is_locked) {
            this.version.is_file_locked = is_locked;
        },
    },
};
</script>
