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
        v-on:submit="createNewLinkVersion"
        data-test="document-new-item-version-modal"
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
                v-bind:item="item"
                v-on:approval-table-action-change="setApprovalUpdateAction"
                v-bind:is-open-after-dnd="false"
            >
                <link-properties
                    v-if="link_model && item.type === TYPE_LINK"
                    v-bind:value="item.link_properties.link_url"
                    key="link-props"
                />
            </item-update-properties>
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Create new version')"
            aria-labelled-by="document-new-item-version-modal"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-link-version"
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
import ItemUpdateProperties from "./PropertiesForUpdate/ItemUpdateProperties.vue";
import LinkProperties from "../PropertiesForCreateOrUpdate/LinkProperties.vue";
import emitter from "../../../../helpers/emitter";
import { TYPE_LINK } from "../../../../constants";
import type { Link } from "../../../../type";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useNamespacedState, useStore } from "vuex-composition-helpers";
import type { ErrorState } from "../../../../store/error/module";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const $store = useStore();

const props = defineProps<{
    item: Link;
}>();

const link_model = ref<LinkProperties | null>(null);
const version = ref({});
const is_loading = ref<boolean>(false);
const is_displayed = ref<boolean>(false);
const approval_table_action = ref<string>();
const form = ref<HTMLFormElement>();
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
    emitter.on("update-link-properties", updateLinkProperties);
});

onBeforeUnmount(() => {
    emitter.off("update-version-title", updateTitleValue);
    emitter.off("update-changelog-property", updateChangelogValue);
    emitter.off("update-lock", updateLock);
    emitter.off("update-link-properties", updateLinkProperties);
});

function setApprovalUpdateAction(value: string): void {
    approval_table_action.value = value;
}

function registerEvents(): void {
    modal?.addEventListener("tlp-modal-hidden", reset);

    show();
}

function show(): void {
    version.value = {
        title: "",
        changelog: "",
        is_file_locked: props.item.lock_info !== null,
    };

    link_model.value = props.item.link_properties;

    is_displayed.value = true;
    modal?.show();
}

function reset(): void {
    $store.commit("error/resetModalError");
    is_displayed.value = false;
    is_loading.value = false;
    link_model.value = null;
}

async function createNewLinkVersion(event: SubmitEvent): Promise<void> {
    event.preventDefault();
    is_loading.value = true;
    $store.commit("error/resetModalError");

    await $store.dispatch("createNewLinkVersionFromModal", [
        props.item,
        link_model.value.link_url,
        version.value.title,
        version.value.changelog,
        version.value.is_file_locked,
        approval_table_action.value,
    ]);

    is_loading.value = false;
    if (!has_modal_error.value) {
        await $store.dispatch("refreshLink", props.item);
        link_model.value = null;
        modal?.hide();
    }
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

function updateLinkProperties(url: string): void {
    if (!props.item) {
        return;
    }
    link_model.value.link_url = url;
}
</script>
