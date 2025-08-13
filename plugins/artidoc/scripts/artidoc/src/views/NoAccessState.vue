<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <state-message-with-image
        v-bind:title="$gettext('You don\'t have access to this document')"
        v-bind:message="$gettext('You can’t see nor edit this document')"
    >
        <template v-slot:state-image>
            <restricted-document-illustration />
        </template>
    </state-message-with-image>
</template>

<script setup lang="ts">
import StateMessageWithImage from "@/components/StateMessageWithImage.vue";
import { useGettext } from "vue3-gettext";
import RestrictedDocumentIllustration from "@/assets/RestrictedDocumentIllustration.vue";

const { $gettext } = useGettext();
</script>
