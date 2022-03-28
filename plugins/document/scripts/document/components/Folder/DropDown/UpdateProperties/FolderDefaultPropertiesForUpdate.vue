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

<!-- eslint-disable vue/no-mutating-props -->
<template>
    <div v-if="has_recursion_proerty" data-test="document-folder-default-properties-container">
        <hr class="tlp-modal-separator" />
        <div class="document-modal-other-information-title-container">
            <div
                v-if="!has_loaded_properties"
                class="document-modal-other-information-title-container-spinner"
                data-test="document-folder-default-properties-spinner"
            >
                <i class="fa fa-spin fa-circle-o-notch"></i>
            </div>
            <h2 class="tlp-modal-subtitle" v-translate>Default properties</h2>
        </div>
        <template v-if="has_loaded_properties">
            <p v-translate>
                All the properties values that you define here will be proposed as default values
                for the items that will be created within this folder.
            </p>
            <div class="document-default-properties">
                <div class="document-properties-container" v-if="is_status_property_used">
                    <div class="document-recursion-checkbox-container">
                        <label class="tlp-label"><i class="fas fa-redo"></i></label>
                        <input
                            id="status"
                            type="checkbox"
                            class="document-recursion-checkbox"
                            value="status"
                            ref="status_input"
                        />
                    </div>
                    <status-property-with-custom-binding-for-folder-update
                        v-bind:status_value="status_value"
                    />
                </div>
                <div
                    v-for="custom in itemProperty"
                    v-bind:key="custom.short_name"
                    class="document-properties-container"
                >
                    <div class="document-recursion-checkbox-container">
                        <label class="tlp-label"><i class="fas fa-redo"></i></label>
                        <input
                            v-bind:id="custom.short_name"
                            type="checkbox"
                            class="document-recursion-checkbox"
                            v-on:change="updatePropertiesWithRecursion"
                            v-model="properties_to_update"
                            v-bind:value="custom.short_name"
                            data-test="document-custom-property-checkbox"
                        />
                    </div>
                    <custom-property-component-type-renderer
                        v-bind:item-property="custom"
                        v-on:input="custom.value = $event.target.value"
                    />
                </div>
                <recursion-options
                    v-model="recursion_option"
                    v-on:input="updateRecursionOption"
                    data-test="document-custom-property-recursion-option"
                />
            </div>
        </template>
    </div>
</template>

<!-- eslint-disable vue/no-mutating-props -->
<script>
import { mapState } from "vuex";
import StatusPropertyWithCustomBindingForFolderUpdate from "./StatusPropertyWithCustomBindingForFolderUpdate.vue";
import CustomPropertyComponentTypeRenderer from "../PropertiesForCreateOrUpdate/CustomProperties/CustomPropertyComponentTypeRenderer.vue";
import RecursionOptions from "../PropertiesForCreateOrUpdate/RecursionOptions.vue";
import emitter from "../../../../helpers/emitter";

export default {
    name: "FolderDefaultPropertiesForUpdate",
    components: {
        RecursionOptions,
        CustomPropertyComponentTypeRenderer,
        StatusPropertyWithCustomBindingForFolderUpdate,
    },
    props: {
        currentlyUpdatedItem: Object,
        itemProperty: Array,
        status_value: String,
    },
    data() {
        return {
            properties_to_update: [],
            recursion: "none",
        };
    },
    computed: {
        ...mapState("configuration", ["is_status_property_used"]),
        ...mapState("properties", ["has_loaded_properties"]),
        recursion_option: {
            get() {
                return "";
            },
            set(value) {
                this.currentlyUpdatedItem.status.recursion = value;
                this.recursion = value;
            },
        },
        has_recursion_proerty() {
            return this.is_status_property_used || this.itemProperty.length > 0;
        },
    },
    mounted() {
        if (!this.has_loaded_properties) {
            this.$store.dispatch("properties/loadProjectProperties");
        }
    },
    methods: {
        updatePropertiesWithRecursion() {
            emitter.emit("properties-recursion-list", {
                detail: { property_list: this.properties_to_update },
            });
        },
        updateRecursionOption() {
            this.properties_to_update = [];
            if (this.recursion !== "none") {
                this.itemProperty.forEach((property) => {
                    this.properties_to_update.push(property.short_name);
                });
                if (this.is_status_property_used) {
                    this.properties_to_update.push("status");
                    this.$refs.status_input.checked = true;
                }
            } else {
                this.$refs.status_input.checked = false;
            }
            this.updatePropertiesWithRecursion();
            emitter.emit("properties-recursion-option", {
                detail: { recursion_option: this.recursion },
            });
        },
    },
};
</script>
