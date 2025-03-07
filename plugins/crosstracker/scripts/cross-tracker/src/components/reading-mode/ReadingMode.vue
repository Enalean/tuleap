<!--
  - Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
    <div
        class="report"
        v-bind:class="{ disabled: !is_user_admin }"
        v-on:click="switchToWritingMode"
        data-test="cross-tracker-reading-mode"
    >
        <tlp-syntax-highlighting v-if="!isExpertQueryEmpty()" data-test="tql-reading-mode-query">
            <code class="language-tql cross-tracker-reading-mode-query">{{
                props.reading_query.tql_query
            }}</code>
        </tlp-syntax-highlighting>
    </div>
    <div class="actions" v-if="report_state === 'result-preview'">
        <button
            type="button"
            class="tlp-button-primary tlp-button-outline"
            v-on:click="cancelReport()"
            data-test="cross-tracker-cancel-report"
        >
            {{ $gettext("Cancel") }}
        </button>
        <button
            type="button"
            class="tlp-button-primary"
            v-on:click="saveReport()"
            v-bind:disabled="is_save_disabled"
            data-test="cross-tracker-save-report"
        >
            <i
                aria-hidden="true"
                class="tlp-button-icon fa-solid"
                v-bind:class="{
                    'fa-circle-notch fa-spin': is_loading,
                    'fa-save': !is_loading,
                }"
            ></i>
            {{ $gettext("Save report") }}
        </button>
    </div>
</template>
<script setup lang="ts">
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { updateQuery, createQuery } from "../../api/rest-querier";
import type { Query } from "../../type";
import { EMITTER, IS_USER_ADMIN, REPORT_STATE, WIDGET_ID } from "../../injection-symbols";
import { SaveReportFault } from "../../domain/SaveReportFault";
import { NOTIFY_FAULT_EVENT, REFRESH_ARTIFACTS_EVENT } from "../../helpers/emitter-provider";

const { $gettext } = useGettext();
const report_state = strictInject(REPORT_STATE);
const widget_id = strictInject(WIDGET_ID);
const is_user_admin = strictInject(IS_USER_ADMIN);

const props = defineProps<{
    has_error: boolean;
    reading_query: Query;
    backend_query: Query;
}>();

const emit = defineEmits<{
    (e: "switch-to-writing-mode"): void;
    (e: "saved", query: Query): void;
    (e: "discard-unsaved-report"): void;
}>();
const emitter = strictInject(EMITTER);

const is_loading = ref(false);

const is_save_disabled = computed(() => is_loading.value === true || props.has_error);

function isExpertQueryEmpty(): boolean {
    return props.reading_query.tql_query === "";
}

function switchToWritingMode(): void {
    if (!is_user_admin) {
        return;
    }
    emit("switch-to-writing-mode");
}

function saveReport(): void {
    if (is_save_disabled.value) {
        return;
    }
    is_loading.value = true;

    if (props.reading_query.id === "") {
        // It is a new query
        createQuery(props.reading_query, widget_id)
            .match(
                (query: Query) => {
                    emit("saved", query);
                },
                (fault) => {
                    emitter.emit(NOTIFY_FAULT_EVENT, {
                        fault: SaveReportFault(fault),
                        tql_query: props.reading_query.tql_query,
                    });
                },
            )
            .then(() => {
                is_loading.value = false;
            });
        return;
    }

    updateQuery(props.reading_query, widget_id)
        .match(
            (query: Query) => {
                emit("saved", query);
            },
            (fault) => {
                emitter.emit(NOTIFY_FAULT_EVENT, {
                    fault: SaveReportFault(fault),
                    tql_query: props.reading_query.tql_query,
                });
            },
        )
        .then(() => {
            is_loading.value = false;
        });
}

function cancelReport(): void {
    emit("discard-unsaved-report");
    emitter.emit(REFRESH_ARTIFACTS_EVENT, { query: props.backend_query });
}
</script>

<style scoped lang="scss">
.report {
    display: flex;
    flex-direction: column;
    margin: calc(-1 * var(--tlp-small-spacing));
    padding: var(--tlp-small-spacing);
    border-radius: var(--tlp-small-radius);
    color: var(--tlp-main-color);
    font-size: 0.9375rem;
    gap: var(--tlp-medium-spacing);

    &:not(.disabled) {
        cursor: pointer;
    }

    &:hover:not(.disabled) {
        background-color: var(--tlp-main-color-transparent-80);
    }
}

.actions {
    display: flex;
    justify-content: center;
    gap: var(--tlp-medium-spacing);
    margin: var(--tlp-medium-spacing) 0 0 0;
    padding-bottom: var(--tlp-medium-spacing);
    border-bottom: 1px solid var(--tlp-neutral-light-color);
}

.cross-tracker-reading-mode-query {
    padding: 3px 0;
    background: transparent;
}
</style>
