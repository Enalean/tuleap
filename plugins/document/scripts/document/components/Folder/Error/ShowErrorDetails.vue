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
    <div class="empty-page-text-with-small-text">
        <translate>Oops, there's an issue.</translate>
        <div class="empty-page-small-text" v-translate>It seems the content of this element can't be loaded.</div>
        <template v-if="has_any_loading_error">
            <div class="document-folder-error-link">
                <a v-if="! is_more_shown"
                   data-test="error-details-show-more-button"
                   v-on:click="is_more_shown = true"
                   href="javascript:void(0)"
                   v-translate
                >Show error details</a>
            </div>
            <pre v-if="is_more_shown"
                 class="document-folder-error-details"
                 data-test="show-more-error-message"
            >{{ error_message }}</pre>
        </template>
    </div>
</template>

<script>
import { mapState, mapGetters } from "vuex";
export default {
    name: "ShowErrorDetails",
    ...mapState("error", [
        "folder_loading_error",
        "has_folder_loading_error",
        "has_document_loading_error",
        "document_loading_error"
    ]),
    data() {
        return {
            is_more_shown: false
        };
    },
    computed: {
        ...mapState("error", [
            "folder_loading_error",
            "has_folder_loading_error",
            "has_document_loading_error",
            "document_loading_error"
        ]),
        ...mapGetters("error", ["has_any_loading_error"]),
        error_message() {
            if (this.has_folder_loading_error) {
                return this.folder_loading_error;
            }

            return this.document_loading_error;
        }
    }
};
</script>
