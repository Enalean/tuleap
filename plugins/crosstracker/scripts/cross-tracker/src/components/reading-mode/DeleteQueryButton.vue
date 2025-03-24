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
    <button
        type="button"
        class="tlp-button-danger tlp-button-mini tlp-button-outline"
        v-on:click="modal?.show()"
        data-test="delete-query-button"
    >
        <i
            aria-hidden="true"
            class="tlp-button-icon fa-solid fa-trash"
            data-test="export-xlsx-button-icon"
        ></i>
        {{ $gettext("Delete") }}
    </button>

    <div ref="modal_element" role="dialog" aria-labelledby="modal-label" class="tlp-modal">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-label">{{ $gettext("Delete the query") }}</h1>
        </div>
        <div class="tlp-modal-body">
            <template v-if="current_query.is_default">
                <p>
                    <strong>{{ $gettext("Warning: This query is set as the default.") }}</strong>
                </p>
                <p>
                    {{ $gettext("Are you sure you want to permanently delete it?") }} <br />
                    {{
                        $gettext(
                            "This action cannot be undone, and you will no longer have a default query.",
                        )
                    }}
                </p>
            </template>
            <template v-if="!current_query.is_default">
                <p>
                    {{ $gettext("Are you sure you want to permanently delete it?") }} <br />
                    {{ $gettext("This action cannot be undone.") }}
                </p>
            </template>
        </div>
        <div class="tlp-modal-footer">
            <button
                id="button-close"
                type="button"
                data-dismiss="modal"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-test="cancel-modal-button"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-danger tlp-modal-action"
                v-on:click="handleDeleteClick"
                data-test="delete-modal-button"
            >
                {{ $gettext("Delete") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import type { Query } from "../../type";
import { deleteQuery } from "../../api/rest-querier";
import { strictInject } from "@tuleap/vue-strict-inject";
import { EMITTER } from "../../injection-symbols";
import { NOTIFY_FAULT_EVENT, QUERY_DELETED_EVENT } from "../../helpers/widget-events";
import type { Fault } from "@tuleap/fault";
import { QueryDeletionFault } from "../../domain/QueryDeletionFault";
import { onMounted, onUnmounted, ref } from "vue";

const emitter = strictInject(EMITTER);

const props = defineProps<{
    current_query: Query;
}>();

const modal_element = ref<HTMLDivElement>();
const modal = ref<Modal | null>(null);

onMounted(() => {
    if (modal_element.value === undefined) {
        throw Error("Cannot find the modal html element");
    }
    modal.value = createModal(modal_element.value, {
        keyboard: false,
        dismiss_on_backdrop_click: true,
    });
});

onUnmounted(() => {
    modal.value?.destroy();
});

function handleDeleteClick(): void {
    deleteQuery(props.current_query).match(
        () => {
            modal.value?.hide();
            emitter.emit(QUERY_DELETED_EVENT, { deleted_query: props.current_query });
        },
        (fault: Fault) => {
            emitter.emit(NOTIFY_FAULT_EVENT, { fault: QueryDeletionFault(fault) });
        },
    );
}
</script>
