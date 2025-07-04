<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
        aria-labelled-by="document-new-empty-version-modal"
        v-on:submit="createNewVersion"
        ref="form"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            aria-labelled-by="document-new-empty-version-modal"
        />
        <modal-feedback />

        <div class="tlp-modal-body">
            <link-properties
                v-if="new_item_version_type === TYPE_LINK"
                v-bind:value="new_item_version.link_properties.link_url"
            />
            <embedded-properties
                v-if="new_item_version_type === TYPE_EMBEDDED"
                v-bind:value="new_item_version.embedded_properties.content"
            />
            <file-properties
                v-if="new_item_version_type === TYPE_FILE"
                v-bind:value="new_item_version.file_properties"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Create new version')"
            aria-labelled-by="document-new-empty-version-modal"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-empty"
        />
    </form>
</template>

<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { sprintf } from "sprintf-js";
import { TYPE_EMBEDDED, TYPE_FILE, TYPE_LINK } from "../../../../constants";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import EmbeddedProperties from "../PropertiesForCreateOrUpdate/EmbeddedProperties.vue";
import LinkProperties from "../PropertiesForCreateOrUpdate/LinkProperties.vue";
import FileProperties from "../PropertiesForCreateOrUpdate/FileProperties.vue";
import emitter from "../../../../helpers/emitter";
import type { Item, ItemType } from "../../../../type";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useNamespacedState, useStore } from "vuex-composition-helpers";
import type { ErrorState } from "../../../../store/error/module";
import { useGettext } from "vue3-gettext";
import type { NewVersionFromEmptyInformation } from "../../../../store/actions-update";

const { $gettext } = useGettext();
const $store = useStore();

const props = defineProps<{
    item: Item;
    type: ItemType | null;
}>();

const emit = defineEmits<{
    (e: "hidden"): void;
}>();

const is_loading = ref<boolean>(false);
const new_item_version_type = ref<ItemType>(props.type || TYPE_FILE);
const new_item_version = ref<NewVersionFromEmptyInformation>({
    link_properties: { link_url: "" },
    file_properties: { file: "" },
    embedded_properties: { content: "" },
});
const form = ref<HTMLFormElement>();

let modal: Modal | null = null;

const { has_modal_error } = useNamespacedState<Pick<ErrorState, "has_modal_error">>("error", [
    "has_modal_error",
]);

const modal_title = computed(() => sprintf($gettext('New version for "%s"'), props.item.title));

onMounted(() => {
    if (!(form.value instanceof HTMLFormElement)) {
        throw new Error("form element not found");
    }
    modal = createModal(form.value);
    modal.addEventListener("tlp-modal-hidden", reset);
    modal.show();
    emitter.on("update-link-properties", updateLinkProperties);
    emitter.on("update-wiki-properties", updateWikiProperties);
    emitter.on("update-embedded-properties", updateEmbeddedContent);
    emitter.on("update-file-properties", updateFilesProperties);
});

onBeforeUnmount(() => {
    emitter.off("update-link-properties", updateLinkProperties);
    emitter.off("update-wiki-properties", updateWikiProperties);
    emitter.off("update-embedded-properties", updateEmbeddedContent);
    emitter.off("update-file-properties", updateFilesProperties);
});

function reset(): void {
    $store.commit("error/resetModalError");
    is_loading.value = false;
    hide();
}

function hide(): void {
    emit("hidden");
}

async function createNewVersion(event: SubmitEvent): Promise<void> {
    event.preventDefault();
    is_loading.value = true;
    $store.commit("error/resetModalError");

    await $store.dispatch("createNewVersionFromEmpty", [
        new_item_version_type.value,
        props.item,
        new_item_version.value,
    ]);

    is_loading.value = false;
    if (!has_modal_error.value) {
        modal?.hide();
    }
}

function updateLinkProperties(url: string): void {
    new_item_version.value.link_properties.link_url = url;
}

function updateWikiProperties(page_name): void {
    new_item_version.value.wiki_properties.page_name = page_name;
}

function updateEmbeddedContent(content: string): void {
    new_item_version.value.embedded_properties.content = content;
}

function updateFilesProperties(file_properties: { file: File }): void {
    new_item_version.value.file_properties = file_properties;
}
</script>
