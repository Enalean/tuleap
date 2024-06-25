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
    <div class="tlp-form-element">
        <label class="tlp-label tlp-checkbox">
            <input type="checkbox" value="1" required data-test="approve_tos" />
            <span v-dompurify-html="agreement" v-on:click="loadAgreement" />
            <i class="fa fa-asterisk" aria-hidden="true"></i>
            <agreement-modal />
        </label>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import AgreementModal from "./AgreementModal.vue";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import emitter from "../../../helpers/emitter";

const { $gettext } = useGettext();

const agreement = computed((): string => {
    return $gettext(`I agree to the <a href="/tos/tos.php">policy agreement</a>`);
});

function loadAgreement(event: MouseEvent): void {
    if (event.target instanceof Element) {
        if (event.target.tagName === "A") {
            emitter.emit("show-agreement");
            event.preventDefault();
        }
    }
}
</script>
