<!--
  - Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
    <div class="explanations-export">
        <p class="tlp-alert-info">
            {{
                $gettext(
                    "Please be aware this configuration screen will be reworked in the future. The development team has knowledge of the visual and usability issues.",
                )
            }}
        </p>
        {{ $gettext("For each levels: ") }}
        <ul>
            <li>
                {{
                    $gettext(
                        "The selected artifacts will be the linked ones that are not filtered by a criteria of the selected report.",
                    )
                }}
            </li>
            <li>
                {{
                    $gettext(
                        "The columns exported are the ones used of the first table of the selected report.",
                    )
                }}
            </li>
        </ul>
    </div>
</template>
<style lang="scss" scoped>
@use "sass:meta";

.explanations-export {
    padding: var(--tlp-medium-spacing);
}

@include meta.load-css("@tuleap/tlp-styles/components/alerts");
</style>
