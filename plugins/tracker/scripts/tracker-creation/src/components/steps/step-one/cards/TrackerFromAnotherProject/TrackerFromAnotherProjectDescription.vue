<!--
    - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="card-content" data-test="template-description">
        <h4 class="card-title">
            {{ $gettext("Tracker from another project") }}
        </h4>
        <div class="card-description">
            <span class="card-description-content">
                {{
                    $gettext(
                        "Create your new tracker by replicating the configuration of an existing one.",
                    )
                }}
            </span>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
</script>
