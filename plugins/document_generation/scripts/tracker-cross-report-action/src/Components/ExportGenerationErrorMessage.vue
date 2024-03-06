<!--
  - Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
    <div class="export-error">
        <p class="tlp-alert-danger">
            {{
                $gettext(
                    "Sorry, we encountered an error while preparing your export. You might want to check that none of the selected reports have more than one table renderer as this is currently not supported.",
                )
            }}
        </p>
    </div>
</template>
<style lang="scss" scoped>
@use "sass:meta";

.export-error {
    padding: var(--tlp-medium-spacing);
}

@include meta.load-css("pkg:@tuleap/tlp-styles/components/alerts");
</style>
