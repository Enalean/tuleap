<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <error-message v-bind:fault="current_fault" v-bind:query="query" />
    <div
        class="tlp-alert-success cross-tracker-report-success"
        v-if="current_success.isValue()"
        data-test="cross-tracker-report-success"
    >
        {{ current_success.unwrapOr("") }}
    </div>
</template>

<script setup lang="ts">
import ErrorMessage from "./ErrorMessage.vue";
import type { Query } from "../../type";
import type { Option } from "@tuleap/option";
import type { Fault } from "@tuleap/fault";

defineProps<{
    query: Query;
    current_fault: Option<Fault>;
    current_success: Option<string>;
}>();
</script>
