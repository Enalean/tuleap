<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label
            class="tlp-label"
            for="document-new-item-title"
        >
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
            v-on:input="$emit('input', $event.target.value)"
            ref="input"
        >
        <p class="tlp-text-danger" v-if="error_message.length > 0">
            <i class="fa fa-info-circle"></i>
            {{ error_message }}
        </p>
    </div>
</template>
<script>
import { mapState } from "vuex";
import { TYPE_FOLDER } from "../../../../constants.js";
export default {
    props: {
        value: String,
        type: String
    },
    data() {
        return {
            error_message: ""
        };
    },
    computed: {
        ...mapState(["folder_content", "current_folder"]),
        placeholder() {
            return this.$gettext("My document");
        }
    },
    watch: {
        value(text_value) {
            let error = "";
            if (this.type === TYPE_FOLDER) {
                const does_folder_already_exist = this.folder_content.find(
                    item =>
                        item.title === text_value &&
                        item.type === TYPE_FOLDER &&
                        item.parent_id === this.current_folder.id
                );

                if (does_folder_already_exist) {
                    error = this.$gettext("A folder already exists with the same title.");
                }
            } else {
                const does_document_already_exist = this.folder_content.find(
                    item =>
                        item.title === text_value &&
                        item.type !== TYPE_FOLDER &&
                        item.parent_id === this.current_folder.id
                );

                if (does_document_already_exist) {
                    error = this.$gettext("A document already exists with the same title.");
                }
            }

            this.$refs.input.setCustomValidity(error);
            this.error_message = error;
        }
    },
    mounted() {
        this.$refs.input.focus();
    }
};
</script>
