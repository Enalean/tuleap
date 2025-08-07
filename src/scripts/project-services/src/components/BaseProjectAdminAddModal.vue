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
    <add-modal v-bind:form_url="form_url" ref="modal" v-on:reset-modal="resetModal">
        <template v-slot:content>
            <input type="hidden" v-bind:name="csrf_token_name" v-bind:value="csrf_token" />
            <sidebar-previewer
                v-bind:label="preview_label"
                v-bind:icon_name="service.icon_name"
                v-bind:is_in_new_tab="service.is_in_new_tab"
                v-bind:allowed_icons="allowed_icons"
            />
            <in-creation-custom-service
                v-bind:minimal_rank="minimal_rank"
                v-bind:service="service"
                v-bind:allowed_icons="allowed_icons"
            />
        </template>
    </add-modal>
</template>
<script>
import AddModal from "./AddModal.vue";
import SidebarPreviewer from "./SidebarPreviewer.vue";
import InCreationCustomService from "./Service/InCreationCustomService.vue";
import { add_modal_mixin } from "./add-modal-mixin.js";

export default {
    name: "BaseProjectAdminAddModal",
    components: { AddModal, SidebarPreviewer, InCreationCustomService },
    mixins: [add_modal_mixin],
    props: {
        minimal_rank: {
            type: Number,
            required: true,
        },
        csrf_token: {
            type: String,
            required: true,
        },
        csrf_token_name: {
            type: String,
            required: true,
        },
        allowed_icons: {
            type: Object,
            required: true,
        },
    },
    computed: {
        preview_label() {
            return this.service.label === "" ? this.$gettext("Preview") : this.service.label;
        },
    },
};
</script>
