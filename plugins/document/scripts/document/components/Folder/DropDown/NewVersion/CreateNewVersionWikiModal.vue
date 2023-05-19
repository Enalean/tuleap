<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
        v-on:submit="createNewWikiVersion"
        data-test="document-new-item-version-modal"
    >
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by" />
        <modal-feedback />
        <div class="tlp-modal-body">
            <div class="docman-item-update-property">
                <wiki-properties v-model="wiki_model.wiki_properties" v-bind:item="wiki_model" />
                <lock-property v-bind:item="wiki_item" v-if="wiki_item !== null" />
            </div>
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="submit_button_label"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-wiki-version"
        />
    </form>
</template>

<script>
import { mapState } from "vuex";
import { createModal } from "@tuleap/tlp-modal";
import { sprintf } from "sprintf-js";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import WikiProperties from "../PropertiesForCreateOrUpdate/WikiProperties.vue";
import LockProperty from "../Lock/LockProperty.vue";
import emitter from "../../../../helpers/emitter";

export default {
    name: "CreateNewVersionWikiModal",
    components: {
        WikiProperties,
        ModalFeedback,
        ModalHeader,
        ModalFooter,
        LockProperty,
    },
    props: {
        item: Object,
    },
    data() {
        return {
            wiki_model: {},
            version: {},
            is_loading: false,
            is_displayed: false,
            modal: null,
            wiki_item: null,
        };
    },
    computed: {
        ...mapState("error", ["has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Create new version");
        },
        modal_title() {
            return sprintf(this.$gettext('New version for "%s"'), this.item.title);
        },
        aria_labelled_by() {
            return "document-new-item-version-modal";
        },
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.wiki_item = this.item;
        this.registerEvents();

        this.show();
    },
    beforeUnmount() {
        emitter.off("update-lock", this.updateLock);
    },
    methods: {
        setApprovalUpdateAction(value) {
            this.approval_table_action = value;
        },
        registerEvents() {
            this.modal.addEventListener("tlp-modal-hidden", this.reset);
            emitter.on("update-lock", this.updateLock);
        },
        show() {
            this.version = {
                title: "",
                changelog: "",
                is_file_locked: this.wiki_item !== null && this.wiki_item.lock_info !== null,
            };
            this.wiki_model = {
                type: this.wiki_item.type,
                wiki_properties: this.wiki_item.wiki_properties,
            };
            this.is_displayed = true;
            this.modal.show();
        },
        reset() {
            this.$store.commit("error/resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
            this.wiki_model = {};
        },
        async createNewWikiVersion(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");

            await this.$store.dispatch("createNewWikiVersionFromModal", [
                this.wiki_item,
                this.wiki_model.wiki_properties.page_name,
                this.version.title,
                this.version.changelog,
                this.version.is_file_locked,
                this.approval_table_action,
            ]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.wiki_item.wiki_properties.page_name =
                    this.wiki_model.wiki_properties.page_name;
                this.$store.dispatch("refreshWiki", this.wiki_item);
                this.wiki_model = {};
                this.modal.hide();
            }
        },
        updateLock(is_locked) {
            this.version.is_file_locked = is_locked;
        },
    },
};
</script>
