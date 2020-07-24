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
        <modal-header
            v-bind:modal-title="modal_title"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-header-class="'fa-plus'"
        />
        <modal-feedback />
        <div class="tlp-modal-body">
            <item-update-properties
                v-bind:version="version"
                v-bind:item="updatedFile"
                v-bind:is-open-after-dnd="true"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Create new version')"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-submit-button-class="'fa-plus'"
        />
    </form>
</template>

<script>
import { createModal } from "tlp";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import ItemUpdateProperties from "../Property/ItemUpdateProperties.vue";
import { sprintf } from "sprintf-js";
import { mapState } from "vuex";

export default {
    name: "FileVersionChangelogModal",
    components: {
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
            version: {},
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
        this.modal = createModal(this.$el, { destroy_on_hide: true });
        this.modal.addEventListener("tlp-modal-hidden", this.close);
        this.modal.show();
    },
    methods: {
        async uploadNewVersion() {
            this.is_loading = true;
            this.$store.commit("error/resetModalError");

            await this.$store.dispatch("createNewFileVersionFromModal", [
                this.updatedFile,
                this.droppedFile,
                this.version.title,
                this.version.changelog,
                false,
                null,
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
    },
};
</script>
