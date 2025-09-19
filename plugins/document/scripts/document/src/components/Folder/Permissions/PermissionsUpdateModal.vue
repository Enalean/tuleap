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
  -
  -->
<template>
    <form
        class="tlp-modal"
        role="dialog"
        aria-labelledby="document-update-permissions-modal"
        enctype="multipart/form-data"
        v-on:submit.prevent="updatePermissions"
        ref="form"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            aria-labelled-by="document-update-permissions-modal"
        />
        <modal-feedback />
        <div class="tlp-modal-body document-item-modal-body">
            <div v-if="project_ugroups === null" class="document-permissions-modal-loading-state">
                <i class="fa-solid fa-spin fa-circle-notch"></i>
            </div>
            <div
                v-else-if="item.permissions_for_groups"
                class="document-permissions-update-container"
            >
                <permissions-for-groups-selector
                    v-bind:project_ugroups="project_ugroups ? project_ugroups : []"
                    v-model="updated_permissions"
                    v-bind:value="updated_permissions"
                />
                <permissions-update-folder-sub-items
                    v-bind:item="item"
                    v-bind:value="updated_permissions.apply_permissions_on_children"
                />
            </div>
        </div>
        <modal-footer
            v-bind:is-loading="!can_be_submitted"
            v-bind:submit-button-label="$gettext('Update permissions')"
            aria-labelled-by="document-update-permissions-modal"
            v-bind:icon-submit-button-class="'fa-solid fa-pencil'"
            data-test="document-modal-submit-update-permissions"
        />
    </form>
</template>
<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { sprintf } from "sprintf-js";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import PermissionsForGroupsSelector from "./PermissionsForGroupsSelector.vue";
import { handleErrors } from "../../../store/actions-helpers/handle-errors";
import PermissionsUpdateFolderSubItems from "./PermissionsUpdateFolderSubItems.vue";
import type {
    UpdateApplyPermissionsOnChildren,
    UpdatePermissionsEvent,
} from "../../../helpers/emitter";
import emitter from "../../../helpers/emitter";
import { CAN_MANAGE, CAN_READ, CAN_WRITE } from "../../../constants";
import type { Item } from "../../../type";
import { computed, onBeforeMount, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useNamespacedState, useStore } from "vuex-composition-helpers";
import type { ErrorState } from "../../../store/error/module";
import type { PermissionsState } from "../../../store/permissions/permissions-default-state";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT } from "../../../configuration-keys";

const { $gettext } = useGettext();
const $store = useStore();

const props = defineProps<{
    item: Item;
}>();

const is_submitting_new_permissions = ref(false);
const updated_permissions = ref({
    apply_permissions_on_children: false,
    can_read: [],
    can_write: [],
    can_manage: [],
});
const form = ref<HTMLFormElement>();
let modal: Modal | null = null;

const project = strictInject(PROJECT);
const { has_modal_error } = useNamespacedState<Pick<ErrorState, "has_modal_error">>("error", [
    "has_modal_error",
]);
const { project_ugroups } = useNamespacedState<Pick<PermissionsState, "project_ugroups">>(
    "permissions",
    ["project_ugroups"],
);

const modal_title = computed(() => sprintf($gettext('Edit "%s" permissions'), props.item.title));
const can_be_submitted = computed(
    () => project_ugroups.value !== null && is_submitting_new_permissions.value === false,
);

watch(() => props.item, setPermissionsToUpdateFromItem);

onBeforeMount(() => {
    setPermissionsToUpdateFromItem();
});

onMounted(() => {
    modal = createModal(form.value);
    emitter.on("show-update-permissions-modal", show);
    emitter.on("update-permissions", updateUGroup);
    emitter.on("update-apply-permissions-on-children", setApplyPermissionsOnChildren);
    modal.addEventListener("tlp-modal-hidden", reset);
    show();
});

onBeforeUnmount(() => {
    emitter.off("show-update-permissions-modal", show);
    emitter.off("update-permissions", updateUGroup);
    modal?.removeEventListener("tlp-modal-hidden", reset);
});

function setPermissionsToUpdateFromItem(): void {
    if (!props.item.permissions_for_groups) {
        return;
    }
    updated_permissions.value = {
        apply_permissions_on_children: false,
        can_read: JSON.parse(JSON.stringify(props.item.permissions_for_groups.can_read)),
        can_write: JSON.parse(JSON.stringify(props.item.permissions_for_groups.can_write)),
        can_manage: JSON.parse(JSON.stringify(props.item.permissions_for_groups.can_manage)),
    };
}

async function show(): Promise<void> {
    modal?.show();
    try {
        await $store.dispatch("permissions/loadProjectUserGroupsIfNeeded", project.id);
    } catch (err) {
        await handleErrors($store, err);
        modal?.hide();
    }
}

function reset(): void {
    setPermissionsToUpdateFromItem();
    $store.commit("error/resetModalError");
}

async function updatePermissions(): Promise<void> {
    is_submitting_new_permissions.value = true;
    $store.commit("error/resetModalError");
    await $store.dispatch("permissions/updatePermissions", {
        item: props.item,
        updated_permissions: updated_permissions.value,
    });
    is_submitting_new_permissions.value = false;
    if (!has_modal_error.value) {
        modal?.hide();
    }
}

function updateUGroup(event: UpdatePermissionsEvent): void {
    switch (event.label) {
        case CAN_READ:
            updated_permissions.value.can_read = event.value;
            break;
        case CAN_WRITE:
            updated_permissions.value.can_write = event.value;
            break;
        case CAN_MANAGE:
            updated_permissions.value.can_manage = event.value;
            break;
        default:
    }
}

function setApplyPermissionsOnChildren(event: UpdateApplyPermissionsOnChildren): void {
    updated_permissions.value = {
        ...updated_permissions.value,
        apply_permissions_on_children: event.do_permissions_apply_on_children,
    };
}
</script>
