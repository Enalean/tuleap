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
    <form
        class="tlp-modal"
        role="dialog"
        aria-labelled-by="document-new-item-version-modal"
        v-on:submit="createNewEmbeddedFileVersion"
        ref="form"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            aria-labelled-by="document-new-item-version-modal"
        />
        <modal-feedback />
        <div class="tlp-modal-body">
            <item-update-properties
                v-bind:version="version"
                v-bind:item="embedded_item"
                v-bind:is-open-after-dnd="false"
                v-on:approval-table-action-change="setApprovalUpdateAction"
            >
                <embedded-properties
                    v-if="embedded_file_model && embedded_item.type === TYPE_EMBEDDED"
                    v-bind:value="embedded_file_model.content"
                    key="embedded-props"
                />
            </item-update-properties>
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Create new version')"
            aria-labelled-by="document-new-item-version-modal"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-embedded-version"
        />
    </form>
</template>

<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { sprintf } from "sprintf-js";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import EmbeddedProperties from "../PropertiesForCreateOrUpdate/EmbeddedProperties.vue";
import ItemUpdateProperties from "./PropertiesForUpdate/ItemUpdateProperties.vue";
import emitter from "../../../../helpers/emitter";
import { computed, onBeforeUnmount, onMounted, ref, toRaw } from "vue";
import { TYPE_EMBEDDED } from "../../../../constants";
import type {
    EmbeddedProperties as EmbeddedPropertiesType,
    Embedded,
    NewVersion,
} from "../../../../type";
import { useGettext } from "vue3-gettext";
import { useNamespacedState, useStore } from "vuex-composition-helpers";
import type { ErrorState } from "../../../../store/error/module";

const { $gettext } = useGettext();
const $store = useStore();

const props = defineProps<{
    item: Embedded;
}>();

const emit = defineEmits<{
    (e: "hidden"): void;
}>();

const embedded_file_model = ref<EmbeddedPropertiesType | null>(null);
const version = ref<NewVersion>({ changelog: "", title: "" });
const is_loading = ref<boolean>(false);
const embedded_item = ref<Embedded>(structuredClone(toRaw(props.item)));
const form = ref<HTMLFormElement>();
const approval_table_action = ref<string | null>(null);
let modal: Modal | null = null;

const { has_modal_error } = useNamespacedState<Pick<ErrorState, "has_modal_error">>("error", [
    "has_modal_error",
]);

const modal_title = computed(() => sprintf($gettext('New version for "%s"'), props.item.title));

onMounted(() => {
    modal = createModal(form.value);
    registerEvents();
    emitter.on("update-version-title", updateTitleValue);
    emitter.on("update-changelog-property", updateChangelogValue);
    emitter.on("update-lock", updateLock);
    emitter.on("update-embedded-properties", updateContent);
});

onBeforeUnmount(() => {
    emitter.off("update-version-title", updateTitleValue);
    emitter.off("update-changelog-property", updateChangelogValue);
    emitter.off("update-lock", updateLock);
    emitter.off("update-embedded-properties", updateContent);
});

function setApprovalUpdateAction(value: string): void {
    approval_table_action.value = value;
}

function registerEvents(): void {
    modal?.addEventListener("tlp-modal-hidden", reset);

    show();
}

async function show(): Promise<void> {
    version.value = {
        title: "",
        changelog: "",
        is_file_locked: props.item.lock_info !== null,
    };

    if (embedded_item.value.embedded_file_properties?.content === undefined) {
        embedded_item.value = await $store.dispatch("loadDocument", embedded_item.value.id);
    }

    embedded_file_model.value = embedded_item.value.embedded_file_properties;

    modal?.show();
}

function reset(): void {
    $store.commit("error/resetModalError");
    is_loading.value = false;
    embedded_file_model.value = null;
    hide();
}

async function createNewEmbeddedFileVersion(event: SubmitEvent): Promise<void> {
    event.preventDefault();
    is_loading.value = true;
    $store.commit("error/resetModalError");

    await $store.dispatch("createNewEmbeddedFileVersionFromModal", [
        embedded_item.value,
        embedded_file_model.value?.content ?? "",
        version.value.title,
        version.value.changelog,
        version.value.is_file_locked,
        approval_table_action.value,
    ]);

    is_loading.value = false;
    if (!has_modal_error.value) {
        embedded_item.value = await $store.dispatch("refreshEmbeddedFile", embedded_item.value);
        embedded_file_model.value = null;
        hide();
        modal?.hide();
    }
}

function hide(): void {
    emit("hidden");
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

function updateContent(content: string): void {
    if (embedded_file_model.value) {
        embedded_file_model.value.content = content;
    }
}
</script>
