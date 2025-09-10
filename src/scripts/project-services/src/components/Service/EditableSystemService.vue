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
        <service-id v-bind:id="service.id" />
        <hidden-service-shortname
            v-if="service.short_name"
            v-bind:short_name="service.short_name"
        />
        <service-label
            id="project-service-edit-modal-label"
            v-bind:label="service.label"
            v-on:input="onEditServiceLabel"
        />
        <read-only-service-icon v-bind:icon_name="service.icon_name" />
        <service-is-used
            v-if="can_update_is_used"
            id="project-service-edit-modal-enabled"
            v-bind:is_used="service.is_used"
            v-bind:is_disabled_reason="service.is_disabled_reason"
        />
        <service-is-active
            id="project-service-edit-modal-active"
            v-bind:is_active="service.is_active"
        />
        <div class="tlp-property" v-if="service.short_name">
            <label class="tlp-label">{{ $gettext("Short name") }}</label>
            <span>{{ service.short_name }}</span>
        </div>
        <read-only-service-rank v-if="is_summary_service" v-bind:rank="service.rank" />
        <service-rank v-else id="project-service-edit-modal-rank" v-bind:rank="service.rank" />
        <service-link
            id="project-service-edit-modal-link"
            v-bind:link="service.link"
            v-bind:disabled="false"
        />
        <service-description
            id="project-service-edit-modal-description"
            v-bind:description="service.description"
        />
    </div>
</template>
<script setup lang="ts">
import { ref, computed } from "vue";
import type { Service } from "../../type";
import { useGettext } from "vue3-gettext";
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
import { ADMIN_SERVICE_SHORTNAME, SUMMARY_SERVICE_SHORTNAME } from "../../constants";

const { $gettext } = useGettext();

const props = defineProps<{
    service_prop: Service;
}>();

const service = ref(props.service_prop);

const is_summary_service = computed(() => service.value.short_name === SUMMARY_SERVICE_SHORTNAME);
const can_update_is_used = computed(
    () => service.value.short_name !== ADMIN_SERVICE_SHORTNAME || !service.value.is_used,
);

function onEditServiceLabel(new_label: string): void {
    service.value.label = new_label;
}
</script>
