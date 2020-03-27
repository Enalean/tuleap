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
    <div
        class="document-file-upload-progress"
        v-bind:class="{ 'document-file-upload-progress-canceled': is_canceled }"
    >
        <span class="document-file-upload-progress-value">{{ item.progress }}%</span>
        <progress
            class="document-file-upload-progress-bar"
            max="100"
            v-bind:value="item.progress"
        ></progress>
        <a
            class="document-file-upload-progress-cancel tlp-tooltip tlp-tooltip-left"
            href="#"
            v-bind:aria-label="cancel_title"
            v-bind:data-tlp-tooltip="cancel_title"
            role="button"
            v-on:click.prevent="cancel"
            data-test="cancel-upload"
        >
            <i class="fa fa-times-circle"></i>
        </a>
    </div>
</template>

<script>
import { mapActions } from "vuex";
import { TYPE_FOLDER } from "../../../constants.js";

export default {
    props: {
        item: Object,
    },
    data() {
        return {
            is_canceled: false,
        };
    },
    computed: {
        cancel_title() {
            return this.$gettext("Cancel upload");
        },
    },
    methods: {
        ...mapActions(["cancelFileUpload", "cancelFolderUpload", "cancelVersionUpload"]),
        cancel() {
            if (!this.is_canceled) {
                this.is_canceled = true;
                if (this.item.is_uploading_new_version) {
                    this.cancelVersionUpload(this.item);
                } else if (this.item.type !== TYPE_FOLDER) {
                    this.cancelFileUpload(this.item);
                } else {
                    this.cancelFolderUpload(this.item);
                }
            }
        },
    },
};
</script>
