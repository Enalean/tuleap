<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <status-property
        v-bind:value="status_value"
        v-if="is_status_property_used"
        data-test="document-status-property-for-folder-update"
    />
</template>

<script setup lang="ts">
import StatusProperty from "../PropertiesForCreateOrUpdate/StatusProperty.vue";
import { IS_STATUS_PROPERTY_USED } from "../../../../configuration-keys";
import { strictInject } from "@tuleap/vue-strict-inject";

defineProps<{ status_value: string }>();

const is_status_property_used = strictInject(IS_STATUS_PROPERTY_USED);
</script>
