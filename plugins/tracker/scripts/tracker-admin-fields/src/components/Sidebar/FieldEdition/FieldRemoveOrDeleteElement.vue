<!--
  - Copyright (c) Enalean, 2026-present. All Rights Reserved.
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
        class="tlp-button-danger tlp-button-outline"
        v-bind:title="$gettext('Delete')"
        v-on:click="displayRemoveOrDeletionModal()"
        v-bind:disabled="!canCurrentFormElementBeRemoved(tracker_root.children, field.field_id)"
        data-test="remove-or-delete-field"
    >
        <i class="fa-regular fa-trash-alt" role="img" v-bind:aria-label="$gettext('Delete')" />
    </button>
    <Teleport to="body">
        <div
            v-bind:id="modal_id"
            role="dialog"
            aria-labelledby="remove-or-delete-modal-label"
            class="tlp-modal tlp-modal-danger"
            ref="modal_element"
        >
            <div class="tlp-modal-header">
                <h1 class="tlp-modal-title" id="remove-or-delete-modal-label">
                    {{ $gettext("You're about to remove a field") }}
                </h1>
                <button
                    class="tlp-modal-close"
                    type="button"
                    data-dismiss="modal"
                    v-bind:aria-label="$gettext('Close')"
                >
                    <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
                </button>
            </div>
            <div class="tlp-modal-body">
                <h2 class="tlp-modal-subtitle">
                    {{ modal_subtitle }}
                </h2>
                <p>
                    <strong>
                        {{ $gettext("Unuse:") }}
                    </strong>
                    {{
                        $gettext(
                            'Moves the field to the "Unused Fields" folder in the palette. The field is not deleted and can be reused later.',
                        )
                    }}
                </p>
                <p>
                    <strong>
                        {{ $gettext("Delete:") }}
                    </strong>
                    {{ $gettext("Permanently deletes this field. This action cannot be undone.") }}
                </p>
            </div>
            <div class="tlp-modal-footer">
                <button
                    type="button"
                    data-dismiss="modal"
                    class="tlp-button-primary tlp-button-outline tlp-modal-action"
                >
                    {{ $gettext("Cancel") }}
                </button>
                <button
                    type="button"
                    class="tlp-button-danger tlp-button-outline tlp-modal-action"
                    v-on:click="removeField()"
                >
                    {{ $gettext("Unuse") }}
                </button>
                <button type="button" disabled class="tlp-button-danger tlp-modal-action">
                    <i class="fa-regular fa-trash-alt tlp-button-icon" aria-hidden="true" />
                    {{ $gettext("Delete") }}
                </button>
            </div>
        </div>
    </Teleport>
</template>
<script setup lang="ts">
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";
import type { Ref } from "vue";
import { computed, onBeforeUnmount, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { patchJSON, uri } from "@tuleap/fetch-result";
import { HANDLE_REMOVE_FIELD, TRACKER_ROOT } from "../../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import { canCurrentFormElementBeRemoved } from "../../../helpers/can-current-form-element-be-removed";
import { useGettext } from "vue3-gettext";
import { useRouter } from "vue-router";

const props = defineProps<{
    field: StructureFields;
}>();

const handleRemoveField = strictInject(HANDLE_REMOVE_FIELD);
const tracker_root = strictInject(TRACKER_ROOT);

const modal_element: Ref<HTMLElement | null> = ref(null);
let modal: Modal | null = null;

const { interpolate, $gettext } = useGettext();
const router = useRouter();

const modal_id = computed((): string => `remove-or-delete-modal-${props.field.field_id}`);

const modal_subtitle = computed((): string =>
    interpolate($gettext('What do you want to do with the field "%{field_name}"?'), {
        field_name: props.field.label,
    }),
);

function displayRemoveOrDeletionModal(): void {
    if (modal_element.value === null) {
        return;
    }
    modal = createModal(modal_element.value);
    modal.show();
}

onBeforeUnmount(() => {
    modal?.destroy();
});

function removeField(): void {
    patchJSON(uri`/api/tracker_fields/${props.field.field_id}`, {
        use_it: false,
    }).match(
        () => {
            handleRemoveField(props.field);
            modal?.hide();
            router.push({ name: "fields-usage" });
        },
        (fault) => {
            throw Error(String(fault));
        },
    );
}
</script>
