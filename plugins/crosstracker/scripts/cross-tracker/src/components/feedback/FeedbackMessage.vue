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
    <error-message v-bind:fault="current_fault" v-bind:tql_query="tql_query" />
    <div
        class="tlp-alert-success cross-tracker-query-success"
        v-if="current_success.isValue()"
        data-test="cross-tracker-query-success"
    >
        {{ current_success.unwrapOr("") }}
    </div>
</template>

<script setup lang="ts">
import ErrorMessage from "./ErrorMessage.vue";
import { Option } from "@tuleap/option";
import type { Fault } from "@tuleap/fault";
import { onMounted, onUnmounted, ref } from "vue";
import type { NotifyFaultEvent, NotifySuccessEvent } from "../../helpers/widget-events";
import {
    SEARCH_ARTIFACTS_SUCCESS_EVENT,
    SWITCH_QUERY_EVENT,
    CREATE_NEW_QUERY_EVENT,
    EDIT_QUERY_EVENT,
    STARTING_XLSX_EXPORT_EVENT,
    NOTIFY_FAULT_EVENT,
    NOTIFY_SUCCESS_EVENT,
} from "../../helpers/widget-events";
import { strictInject } from "@tuleap/vue-strict-inject";
import { EMITTER } from "../../injection-symbols";

const emitter = strictInject(EMITTER);

const tql_query = ref<string>("");
const current_fault = ref<Option<Fault>>(Option.nothing());
const current_success = ref<Option<string>>(Option.nothing());

onMounted(() => {
    emitter.on(NOTIFY_FAULT_EVENT, handleFault);
    emitter.on(NOTIFY_SUCCESS_EVENT, handleSuccess);
    emitter.on(CREATE_NEW_QUERY_EVENT, resetFeedbackComponent);
    emitter.on(EDIT_QUERY_EVENT, resetFeedbackComponent);
    emitter.on(STARTING_XLSX_EXPORT_EVENT, resetFeedbackComponent);
    emitter.on(SWITCH_QUERY_EVENT, resetFeedbackComponent);
    emitter.on(SEARCH_ARTIFACTS_SUCCESS_EVENT, clearFault);
});

onUnmounted(() => {
    emitter.off(NOTIFY_FAULT_EVENT, handleFault);
    emitter.off(NOTIFY_SUCCESS_EVENT, handleSuccess);
    emitter.off(CREATE_NEW_QUERY_EVENT, resetFeedbackComponent);
    emitter.off(EDIT_QUERY_EVENT, resetFeedbackComponent);
    emitter.off(STARTING_XLSX_EXPORT_EVENT, resetFeedbackComponent);
    emitter.off(SWITCH_QUERY_EVENT, resetFeedbackComponent);
    emitter.off(SEARCH_ARTIFACTS_SUCCESS_EVENT, clearFault);
});

function handleFault(event: NotifyFaultEvent): void {
    current_fault.value = Option.fromValue(event.fault);
    if (event.tql_query !== undefined) {
        tql_query.value = event.tql_query;
    }
}

function handleSuccess(event: NotifySuccessEvent): void {
    current_success.value = Option.fromValue(event.message);
}

function clearFault(): void {
    current_fault.value = Option.nothing();
}

function clearSuccess(): void {
    current_success.value = Option.nothing();
}

function resetFeedbackComponent(): void {
    clearFault();
    clearSuccess();
    tql_query.value = "";
}
</script>

<style lang="scss" scoped>
.cross-tracker-query-success {
    margin: var(--tlp-medium-spacing) var(--tlp-medium-spacing) 0;
}
</style>
