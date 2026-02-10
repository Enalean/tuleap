<!--
  - Copyright (c) Enalean, 2026-present. All Rights Reserved.
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
    <div
        role="dialog"
        aria-labelledby="not-found-title"
        class="tlp-modal tlp-modal-danger"
        ref="element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="not-found-title">
                {{ $gettext("Not found") }}
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
                {{
                    $gettext("The resource you are requesting does not exist or has been removed.")
                }}
            </p>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                data-dismiss="modal"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
            >
                {{ $gettext("Close") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { onMounted, onBeforeUnmount, useTemplateRef } from "vue";
import { useGettext } from "vue3-gettext";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import { useRouter } from "vue-router";

const router = useRouter();
const { $gettext } = useGettext();

const element = useTemplateRef<HTMLElement>("element");
let modal: Modal | null = null;

onMounted(() => {
    if (element.value) {
        modal = createModal(element.value);
        modal.show();
        modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, () => router.push({ name: "fields-usage" }));
    }
});

onBeforeUnmount(() => {
    modal?.destroy();
});
</script>
