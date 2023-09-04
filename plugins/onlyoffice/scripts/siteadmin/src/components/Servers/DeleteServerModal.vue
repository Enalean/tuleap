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
        v-bind:action="server.delete_url"
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        v-bind:aria-labelledby="'onlyoffice-admin-delete-server-title-' + server.id"
        ref="root"
    >
        <div class="tlp-modal-header">
            <h1
                class="tlp-modal-title"
                v-bind:id="'onlyoffice-admin-delete-server-title-' + server.id"
            >
                {{ $gettext("Delete document server") }}
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
            <p>
                {{ $gettext("You're about to delete the following document server:") }}
                <span class="tlp-badge-secondary">{{ server.server_url }}</span>
            </p>
            <p>
                {{
                    $gettext(
                        "Deleting a document server will induce loosing of modifications for users that are currently using it.",
                    )
                }}
            </p>
            <p>{{ $gettext("Please confirm your action.") }}</p>
            <csrf-token />
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button type="submit" class="tlp-button-danger tlp-modal-action">
                {{ $gettext("Delete") }}
            </button>
        </div>
    </form>
</template>
<script setup lang="ts">
import type { Server } from "../../type";
import CsrfToken from "../CsrfToken.vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { onMounted, onUnmounted, ref } from "vue";
import emitter from "../../helpers/emitter";

const props = defineProps<{ server: Server }>();

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
    emitter.on("show-delete-server-modal", showModal);
});
onUnmounted(() => {
    emitter.off("show-delete-server-modal", showModal);
});
</script>
