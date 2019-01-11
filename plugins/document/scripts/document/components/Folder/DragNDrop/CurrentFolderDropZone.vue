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
    <div class="document-upload-to-current-folder" v-bind:class="classes">
        <div v-if="user_can_dragndrop_in_current_folder" class="document-upload-to-current-folder-message">
            <i class="fa fa-rotate-90 fa-mail-forward document-upload-to-current-folder-icon"></i>
            <p>{{ message_success }}</p>
        </div>
        <div v-else class="document-upload-to-current-folder-message">
            <i class="fa fa-ban document-upload-to-current-folder-icon"></i>
            <p>{{ message_error }}</p>
        </div>
    </div>
</template>

<script>
import { mapState, mapGetters } from "vuex";
import { sprintf } from "sprintf-js";

export default {
    props: {
        user_can_dragndrop_in_current_folder: Boolean,
        is_dropzone_highlighted: Boolean
    },
    computed: {
        ...mapGetters(["current_folder_title"]),
        ...mapState(["max_files_dragndrop"]),
        message_success() {
            return this.$gettextInterpolate(
                this.$ngettext(
                    "Drop one file to upload it to %{ folder }.",
                    "Drop up to %{ n } files to upload them to %{ folder }.",
                    this.max_files_dragndrop
                ),
                {
                    n: this.max_files_dragndrop,
                    folder: this.current_folder_title
                }
            );
        },
        message_error() {
            return sprintf(
                this.$gettext("Dropping files in %s is forbidden."),
                this.current_folder_title
            );
        },
        upload_current_folder_class() {
            return this.user_can_dragndrop_in_current_folder ? "shown-success" : "shown-error";
        },
        classes() {
            return this.is_dropzone_highlighted ? this.upload_current_folder_class : "";
        }
    }
};
</script>
