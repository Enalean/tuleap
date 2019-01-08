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
    <div class="document-upload-to-current-folder"
         v-if="current_folder"
    >
        <div v-if="current_folder.user_can_write" class="document-upload-to-current-folder-message">
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
    computed: {
        ...mapGetters(["current_folder_title"]),
        ...mapState(["current_folder"]),
        upload_current_folder_class() {
            return this.current_folder && this.current_folder.user_can_write
                ? "shown-success"
                : "shown-error";
        },
        message_success() {
            return sprintf(
                this.$gettext("Drop your file to upload it to %s."),
                this.current_folder_title
            );
        },
        message_error() {
            return sprintf(
                this.$gettext("Dropping files in %s is forbidden."),
                this.current_folder_title
            );
        }
    },
    mounted() {
        const main = document.querySelector(".document-main");
        main.addEventListener("dragover", event => {
            this.highlight();
            event.preventDefault();
            event.stopPropagation();
        });
        main.addEventListener("dragleave", event => {
            this.unhighlight();
            event.preventDefault();
            event.stopPropagation();
        });
        main.addEventListener("drop", event => {
            this.unhighlight();
            event.preventDefault();
            event.stopPropagation();
        });
    },
    methods: {
        unhighlight() {
            this.$el.classList.remove(this.upload_current_folder_class);
        },
        highlight() {
            this.$el.classList.add(this.upload_current_folder_class);
        }
    }
};
</script>
