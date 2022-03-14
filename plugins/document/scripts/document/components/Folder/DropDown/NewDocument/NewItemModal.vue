<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
        aria-labelledby="document-new-item-modal"
        v-on:submit="addDocument"
        enctype="multipart/form-data"
        data-test="document-new-item-modal"
    >
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by" />
        <modal-feedback />
        <div class="tlp-modal-body document-item-modal-body" v-if="is_displayed">
            <type-selector v-model="item.type" />

            <document-global-property-for-create
                v-bind:currently-updated-item="item"
                v-bind:parent="parent"
            >
                <link-properties
                    v-model="item.link_properties"
                    v-bind:item="item"
                    name="properties"
                />
                <wiki-properties
                    v-model="item.wiki_properties"
                    v-bind:item="item"
                    name="properties"
                />
                <embedded-properties
                    v-model="item.embedded_properties"
                    v-bind:item="item"
                    name="properties"
                />
                <file-properties
                    v-model="item.file_properties"
                    v-bind:item="item"
                    name="properties"
                />
            </document-global-property-for-create>
            <other-information-properties-for-create
                v-bind:currently-updated-item="item"
                v-model="item.obsolescence_date"
            />
            <creation-modal-permissions-section
                v-if="item.permissions_for_groups"
                v-model="item.permissions_for_groups"
                v-bind:project_ugroups="project_ugroups"
            />
        </div>

        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="submit_button_label"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-submit-button-class="'fa-plus'"
        />
    </form>
</template>

<script>
import { mapState } from "vuex";
import { createModal } from "tlp";
import { TYPE_FILE } from "../../../../constants";
import DocumentGlobalPropertyForCreate from "./PropertiesForCreate/DocumentGlobalPropertyForCreate.vue";
import LinkProperties from "../PropertiesForCreateOrUpdate/LinkProperties.vue";
import WikiProperties from "../PropertiesForCreateOrUpdate/WikiProperties.vue";
import TypeSelector from "./TypeSelector.vue";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import EmbeddedProperties from "../PropertiesForCreateOrUpdate/EmbeddedProperties.vue";
import FileProperties from "../PropertiesForCreateOrUpdate/FileProperties.vue";
import OtherInformationPropertiesForCreate from "./PropertiesForCreate/OtherInformationPropertiesForCreate.vue";
import { getCustomProperties } from "../../../../helpers/properties-helpers/custom-properties-helper";
import { handleErrors } from "../../../../store/actions-helpers/handle-errors";
import CreationModalPermissionsSection from "./CreationModalPermissionsSection.vue";
import { transformCustomPropertiesForItemCreation } from "../../../../helpers/properties-helpers/creation-data-transformatter-helper";
import emitter from "../../../../helpers/emitter";

export default {
    name: "NewItemModal",
    components: {
        OtherInformationPropertiesForCreate,
        DocumentGlobalPropertyForCreate,
        FileProperties,
        EmbeddedProperties,
        ModalFooter,
        ModalHeader,
        LinkProperties,
        WikiProperties,
        TypeSelector,
        CreationModalPermissionsSection,
        ModalFeedback,
    },
    data() {
        return {
            item: {},
            is_displayed: false,
            is_loading: false,
            modal: null,
            parent: {},
        };
    },
    computed: {
        ...mapState(["current_folder"]),
        ...mapState("configuration", [
            "project_id",
            "is_status_property_used",
            "is_obsolescence_date_property_used",
        ]),
        ...mapState("error", ["has_modal_error"]),
        ...mapState("properties", ["has_loaded_properties"]),
        ...mapState("permissions", ["project_ugroups"]),
        submit_button_label() {
            return this.$gettext("Create document");
        },
        modal_title() {
            return this.$gettext("New document");
        },
        aria_labelled_by() {
            return "document-new-item-modal";
        },
    },
    mounted() {
        this.modal = createModal(this.$el);
        emitter.on("createItem", this.show);
        emitter.on("update-multiple-properties-list-value", this.updateMultiplePropertiesListValue);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
    },
    beforeDestroy() {
        emitter.off("createItem", this.show);
        emitter.off(
            "update-multiple-properties-list-value",
            this.updateMultiplePropertiesListValue
        );
        this.modal.removeEventListener("tlp-modal-hidden", this.reset);
    },
    methods: {
        getDefaultItem() {
            return {
                title: "",
                description: "",
                type: TYPE_FILE,
                link_properties: {
                    link_url: "",
                },
                wiki_properties: {
                    page_name: "",
                },
                file_properties: {
                    file: "",
                },
                embedded_properties: {
                    content: "",
                },
                obsolescence_date: "",
                properties: null,
                permissions_for_groups: {
                    can_read: [],
                    can_write: [],
                    can_manage: [],
                },
            };
        },
        async show(event) {
            this.item = this.getDefaultItem();
            this.parent = event.item;
            this.addParentPropertiesToDefaultItem();
            this.item.permissions_for_groups = JSON.parse(
                JSON.stringify(this.parent.permissions_for_groups)
            );

            if (this.parent.obsolescence_date) {
                this.item.obsolescence_date = this.parent.obsolescence_date;
            }

            this.is_displayed = true;
            this.modal.show();
            try {
                await this.$store.dispatch(
                    "permissions/loadProjectUserGroupsIfNeeded",
                    this.project_id
                );
            } catch (e) {
                await handleErrors(this.$store, e);
                this.modal.hide();
            }
        },
        reset() {
            this.$store.commit("error/resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
            this.item = this.getDefaultItem();
        },
        async addDocument(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");
            await this.$store.dispatch("createNewItem", [
                this.item,
                this.parent,
                this.current_folder,
            ]);

            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
            }
        },
        addParentPropertiesToDefaultItem() {
            const parent_properties = getCustomProperties(this.parent.properties);

            const formatted_properties =
                transformCustomPropertiesForItemCreation(parent_properties);

            if (formatted_properties.length > 0) {
                this.item.properties = formatted_properties;
            }
        },
        updateMultiplePropertiesListValue(event) {
            if (!this.item.properties) {
                return;
            }
            const item_properties = this.item.properties.find(
                (property) => property.short_name === event.detail.id
            );
            item_properties.list_value = event.detail.value;
        },
    },
};
</script>
