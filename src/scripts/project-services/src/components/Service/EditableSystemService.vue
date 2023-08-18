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
        <service-id v-bind:value="service.id" />
        <hidden-service-shortname v-if="service.short_name" v-bind:value="service.short_name" />
        <service-label id="project-service-edit-modal-label" v-model="service.label" />
        <read-only-service-icon v-bind:value="service.icon_name" />
        <service-is-used
            v-if="can_update_is_used"
            id="project-service-edit-modal-enabled"
            v-bind:value="service.is_used"
            v-bind:disabled-reason="service.is_disabled_reason"
        />
        <service-is-active
            id="project-service-edit-modal-active"
            v-bind:value="service.is_active"
        />
        <div class="tlp-property" v-if="service.shortname">
            <label class="tlp-label" v-translate>Short name</label>
            <span>{{ service.short_name }}</span>
        </div>
        <read-only-service-rank v-if="is_summary_service" v-bind:value="service.rank" />
        <service-rank
            v-else
            id="project-service-edit-modal-rank"
            v-bind:minimal_rank="minimal_rank"
            v-bind:value="service.rank"
        />
        <service-link id="project-service-edit-modal-link" v-bind:value="service.link" />
        <service-description
            id="project-service-edit-modal-description"
            v-bind:value="service.description"
        />
    </div>
</template>
<script>
import ServiceId from "./ServiceId.vue";
import HiddenServiceShortname from "./HiddenServiceShortname.vue";
import ServiceLabel from "./ServiceLabel.vue";
import ServiceIsUsed from "./ServiceIsUsed.vue";
import ServiceIsActive from "./ServiceIsActive.vue";
import ServiceRank from "./ServiceRank.vue";
import ServiceLink from "./ServiceLink.vue";
import ServiceDescription from "./ServiceDescription.vue";
import ReadOnlyServiceRank from "./ReadOnlyServiceRank.vue";
import ReadOnlyServiceIcon from "./ReadOnlyServiceIcon.vue";
import { system_service_mixin } from "./system-service-mixin.js";
import { service_mixin } from "./service-mixin.js";

export default {
    name: "EditableSystemService",
    components: {
        ServiceId,
        HiddenServiceShortname,
        ServiceLabel,
        ServiceIsUsed,
        ServiceIsActive,
        ServiceRank,
        ReadOnlyServiceRank,
        ServiceLink,
        ServiceDescription,
        ReadOnlyServiceIcon,
    },
    mixins: [service_mixin, system_service_mixin],
};
</script>
