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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-form-element" v-if="is_displayed">
        <label class="tlp-label" for="document-new-file-upload">
            <translate>File</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <div class="tlp-form-element">
            <input
                type="file"
                id="document-new-file-upload"
                name="file-upload"
                required
                v-on:change="onFileChange"
                ref="input"
            />
            <p class="tlp-text-danger" v-if="error_message.length > 0">
                {{ error_message }}
            </p>
        </div>
    </div>
</template>

<script>
import { TYPE_FILE } from "../../../constants.js";
import { mapState } from "vuex";
import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";

export default {
    name: "FileProperties",
    props: {
        value: Object,
        item: Object,
    },
    data() {
        return {
            error_message: "",
        };
    },
    computed: {
        ...mapState(["max_size_upload"]),
        is_displayed() {
            return this.item.type === TYPE_FILE;
        },
    },
    methods: {
        onFileChange(e) {
            var files = e.target.files || e.dataTransfer.files;
            if (!files.length) {
                return;
            }

            const file = files.item(0);
            if (!this.item.title) {
                this.item.title = file.name;
            }

            this.error_message = "";
            if (file.size > this.max_size_upload) {
                this.error_message = sprintf(
                    this.$gettext("You are not allowed to upload files bigger than %s."),
                    prettyKibibytes(this.max_size_upload)
                );
            }

            this.$refs.input.setCustomValidity(this.error_message);

            this.$emit("input", { file });
        },
    },
};
</script>
