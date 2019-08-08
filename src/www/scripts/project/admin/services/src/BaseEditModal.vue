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
        aria-labelledby="project-admin-services-custom-edit-modal-title"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="project-admin-services-custom-edit-modal-title">
                <i class="fa fa-pencil tlp-modal-title-icon"></i>
                <translate>Edit service</translate>
            </h1>
            <div class="tlp-modal-close" data-dismiss="modal" aria-label="close_label">
                ×
            </div>
        </div>
        <input type="hidden" v-bind:name="csrf_token_name" v-bind:value="csrf_token">
        <project-scope-service
            v-bind:minimal_rank="minimal_rank"
            v-bind:service="service"
        />
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >
                Cancel
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action user-group-modal-button"
                data-test="save-service-modifications"
            >
                <i class="fa fa-save tlp-button-icon"></i>
                <translate>Save modifications</translate>
            </button>
        </div>
    </form>
</template>
<script>
import { modal as createModal } from "tlp";
import ProjectScopeService from "./ProjectScopeService.vue";

export default {
    name: "BaseEditModal",
    components: { ProjectScopeService },
    props: {
        project_id: {
            type: String,
            required: true
        },
        minimal_rank: {
            type: String,
            required: true
        },
        csrf_token: {
            type: String,
            required: true
        },
        csrf_token_name: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            modal: null,
            service: this.resetService()
        };
    },
    computed: {
        form_url() {
            return `/project/${encodeURIComponent(this.project_id)}/admin/services/edit`;
        },
        close_label() {
            return this.$gettext("Close");
        }
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-hidden", this.resetModal);
    },
    beforeDestroy() {
        if (this.modal !== null) {
            this.modal.destroy();
        }
    },
    methods: {
        resetModal() {
            this.service = this.resetService();
        },
        resetService() {
            return {
                id: null,
                label: null,
                link: null,
                description: null,
                is_active: true,
                is_in_iframe: false,
                rank: this.minimal_rank
            };
        },
        show(button) {
            this.service = JSON.parse(button.dataset.serviceJson);
            this.modal.show();
        }
    }
};
</script>
