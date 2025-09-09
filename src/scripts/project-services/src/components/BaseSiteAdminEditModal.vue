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
    <edit-modal v-bind:form_url="form_url" ref="modal" v-on:reset-modal="resetModal">
        <template v-slot:content>
            <input type="hidden" v-bind:name="csrf_token.name" v-bind:value="csrf_token.value" />
            <sidebar-previewer
                v-bind:label="service.label"
                v-bind:icon_name="service.icon_name"
                v-bind:is_in_new_tab="service.is_in_new_tab"
            />
            <in-edition-custom-service
                v-if="service.is_project_scope && is_shown"
                v-bind:service_prop="service"
            >
                <template v-slot:is_active>
                    <service-is-active
                        id="project-service-edit-modal-active"
                        v-bind:is_active="service.is_active"
                    />
                </template>
            </in-edition-custom-service>
            <editable-system-service
                v-if="!service.is_project_scope && is_shown"
                v-bind:service_prop="service"
            />
        </template>
    </edit-modal>
</template>
<script setup lang="ts">
import { computed, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Service } from "../type";
import { CSRF_TOKEN, MINIMAL_RANK, PROJECT_ID } from "../injection-symbols";
import InEditionCustomService from "./Service/InEditionCustomService.vue";
import EditModal from "./EditModal.vue";
import ServiceIsActive from "./Service/ServiceIsActive.vue";
import SidebarPreviewer from "./SidebarPreviewer.vue";
import EditableSystemService from "./Service/EditableSystemService.vue";

const project_id = strictInject(PROJECT_ID);
const minimal_rank = strictInject(MINIMAL_RANK);
const csrf_token = strictInject(CSRF_TOKEN);

const is_shown = ref(false);
const service = ref(resetService());
const modal = ref<typeof EditModal>();

const form_url = computed(() => `/project/${encodeURIComponent(project_id)}/admin/services/edit`);

function show(button: HTMLButtonElement): void {
    if (!button.dataset.serviceJson) {
        return;
    }

    is_shown.value = true;
    service.value = JSON.parse(button.dataset.serviceJson);
    modal.value?.show();
}

function resetModal(): void {
    is_shown.value = false;
    service.value = resetService();
}

function resetService(): Service {
    return {
        id: null,
        icon_name: "",
        label: "",
        link: "",
        description: "",
        is_active: true,
        is_used: true,
        is_in_iframe: false,
        is_in_new_tab: false,
        rank: minimal_rank,
        is_project_scope: true,
        is_disabled_reason: "",
        is_link_customizable: false,
        short_name: "",
    };
}

defineExpose({ show });
</script>
