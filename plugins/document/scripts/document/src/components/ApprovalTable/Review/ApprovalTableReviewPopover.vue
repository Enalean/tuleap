<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <section class="tlp-popover tlp-popover-info" ref="popover_content">
        <div class="tlp-popover-arrow"></div>
        <div class="tlp-popover-header">
            <h1 class="tlp-popover-title">
                {{ $gettext("Possible commitment are:") }}
            </h1>
        </div>
        <div class="tlp-popover-body">
            <ul>
                <li>
                    <strong>{{ $gettext("Not yet") }}</strong>
                    {{ $gettext(": Status quo. You can add comment but nobody will be notified.") }}
                </li>
                <li>
                    <strong>{{ $gettext("Approved") }}</strong>
                    {{
                        $gettext(
                            ": Sends an email to the approval requester and notify next reviewer in sequence if any.",
                        )
                    }}
                </li>
                <li>
                    <strong>{{ $gettext("Rejected") }}</strong>
                    {{
                        $gettext(
                            ": Sends an email to the approval requester and stops the approval sequence if any.",
                        )
                    }}
                </li>
                <li>
                    <strong>{{ $gettext("Comment only") }}</strong>
                    {{
                        $gettext(
                            ": Sends an email to the approval requester to inform there are comments. In Sequential notification type, the notification workflow is frozen.",
                        )
                    }}
                </li>
                <li>
                    <strong>{{ $gettext("Will not review") }}</strong>
                    {{
                        $gettext(
                            ": Sends an email to the approval requester and notify next reviewer in sequence if any.",
                        )
                    }}
                </li>
            </ul>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { createPopover } from "@tuleap/tlp-popovers";

const props = defineProps<{
    trigger: HTMLElement;
}>();

const popover_content = ref<HTMLElement>();

onMounted(() => {
    if (popover_content.value === undefined) {
        throw Error("Failed to create review popover");
    }

    createPopover(props.trigger, popover_content.value);
});
</script>
