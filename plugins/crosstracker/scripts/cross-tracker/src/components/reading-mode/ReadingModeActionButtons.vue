<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
    <div class="reading-mode-action-buttons">
        <div>
            <button
                v-if="is_user_admin"
                type="button"
                class="tlp-button-primary tlp-button-mini tlp-button-outline reading-mode-action-edit-button"
                v-on:click="emitter.emit(EDIT_QUERY_EVENT, { query_to_edit: current_query })"
                data-test="reading-mode-action-edit-button"
            >
                <i class="fa-solid fa-fw fa-edit" aria-hidden="true"></i> {{ $gettext("Edit") }}
            </button>
        </div>
        <delete-query-button v-if="is_user_admin" v-bind:current_query="current_query" />
        <div v-if="is_xlsx_export_allowed" class="export-button">
            <export-x-l-s-x-button v-bind:current_query="current_query" />
        </div>
    </div>
</template>

<script setup lang="ts">
import ExportXLSXButton from "../ExportXLSXButton.vue";
import { EMITTER, IS_EXPORT_ALLOWED, IS_USER_ADMIN } from "../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Query } from "../../type";
import { EDIT_QUERY_EVENT } from "../../helpers/emitter-provider";
import DeleteQueryButton from "./DeleteQueryButton.vue";

defineProps<{
    current_query: Query;
}>();

const is_xlsx_export_allowed = strictInject(IS_EXPORT_ALLOWED);
const is_user_admin = strictInject(IS_USER_ADMIN);
const emitter = strictInject(EMITTER);
</script>

<style scoped lang="scss">
.reading-mode-action-buttons {
    display: flex;
    gap: var(--tlp-medium-spacing);
}
</style>
