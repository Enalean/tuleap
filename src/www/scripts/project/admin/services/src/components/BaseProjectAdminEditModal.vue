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
    <edit-modal
        v-bind:form_url="form_url"
        ref="modal"
        v-on:reset-modal="resetModal"
    >
        <template v-slot:content>
            <input type="hidden" v-bind:name="csrf_token_name" v-bind:value="csrf_token">
            <sidebar-previewer v-bind:label="service.label" v-bind:icon_name="service.icon_name"/>
            <project-defined-service
                v-bind:minimal_rank="minimal_rank"
                v-bind:service="service"
                v-bind:allowed_icons="allowed_icons"
            />
        </template>
    </edit-modal>
</template>
<script>
import ProjectDefinedService from "./ProjectDefinedService.vue";
import SidebarPreviewer from "./SidebarPreviewer.vue";
import EditModal from "./EditModal.vue";
import { edit_modal_mixin } from "./edit-modal-mixin.js";

export default {
    name: "BaseProjectAdminEditModal",
    components: { EditModal, ProjectDefinedService, SidebarPreviewer },
    mixins: [edit_modal_mixin]
};
</script>
