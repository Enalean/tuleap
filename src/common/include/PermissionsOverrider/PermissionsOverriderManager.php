<?php
/*
* Copyright (c) Enalean 2015. All Rights Reserved.
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

/**
 * I'm responsible of dealing with the eventual PermissionsOverrider class
 * used as a glue to override Tuleap permissions
 */
class PermissionsOverrider_PermissionsOverriderManager {

    const PERMISSIONS_OVERRIDER_DIRECTORY = "local_glue";
    const PERMISSIONS_OVERRIDER_FILE      = "PermissionsOverrider.php";

    public function doesOverriderAllowUserToAccessPlatform(PFUser $user) {
        $permissions_overrider = $this->getPermissionsOverrider();

        if (! $permissions_overrider) {
            return false;
        }

        return $permissions_overrider->decideToLetUserAccessPlatformEvenIfTuleapWouldNot($user);
    }

    public function doesOverriderAllowUserToAccessProject(PFUser $user, Project $project) {
        $permissions_overrider = $this->getPermissionsOverrider();

        if (! $permissions_overrider) {
            return false;
        }

        return $permissions_overrider->decideToLetUserAccessProjectEvenIfTuleapWouldNot($user, $project);
    }

    public function doesOverriderForceUsageOfAnonymous() {
        $permissions_overrider = $this->getPermissionsOverrider();

        if (! $permissions_overrider) {
            return false;
        }

        return $permissions_overrider->forceUsageOfAnonymous();
    }

    private function getPermissionsOverriderDirectory() {
        return ForgeConfig::get("sys_custom_dir")."/".self::PERMISSIONS_OVERRIDER_DIRECTORY;
    }

    private function getPermissionsOverriderFilePath() {
        return $this->getPermissionsOverriderDirectory()."/".self::PERMISSIONS_OVERRIDER_FILE;
    }

    private function getPermissionsOverrider() {
        if (! is_dir($this->getPermissionsOverriderDirectory())) {
            return;
        }

        if (! is_file($this->getPermissionsOverriderFilePath())) {
            return;
        }

        require_once($this->getPermissionsOverriderFilePath());

        $permissions_overrider = new PermissionsOverrider();

        if (! $permissions_overrider instanceof PermissionsOverrider_IOverridePermissions ) {
            return;
        }

        return $permissions_overrider;
    }
}