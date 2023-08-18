<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <form
        method="post"
        v-bind:action="form_url"
        class="tlp-modal"
        role="dialog"
        aria-labelledby="project-admin-services-add-modal-title"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="project-admin-services-add-modal-title">
                <translate>Add a new service</translate>
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close_label"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <slot name="content" />
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >
                Cancel
            </button>
            <button type="submit" class="tlp-button-primary tlp-modal-action">
                <i class="fa fa-plus tlp-button-icon"></i>
                <translate>Add service</translate>
            </button>
        </div>
    </form>
</template>
<script>
import { createModal } from "@tuleap/tlp-modal";

export default {
    name: "AddModal",
    props: {
        form_url: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            modal: null,
        };
    },
    computed: {
        close_label() {
            return this.$gettext("Close");
        },
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-hidden", () => {
            this.$emit("reset-modal");
        });
    },
    beforeDestroy() {
        if (this.modal !== null) {
            this.modal.destroy();
        }
    },
    methods: {
        show() {
            this.modal.show();
        },
    },
};
</script>
