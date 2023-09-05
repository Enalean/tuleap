<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
        method="post"
        v-bind:action="server.update_url"
        class="tlp-modal"
        role="dialog"
        v-bind:aria-labelledby="'onlyoffice-admin-edit-server-title-' + server.id"
        ref="root"
    >
        <div class="tlp-modal-header">
            <h1
                class="tlp-modal-title"
                v-bind:id="'onlyoffice-admin-edit-server-title-' + server.id"
            >
                {{ $gettext("Edit document server settings") }}
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
            <div class="tlp-form-element">
                <label class="tlp-label" for="server-url">
                    {{ $gettext("Document server URL") }}
                    <i class="fa-solid fa-asterisk" aria-hidden="true"></i
                ></label>
                <input
                    type="url"
                    autocomplete="url"
                    pattern="https://.*"
                    class="tlp-input"
                    id="server-url"
                    name="server_url"
                    placeholder="https://…"
                    required
                    v-bind:value="server.server_url"
                />
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="server-key">
                    {{ $gettext("JWT secret") }}
                    <i class="fa-solid fa-asterisk" aria-hidden="true"></i
                ></label>
                <input
                    type="password"
                    class="tlp-input"
                    id="server-key"
                    name="server_key"
                    minlength="32"
                    required
                    v-bind:placeholder="placeholder"
                />
            </div>
            <csrf-token />
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button type="submit" class="tlp-button-primary tlp-modal-action">
                {{ $gettext("Save") }}
            </button>
        </div>
    </form>
</template>
<script setup lang="ts">
import type { Server } from "../../type";
import { useGettext } from "vue3-gettext";
import CsrfToken from "../CsrfToken.vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { computed, onMounted, onUnmounted, ref } from "vue";
import emitter from "../../helpers/emitter";

const props = defineProps<{ server: Server }>();

const { $gettext } = useGettext();

const placeholder = computed((): string =>
    props.server.has_existing_secret ? $gettext("Current secret not displayed") : "",
);

let modal: Modal | null = null;
const root = ref<HTMLElement | null>(null);

function showModal(server: Server): void {
    if (server.id !== props.server.id) {
        return;
    }

    if (!root.value) {
        return;
    }

    if (!modal) {
        modal = createModal(root.value);
    }

    modal.show();
}

onMounted(() => {
    emitter.on("show-edit-server-modal", showModal);
});
onUnmounted(() => {
    emitter.off("show-edit-server-modal", showModal);
});
</script>
