<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <choose-query-button v-bind:backend_query="backend_query" v-bind:queries="queries" />
    <query-details-button v-bind:are_query_details_toggled="are_query_details_toggled" />
    <export-x-l-s-x-button v-if="is_xlsx_export_allowed" v-bind:current_query="backend_query" />
</template>

<script setup lang="ts">
import ChooseQueryButton from "./ChooseQueryButton.vue";
import type { Query } from "../../type";
import QueryDetailsButton from "./QueryDetailsButton.vue";
import ExportXLSXButton from "./ExportXLSXButton.vue";
import { IS_EXPORT_ALLOWED } from "../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";

const is_xlsx_export_allowed = strictInject(IS_EXPORT_ALLOWED);
defineProps<{
    backend_query: Query;
    queries: ReadonlyArray<Query>;
    are_query_details_toggled: boolean;
}>();
</script>
