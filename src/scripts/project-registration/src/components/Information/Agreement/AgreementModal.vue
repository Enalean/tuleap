<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div
        id="modal-big-content"
        class="tlp-modal"
        role="dialog"
        aria-labelledby="term-of-services"
        ref="modal_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="term-of-services">
                {{ $gettext("Policy agreement") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fa-solid fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body" v-if="is_loading">
            <i class="fas fa-circle-notch fa-spin"></i>
        </div>
        <div class="tlp-modal-body" v-dompurify-html="agreement_content" v-else></div>
        <div class="tlp-modal-footer">
            <button type="button" class="tlp-button-primary tlp-modal-action" data-dismiss="modal">
                {{ $gettext("Close") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { useGettext } from "vue3-gettext";
import { getTermOfService } from "../../../api/rest-querier";
import emitter from "../../../helpers/emitter";

const { $gettext } = useGettext();

let modal: Modal | null = null;

const is_loading = ref<boolean>(false);
const agreement_content = ref<string>("");
const modal_element = ref<InstanceType<typeof Element>>();

onMounted(() => {
    emitter.on("show-agreement", show);
});
onBeforeUnmount(() => {
    emitter.off("show-agreement", show);
});

async function show(): Promise<void> {
    if (!modal_element.value) {
        return;
    }
    modal = createModal(modal_element.value, { destroy_on_hide: true });
    if (modal) {
        is_loading.value = true;
        modal.show();

        agreement_content.value = await getTermOfService();
        is_loading.value = false;
    }
}
</script>
