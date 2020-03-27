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
    <div
        class="tlp-form-element document-metadata-title"
        v-bind:class="{ 'tlp-form-element-disabled': is_root_folder }"
        data-test="document-new-item-title-form-element"
    >
        <label class="tlp-label" for="document-new-item-title">
            <translate>Title</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <input
            type="text"
            class="tlp-input"
            id="document-new-item-title"
            name="title"
            v-bind:placeholder="placeholder"
            required
            v-bind:value="value"
            v-bind:disabled="is_root_folder"
            v-on:input="$emit('input', $event.target.value)"
            ref="input"
            data-test="document-new-item-title"
        />
        <p class="tlp-text-danger" v-if="error_message.length > 0" data-test="title-error-message">
            {{ error_message }}
        </p>
    </div>
</template>
<script>
import { mapState } from "vuex";
import { TYPE_FOLDER } from "../../../constants.js";
import {
    doesDocumentAlreadyExistsAtUpdate,
    doesDocumentNameAlreadyExist,
    doesFolderNameAlreadyExist,
    doesFolderAlreadyExistsAtUpdate,
} from "../../../helpers/metadata-helpers/check-item-title.js";

export default {
    props: {
        value: String,
        parent: Object,
        isInUpdateContext: Boolean,
        currentlyUpdatedItem: Object,
    },
    data() {
        return {
            error_message: "",
        };
    },
    computed: {
        ...mapState(["folder_content"]),
        placeholder() {
            return this.$gettext("My document");
        },
        is_root_folder() {
            return this.currentlyUpdatedItem.parent_id === 0;
        },
    },
    watch: {
        value(text_value) {
            const error = this.checkTitleValidity(text_value);
            this.$refs.input.setCustomValidity(error);
            this.error_message = error;
        },
    },
    mounted() {
        this.$refs.input.focus();
    },
    methods: {
        checkTitleValidity(text_value) {
            if (this.isInUpdateContext === true) {
                return this.getValidityErrorAtUpdate(
                    text_value,
                    this.type,
                    this.currentlyUpdatedItem,
                    this.parent
                );
            }
            return this.getValidityErrorAtCreation(text_value, this.type, this.parent);
        },
        getValidityErrorAtCreation(text_value) {
            if (
                this.currentlyUpdatedItem.type === TYPE_FOLDER &&
                doesFolderNameAlreadyExist(text_value, this.folder_content, this.parent)
            ) {
                return this.getErrorWhenFolderAlreadyExists();
            } else if (
                this.currentlyUpdatedItem.type !== TYPE_FOLDER &&
                doesDocumentNameAlreadyExist(text_value, this.folder_content, this.parent)
            ) {
                return this.getErrorWhenDocumentAlreadyExists();
            }

            return "";
        },
        getValidityErrorAtUpdate(text_value) {
            if (
                this.currentlyUpdatedItem.type !== TYPE_FOLDER &&
                doesDocumentAlreadyExistsAtUpdate(
                    text_value,
                    this.folder_content,
                    this.currentlyUpdatedItem,
                    this.parent
                )
            ) {
                return this.getErrorWhenDocumentAlreadyExists();
            } else if (
                this.currentlyUpdatedItem.type === TYPE_FOLDER &&
                doesFolderAlreadyExistsAtUpdate(
                    text_value,
                    this.folder_content,
                    this.currentlyUpdatedItem,
                    this.parent
                )
            ) {
                return this.getErrorWhenFolderAlreadyExists();
            }

            return "";
        },
        getErrorWhenDocumentAlreadyExists() {
            return this.$gettext("A document already exists with the same title.");
        },
        getErrorWhenFolderAlreadyExists() {
            return this.$gettext("A folder already exists with the same title.");
        },
    },
};
</script>
