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
        v-bind:action="config.create_url"
        class="tlp-modal"
        role="dialog"
        aria-labelledby="onlyoffice-admin-add-server-title"
        ref="root"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="onlyoffice-admin-add-server-title">
                {{ $gettext("Add document server") }}
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
                    <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
                </label>
                <input
                    type="url"
                    autocomplete="url"
                    pattern="https://.*"
                    class="tlp-input"
                    id="server-url"
                    name="server_url"
                    placeholder="https://…"
                    required
                />
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="server-key">
                    {{ $gettext("JWT secret") }}
                    <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
                </label>
                <input
                    type="password"
                    class="tlp-input"
                    id="server-key"
                    name="server_key"
                    minlength="32"
                    required
                />
            </div>
            <csrf-token />
        </div>
        <div class="tlp-modal-footer">
            <div
                class="tlp-alert-warning"
                data-test="warning"
                v-if="config.servers.length === 1 && !config.servers[0].is_project_restricted"
            >
                {{ $gettext("Adding a second server will restrict the existing one.") }}
                {{
                    $gettext(
                        "Users won't have access anymore and will lose unsaved modifications until their projects are explicitelly allowed.",
                    )
                }}
            </div>
            <div class="onlyoffice-admin-add-server-modal-footer">
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
        </div>
    </form>
</template>

<script setup lang="ts">
import emitter from "../../helpers/emitter";
import { onMounted, onUnmounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { CONFIG } from "../../injection-keys";
import { strictInject } from "@tuleap/vue-strict-inject";
import CsrfToken from "../CsrfToken.vue";

const config = strictInject(CONFIG);

let modal: Modal | null = null;
const root = ref<HTMLElement | null>(null);

function showModal(): void {
    if (!root.value) {
        return;
    }

    if (!modal) {
        modal = createModal(root.value);
    }

    modal.show();
}

onMounted(() => {
    emitter.on("show-add-server-modal", showModal);
});
onUnmounted(() => {
    emitter.off("show-add-server-modal", showModal);
});
</script>

<style lang="scss" scoped>
.tlp-modal-footer {
    flex-direction: column;
}

.onlyoffice-admin-add-server-modal-footer {
    display: flex;
    justify-content: flex-end;
}
</style>
