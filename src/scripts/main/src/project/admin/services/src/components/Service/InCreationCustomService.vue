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
        <service-label id="project-service-add-modal-label" v-model="service.label" />
        <icon-selector
            id="project-service-add-modal-icon"
            v-model="service.icon_name"
            v-bind:allowed_icons="allowed_icons"
        />
        <service-is-used
            id="project-service-add-modal-enabled"
            v-bind:value="service.is_used"
            v-bind:disabled-reason="service.is_disabled_reason"
        />
        <slot name="is_active">
            <hidden-service-is-active v-bind:value="service.is_active" />
        </slot>
        <service-rank
            id="project-service-add-modal-rank"
            v-bind:minimal_rank="minimal_rank"
            v-bind:value="service.rank"
        />
        <service-link id="project-service-add-modal-link" v-bind:value="service.link" />
        <slot name="shortname" />
        <service-description
            id="project-service-add-modal-description"
            v-bind:value="service.description"
        />

        <service-open-in-new-tab
            id="project-service-add-modal-new-tab"
            v-bind:value="service.is_in_new_tab"
            v-on:change="onNewTabChange"
        />
    </div>
</template>
<script>
import ServiceOpenInNewTab from "./ServiceOpenInNewTab.vue";
import IconSelector from "./IconSelector.vue";
import ServiceLabel from "./ServiceLabel.vue";
import ServiceLink from "./ServiceLink.vue";
import ServiceDescription from "./ServiceDescription.vue";
import ServiceIsUsed from "./ServiceIsUsed.vue";
import ServiceRank from "./ServiceRank.vue";
import HiddenServiceIsActive from "./HiddenServiceIsActive.vue";
import { service_mixin } from "./service-mixin.js";

export default {
    name: "InCreationCustomService",
    components: {
        ServiceOpenInNewTab,
        HiddenServiceIsActive,
        ServiceRank,
        ServiceIsUsed,
        ServiceDescription,
        ServiceLink,
        ServiceLabel,
        IconSelector,
    },
    mixins: [service_mixin],
    props: {
        allowed_icons: {
            type: Object,
            required: true,
        },
    },
    methods: {
        onNewTabChange($event) {
            this.service.is_in_new_tab = $event.target.checked;
        },
    },
};
</script>
