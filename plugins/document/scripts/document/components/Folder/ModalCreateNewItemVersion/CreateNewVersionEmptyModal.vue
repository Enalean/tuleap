<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
        v-bind:aria-labelled-by="`document-new-empty-version-modal`"
        v-on:submit="createNewVersion"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            v-bind:aria-labelled-by="`document-new-empty-version-modal`"
            v-bind:icon-header-class="'fa-plus'"
        />
        <modal-feedback />

        <type-selector-for-empty-modal v-model="new_item_version.type" />
        <div class="tlp-modal-body">
            <link-properties
                v-model="new_item_version.link_properties"
                v-bind:item="new_item_version"
            />
            <embedded-properties
                v-model="new_item_version.embedded_properties"
                v-bind:item="new_item_version"
            />
            <file-properties
                v-model="new_item_version.file_properties"
                v-bind:item="new_item_version"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="submit_button_label"
            v-bind:aria-labelled-by="`document-new-empty-version-modal`"
            v-bind:icon-submit-button-class="'fa-plus'"
        />
    </form>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import { sprintf } from "sprintf-js";
import { TYPE_FILE } from "../../../constants.js";
import { redirectToUrl } from "../../../helpers/location-helper.js";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import EmbeddedProperties from "../Property/EmbeddedProperties.vue";
import LinkProperties from "../Property/LinkProperties.vue";
import FileProperties from "../Property/FileProperties.vue";
import TypeSelectorForEmptyModal from "./TypeSelectorForEmptyModal.vue";

export default {
    name: "CreateNewVersionEmptyModal",
    components: {
        TypeSelectorForEmptyModal,
        FileProperties,
        LinkProperties,
        ModalFeedback,
        ModalHeader,
        ModalFooter,
        EmbeddedProperties,
    },
    props: {
        item: Object,
    },
    data() {
        return {
            is_loading: false,
            new_item_version: {
                type: TYPE_FILE,
                link_properties: {
                    link_url: "",
                },
                file_properties: {
                    file: "",
                },
                embedded_properties: {
                    content: "",
                },
            },
        };
    },
    computed: {
        ...mapState("error", ["has_modal_error"]),
        ...mapState(["project_id"]),
        submit_button_label() {
            return this.$gettext("Create new version");
        },
        modal_title() {
            return sprintf(this.$gettext('New version for "%s"'), this.item.title);
        },
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.modal.show();
    },
    methods: {
        reset() {
            this.$store.commit("error/resetModalError");
            this.is_loading = false;
            this.hide();
        },
        hide() {
            this.$emit("hidden");
        },
        redirectToLegacyUrl() {
            return redirectToUrl(
                `/plugins/docman/index.php?group_id=${this.project_id}&id=${this.item.id}&action=action_update`
            );
        },
        async createNewVersion(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");

            await this.$store.dispatch("createNewVersionFromEmpty", [
                this.new_item_version.type,
                this.item,
                this.new_item_version,
            ]);

            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
            }
        },
    },
};
</script>
