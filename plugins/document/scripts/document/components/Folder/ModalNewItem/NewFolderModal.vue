<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <form class="tlp-modal" role="dialog" aria-labelledby="document-new-folder-modal" v-on:submit="addFolder">
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by"/>
        <modal-feedback/>
        <div class="tlp-modal-body document-new-item-modal-body" v-if="is_displayed">
            <global-metadata v-bind:item="item" v-bind:parent="parent"/>
        </div>
        <modal-footer v-bind:is-loading="is_loading" v-bind:submit-button-label="submit_button_label" v-bind:aria-labelled-by="aria_labelled_by"/>
    </form>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import { TYPE_FOLDER } from "../../../constants.js";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import GlobalMetadata from "../Metadata/GlobalMetadata.vue";

export default {
    name: "NewFolderModal",
    components: {
        ModalFeedback,
        ModalHeader,
        ModalFooter,
        GlobalMetadata
    },
    data() {
        return {
            item: {
                title: "",
                description: "",
                type: TYPE_FOLDER
            },
            is_loading: false,
            is_displayed: false,
            modal: null,
            parent: {}
        };
    },
    computed: {
        ...mapState(["current_folder"]),
        ...mapState("error", ["has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Create folder");
        },
        modal_title() {
            return this.$gettext("New folder");
        },
        aria_labelled_by() {
            return "document-new-item-modal";
        }
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.registerEvents();
    },
    methods: {
        registerEvents() {
            document.addEventListener("show-new-folder-modal", this.show);
            this.$once("hook:beforeDestroy", () => {
                document.removeEventListener("show-new-folder-modal", this.show);
            });
            this.modal.addEventListener("tlp-modal-hidden", this.reset);
        },
        show(event) {
            this.item.title = "";
            this.item.description = "";
            this.parent = event.detail.parent;
            this.is_displayed = true;
            this.modal.show();
        },
        reset() {
            this.$store.commit("error/resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
        },
        async addFolder(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");

            await this.$store.dispatch("createNewItem", [
                this.item,
                this.parent,
                this.current_folder
            ]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
            }
        }
    }
};
</script>
