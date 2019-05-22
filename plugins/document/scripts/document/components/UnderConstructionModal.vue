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
    <div class="tlp-modal tlp-modal-info" role="dialog" aria-labelledby="document-modal-under-construction">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-modal-under-construction">
                <translate>Welcome to the new documents manager</translate>
            </h1>
            <div class="tlp-modal-close" data-dismiss="modal" v-bind:aria-label="close">
                &times;
            </div>
        </div>
        <div class="tlp-modal-body">
            <p v-translate>
                <b>The new interface is under construction</b>, all features are not available for now. Here's the list of what is yet to be implemented:
            </p>
            <ul>
                <li v-translate>Cut / Copy / Paste documents</li>
                <li v-translate>Update modal for documents of type "empty"</li>
                <li v-translate>Set the values of custom document properties in the document creation and update modals</li>
                <li v-translate>Set the read and write permissions of a document in the document creation and update modals</li>
                <li v-translate>Share the preview url of a document</li>
            </ul>
            <p v-translate>
                You can switch to the old Document Manager UI whenever you want. (The switch is at the bottom of the page)
            </p>
        </div>
        <div class="tlp-modal-footer tlp-modal-footer-large">
            <button type="button"
                    class="tlp-button-info tlp-modal-action"
                    data-dismiss="modal"
                    v-on:click="unsetUserPreference()"
                    v-translate
            >
                OK, got it
            </button>
        </div>
    </div>
</template>
<script>
import { modal as createModal } from "tlp";

export default {
    name: "UnderConstructionModal",
    computed: {
        close() {
            return this.$gettext("Close");
        }
    },
    mounted() {
        const modal = createModal(this.$el);
        modal.show();
    },
    methods: {
        unsetUserPreference() {
            this.$store.dispatch("unsetUnderConstructionUserPreference");
        }
    }
};
</script>
