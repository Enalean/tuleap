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
        <read-only-service-icon v-bind:value="service.icon_name" />
        <div class="tlp-property">
            <label class="tlp-label" v-translate>Label</label>
            <span>{{ service.label }}</span>
            <input type="hidden" name="label" v-bind:value="service.label" />
        </div>
        <service-is-used
            v-if="can_update_is_used"
            id="project-service-edit-modal-enabled"
            v-bind:value="service.is_used"
            v-bind:disabled-reason="service.is_disabled_reason"
        />
        <hidden-service-is-active v-bind:value="service.is_active" />
        <read-only-service-rank v-if="is_summary_service" v-bind:value="service.rank" />
        <service-rank
            v-else
            id="project-service-edit-modal-rank"
            v-bind:minimal_rank="minimal_rank"
            v-bind:value="service.rank"
        />
        <service-link
            id="project-service-edit-modal-link"
            v-bind:value="service.link"
            v-bind:disabled="!service.is_link_customizable"
        />
        <div class="tlp-property">
            <label class="tlp-label" v-translate>Description</label>
            <span>{{ service.description }}</span>
            <input type="hidden" name="description" v-bind:value="service.description" />
        </div>
    </div>
</template>
<script>
import ServiceId from "./ServiceId.vue";
import ServiceLink from "./ServiceLink.vue";
import ServiceIsUsed from "./ServiceIsUsed.vue";
import ServiceRank from "./ServiceRank.vue";
import HiddenServiceShortname from "./HiddenServiceShortname.vue";
import HiddenServiceIsActive from "./HiddenServiceIsActive.vue";
import ReadOnlyServiceRank from "./ReadOnlyServiceRank.vue";
import ReadOnlyServiceIcon from "./ReadOnlyServiceIcon.vue";
import { system_service_mixin } from "./system-service-mixin.js";
import { service_mixin } from "./service-mixin.js";

export default {
    name: "ReadOnlySystemService",
    components: {
        HiddenServiceIsActive,
        HiddenServiceShortname,
        ServiceLink,
        ServiceId,
        ServiceIsUsed,
        ServiceRank,
        ReadOnlyServiceRank,
        ReadOnlyServiceIcon,
    },
    mixins: [service_mixin, system_service_mixin],
};
</script>
