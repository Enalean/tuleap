<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div v-if="hasError" class="tlp-alert-danger">
        {{ message() }}
    </div>
</template>
<script setup lang="ts">
import { ERROR_TYPE_NO_GIT, ERROR_TYPE_UNKNOWN_ERROR } from "../constants";
import { useGetters, useState } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const { error_message_type } = useState(["error_message_type"]);
const { hasError } = useGetters(["hasError"]);

const message = (): string => {
    switch (error_message_type.value) {
        case ERROR_TYPE_NO_GIT:
            return $gettext("Git plugin is not activated");
        case ERROR_TYPE_UNKNOWN_ERROR:
            return $gettext("An error occurred during your last action.");
        default:
            return "";
    }
};
</script>
