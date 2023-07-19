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
        />
        <modal-feedback />

        <div class="tlp-modal-body">
            <link-properties
                v-bind:value="new_item_version.link_properties.link_url"
                v-bind:item="new_item_version"
            />
            <embedded-properties
                v-bind:value="new_item_version.embedded_properties.content"
                v-bind:item="new_item_version"
            />
            <file-properties
                v-bind:value="new_item_version.file_properties"
                v-bind:item="new_item_version"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="submit_button_label"
            v-bind:aria-labelled-by="`document-new-empty-version-modal`"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-empty"
        />
    </form>
</template>

<script>
import { mapState } from "vuex";
import { createModal } from "@tuleap/tlp-modal";
import { sprintf } from "sprintf-js";
import { TYPE_FILE } from "../../../../constants";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import EmbeddedProperties from "../PropertiesForCreateOrUpdate/EmbeddedProperties.vue";
import LinkProperties from "../PropertiesForCreateOrUpdate/LinkProperties.vue";
import FileProperties from "../PropertiesForCreateOrUpdate/FileProperties.vue";
import emitter from "../../../../helpers/emitter";

export default {
    name: "CreateNewVersionEmptyModal",
    components: {
        FileProperties,
        LinkProperties,
        ModalFeedback,
        ModalHeader,
        ModalFooter,
        EmbeddedProperties,
    },
    props: {
        item: Object,
        type: {
            required: false,
            type: String,
        },
    },
    data() {
        return {
            is_loading: false,
            new_item_version: {
                type: this.type || TYPE_FILE,
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
        ...mapState("configuration", ["project_id"]),
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
        emitter.on("update-link-properties", this.updateLinkProperties);
        emitter.on("update-wiki-properties", this.updateWikiProperties);
        emitter.on("update-embedded-properties", this.updateEmbeddedContent);
        emitter.on("update-file-properties", this.updateFilesProperties);
    },
    beforeUnmount() {
        emitter.off("update-link-properties", this.updateLinkProperties);
        emitter.off("update-wiki-properties", this.updateWikiProperties);
        emitter.off("update-embedded-properties", this.updateEmbeddedContent);
        emitter.off("update-file-properties", this.updateFilesProperties);
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
        updateLinkProperties(url) {
            this.new_item_version.link_properties.link_url = url;
        },
        updateWikiProperties(page_name) {
            this.new_item_version.wiki_properties.page_name = page_name;
        },
        updateEmbeddedContent(content) {
            this.new_item_version.embedded_properties.content = content;
        },
        updateFilesProperties(file_properties) {
            this.new_item_version.file_properties = file_properties;
        },
    },
};
</script>
