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
        aria-labelledby="document-new-item-version-modal"
        v-on:submit="createNewWikiVersion"
        data-test="document-new-item-version-modal"
        ref="form"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            aria-labelled-by="document-new-item-version-modal"
        />
        <modal-feedback />
        <div class="tlp-modal-body">
            <div class="docman-item-update-property">
                <wiki-properties
                    v-model="wiki_model.wiki_properties"
                    v-if="wiki_model.type === TYPE_WIKI"
                />
                <lock-property v-bind:item="wiki_item" v-if="wiki_item !== null" />
            </div>
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Create new version')"
            aria-labelled-by="document-new-item-version-modal"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-wiki-version"
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
import WikiProperties from "../PropertiesForCreateOrUpdate/WikiProperties.vue";
import LockProperty from "../Lock/LockProperty.vue";
import emitter from "../../../../helpers/emitter";
import { TYPE_WIKI } from "../../../../constants";
import type { Wiki } from "../../../../type";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useNamespacedState, useStore } from "vuex-composition-helpers";
import type { ErrorState } from "../../../../store/error/module";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const $store = useStore();

const props = defineProps<{
    item: Wiki;
}>();

const wiki_model = ref({});
const version = ref({});
const is_loading = ref<boolean>(false);
const is_displayed = ref<boolean>(false);
const wiki_item = ref<Wiki | null>(null);
const approval_table_action = ref<string>("");
const form = ref<HTMLFormElement>();
let modal: Modal | null = null;

const { has_modal_error } = useNamespacedState<Pick<ErrorState, "has_modal_error">>("error", [
    "has_modal_error",
]);

const modal_title = computed(() => sprintf($gettext('New version for "%s"'), props.item.title));

onMounted(() => {
    modal = createModal(form.value);
    wiki_item.value = props.item;
    registerEvents();

    show();
});

onBeforeUnmount(() => {
    emitter.off("update-lock", updateLock);
});

function registerEvents(): void {
    modal?.addEventListener("tlp-modal-hidden", reset);
    emitter.on("update-lock", updateLock);
}

function show(): void {
    version.value = {
        title: "",
        changelog: "",
        is_file_locked: wiki_item.value !== null && wiki_item.value.lock_info !== null,
    };
    wiki_model.value = {
        type: wiki_item.value.type,
        wiki_properties: wiki_item.value.wiki_properties,
    };
    is_displayed.value = true;
    modal?.show();
}

function reset(): void {
    $store.commit("error/resetModalError");
    is_displayed.value = false;
    is_loading.value = false;
    wiki_model.value = {};
}

async function createNewWikiVersion(event: SubmitEvent): Promise<void> {
    event.preventDefault();
    is_loading.value = true;
    $store.commit("error/resetModalError");

    await $store.dispatch("createNewWikiVersionFromModal", [
        wiki_item.value,
        wiki_model.value.wiki_properties.page_name,
        version.value.title,
        version.value.changelog,
        version.value.is_file_locked,
        approval_table_action.value,
    ]);
    is_loading.value = false;
    if (!has_modal_error.value) {
        wiki_item.value.wiki_properties.page_name = wiki_model.value.wiki_properties.page_name;
        await $store.dispatch("refreshWiki", wiki_item.value);
        wiki_model.value = {};
        modal?.hide();
    }
}

function updateLock(is_locked: boolean): void {
    version.value.is_file_locked = is_locked;
}
</script>
