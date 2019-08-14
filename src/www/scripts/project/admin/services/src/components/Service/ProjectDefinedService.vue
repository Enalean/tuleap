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
        <service-id v-bind:value="service.id"/>

        <div class="project-admin-services-edit-modal-top-fields">
            <service-label v-model="service.label"/>
            <service-is-used v-bind:value="service.is_used"/>
        </div>
        <div class="project-admin-services-edit-modal-top-fields">
            <icon-selector v-model="service.icon_name" v-bind:allowed_icons="allowed_icons"/>
            <slot name="is_active">
                <hidden-service-is-active v-bind:value="service.is_active"/>
            </slot>
        </div>

        <service-rank v-bind:minimal_rank="minimal_rank" v-bind:value="service.rank"/>
        <service-link v-bind:value="service.link"/>
        <service-description v-bind:value="service.description"/>

        <div class="tlp-form-element">
            <label
                class="tlp-label"
                for="project-admin-services-edit-modal-iframe"
                v-translate
            >Display in iframe</label>
            <div class="tlp-switch">
                <input
                    class="tlp-switch-checkbox"
                    id="project-admin-services-edit-modal-iframe"
                    type="checkbox"
                    name="is_in_iframe"
                    value="1"
                    v-bind:checked="service.is_in_iframe"
                >
                <label
                    class="tlp-switch-button"
                    for="project-admin-services-edit-modal-iframe"
                    aria-hidden
                ></label>
            </div>
        </div>
    </div>
</template>
<script>
import IconSelector from "./IconSelector.vue";
import ServiceId from "./ServiceId.vue";
import ServiceLabel from "./ServiceLabel.vue";
import ServiceLink from "./ServiceLink.vue";
import ServiceDescription from "./ServiceDescription.vue";
import ServiceIsUsed from "./ServiceIsUsed.vue";
import ServiceRank from "./ServiceRank.vue";
import HiddenServiceIsActive from "./HiddenServiceIsActive.vue";
import { service_mixin } from "./service-mixin.js";

export default {
    name: "ProjectDefinedService",
    components: {
        HiddenServiceIsActive,
        ServiceRank,
        ServiceIsUsed,
        ServiceDescription,
        ServiceLink,
        ServiceLabel,
        IconSelector,
        ServiceId
    },
    mixins: [service_mixin],
    props: {
        allowed_icons: {
            type: Object,
            required: true
        }
    }
};
</script>
