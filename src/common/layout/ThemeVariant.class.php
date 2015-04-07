<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class ThemeVariant {

    const PREFERENCE_NAME = 'theme_variant';

    /** @var string */
    private $default;

    /** @var string[] */
    private $allowed;

    public function __construct() {
        $this->default = ForgeConfig::get('sys_default_theme_variant');
        $this->setAllowedVartiants();
    }

    private function setAllowedVartiants() {
        $this->allowed = ForgeConfig::get('sys_available_theme_variants');
        $this->allowed = explode(',', $this->allowed);
        $this->allowed = array_map('trim', $this->allowed);
        $this->allowed = array_filter($this->allowed);

        if (! is_file('../../www/themes/FlamingParrot/FlamingParrot_Theme.class.php')) {
            return;
        }

        require_once '../../www/themes/FlamingParrot/FlamingParrot_Theme.class.php';
        $this->unsetInvalidThemes();
    }

    public function getVariantForUser(PFUser $user) {
        $variant = $user->getPreference(self::PREFERENCE_NAME);
        if (! $variant) {
            return $this->default;
        }

        if (! $this->isAllowed($variant)) {
            return $this->default;
        }

        return $variant;
    }

    public function getAllowedVariants() {
        return $this->allowed;
    }

    public function isAllowed($variant) {
        return in_array($variant, $this->allowed);
    }

    public function getDefault() {
        return $this->default;
    }

    private function unsetInvalidThemes() {
        foreach ($this->allowed as $index => $item) {
            if (! in_array($item, FlamingParrot_Theme::getVariants())) {
               unset($this->allowed[$index]);
            }
        }

        $this->allowed = array_values($this->allowed);
    }
}
