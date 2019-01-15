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
        <modal-header v-bind:modal_title="modal_title"/>
        <modal-feedback/>
        <div class="tlp-modal-body document-new-item-modal-body" v-if="is_displayed">
            <global-properties v-bind:item="item"/>
        </div>
        <modal-footer v-bind:is_loading="is_loading" v-bind:submit_button_label="submit_button_label"/>
    </form>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import { TYPE_FOLDER } from "../../../constants.js";
import { selfClosingInfo } from "../../../../../../../src/www/scripts/tuleap/feedback.js";
import ModalHeader from "./ModalHeader.vue";
import ModalFeedback from "./ModalFeedback.vue";
import ModalFooter from "./ModalFooter.vue";
import GlobalProperties from "./Property/GlobalProperties.vue";

export default {
    name: "NewFolderModal",
    components: {
        ModalFeedback,
        ModalHeader,
        ModalFooter,
        GlobalProperties
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
            modal: null
        };
    },
    computed: {
        ...mapState(["current_folder", "has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Create folder");
        },
        modal_title() {
            return this.$gettext("New folder");
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
        show() {
            this.item.title = "";
            this.item.description = "";
            this.is_displayed = true;
            this.modal.show();
        },
        reset() {
            this.$store.commit("resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
        },
        async addFolder(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("resetModalError");

            await this.$store.dispatch("createNewItem", [this.item, this.current_folder]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
                selfClosingInfo(this.$gettext("Folder has been successfully created."));
            }
        }
    }
};
</script>
