<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
    <div class="tlp-property">
        <label-for-field v-bind:field="field" />
        <span
            v-if="is_user_loading"
            class="tlp-property"
            data-test="field-static-user-text-skeleton"
        >
            <i
                class="fa-solid fa-user tlp-skeleton-text-icon tlp-skeleton-icon"
                aria-hidden="true"
            ></i>
            <span class="tlp-skeleton-text"></span>
        </span>
        <span v-else-if="!is_user_loading" data-test="field-static-user-text">
            <div class="tlp-avatar-mini">
                <img v-bind:alt="buildUserAvatarAlt()" loading="lazy" v-bind:src="avatar_url" />
            </div>
            {{ display_name }}
        </span>
    </div>
</template>

<script setup lang="ts">
import LabelForField from "./LabelForField.vue";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CURRENT_USER, IS_USER_LOADING } from "../../injection-symbols";
import { useGettext } from "vue3-gettext";
import type { User } from "@tuleap/core-rest-api-types";

const { interpolate, $gettext } = useGettext();

const current_user = strictInject(CURRENT_USER);
const is_user_loading = strictInject(IS_USER_LOADING);

const display_name = current_user.match(
    (user: User) => user.display_name,
    () => "",
);

const avatar_url = current_user.match(
    (user: User) => user.avatar_url,
    () => "",
);

function buildUserAvatarAlt(): string {
    return interpolate($gettext("%{user} user's avatar"), {
        user: display_name,
    });
}

defineProps<{
    field: StructureFields;
}>();
</script>
