<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <form
        class="tlp-modal"
        role="dialog"
        aria-labelled-by="document-file-changelog-modal"
        v-on:submit.prevent="uploadNewVersion"
        ref="form"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            aria-labelled-by="document-file-changelog-modal"
        />
        <modal-feedback />
        <div class="tlp-modal-body">
            <item-update-properties
                v-bind:version="version"
                v-bind:item="updated_file"
                v-bind:is-open-after-dnd="true"
                v-on:approval-table-action-change="setApprovalUpdateAction"
            />
            <file-version-history v-bind:item="updated_file" />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Create new version')"
            aria-labelled-by="document-file-changelog-modal"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-version-changelog"
        />
    </form>
</template>

<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import ItemUpdateProperties from "./PropertiesForUpdate/ItemUpdateProperties.vue";
import { sprintf } from "sprintf-js";
import emitter from "../../../../helpers/emitter";
import FileVersionHistory from "./History/FileVersionHistory.vue";
import type { ItemFile, NewVersion } from "../../../../type";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useNamespacedState, useStore } from "vuex-composition-helpers";
import type { ErrorState } from "../../../../store/error/module";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const $store = useStore();

const props = defineProps<{
    updated_file: ItemFile;
    dropped_file: File;
}>();

const emit = defineEmits<{
    (e: "close-changelog-modal"): void;
}>();

let modal: Modal | null = null;
const is_loading = ref<boolean>(false);
const version = ref<NewVersion>({ title: "", changelog: "" });
const approval_table_action = ref<string | null>(null);
const form = ref<HTMLFormElement>();

const { has_modal_error } = useNamespacedState<Pick<ErrorState, "has_modal_error">>("error", [
    "has_modal_error",
]);

const modal_title = computed(() =>
    sprintf($gettext('New version for "%s"'), props.updated_file.title),
);

onMounted(() => {
    modal = createModal(form.value, { destroy_on_hide: true });
    modal.addEventListener("tlp-modal-hidden", close);
    modal.show();
    emitter.on("update-version-title", updateTitleValue);
    emitter.on("update-changelog-property", updateChangelogValue);
    emitter.on("update-lock", updateLock);
});

onBeforeUnmount(() => {
    emitter.off("update-version-title", updateTitleValue);
    emitter.off("update-changelog-property", updateChangelogValue);
    emitter.off("update-lock", updateLock);
});

function setApprovalUpdateAction(value: string): void {
    approval_table_action.value = value;
}

async function uploadNewVersion(): Promise<void> {
    is_loading.value = true;
    $store.commit("error/resetModalError");

    await $store.dispatch("createNewFileVersionFromModal", [
        props.updated_file,
        props.dropped_file,
        version.value.title,
        version.value.changelog,
        false,
        approval_table_action.value,
    ]);
    is_loading.value = false;
    if (!has_modal_error.value) {
        close();
    }
}

function close(): void {
    modal?.removeBackdrop();
    emit("close-changelog-modal");
}

function updateTitleValue(title: string): void {
    version.value.title = title;
}

function updateChangelogValue(changelog: string): void {
    version.value.changelog = changelog;
}

function updateLock(is_locked: boolean): void {
    version.value.is_file_locked = is_locked;
}
</script>
