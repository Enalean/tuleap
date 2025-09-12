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
    <div class="tlp-modal-body">
        <service-label
            id="project-service-add-modal-label"
            v-bind:label="service.label"
            v-on:input="onEditServiceLabel"
        />
        <icon-selector
            id="project-service-add-modal-icon"
            v-bind:icon_name="service.icon_name"
            v-on:input="onEditIcon"
        />
        <service-is-used
            id="project-service-add-modal-enabled"
            v-bind:is_used="service.is_used"
            v-bind:is_disabled_reason="service.is_disabled_reason"
        />
        <slot name="is_active">
            <hidden-service-is-active v-bind:is_active="service.is_active" />
        </slot>
        <service-rank id="project-service-add-modal-rank" v-bind:rank="service.rank" />
        <service-link
            id="project-service-add-modal-link"
            v-bind:link="service.link"
            v-bind:disabled="false"
        />
        <slot name="shortname" />
        <service-description
            id="project-service-add-modal-description"
            v-bind:description="service.description"
        />

        <service-open-in-new-tab
            id="project-service-add-modal-new-tab"
            v-bind:is_in_new_tab="service.is_in_new_tab"
            v-on:input="onNewTabChange"
        />
    </div>
</template>
<script setup lang="ts">
import { ref } from "vue";
import type { Service } from "../../type";
import ServiceOpenInNewTab from "./ServiceOpenInNewTab.vue";
import IconSelector from "./IconSelector.vue";
import ServiceLabel from "./ServiceLabel.vue";
import ServiceLink from "./ServiceLink.vue";
import ServiceDescription from "./ServiceDescription.vue";
import ServiceIsUsed from "./ServiceIsUsed.vue";
import ServiceRank from "./ServiceRank.vue";
import HiddenServiceIsActive from "./HiddenServiceIsActive.vue";

const props = defineProps<{
    service_prop: Service;
}>();

const service = ref(props.service_prop);

function onEditServiceLabel(new_label: string): void {
    service.value.label = new_label;
}

function onEditIcon(new_icon: string): void {
    service.value.icon_name = new_icon;
}

function onNewTabChange(new_tab: boolean): void {
    service.value.is_in_new_tab = new_tab;
}
</script>
