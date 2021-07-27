<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

// This wrapper is used for verification so we can catch the classes that won't be preloaded due to missing dependencies
// We use a dedicated script that wraps the preload so this strict enforcement will only kick in development but will
// not affect production (as warnings about missing dependencies are harmless for production, it only means the preloading
// is skipped.
//
// **What to do as a developer ?**
//
// If you end-up there because `make composer preload MODE=Prod` failed with an error, it means:
// - either you need to exclude the incriminated file from preload in `composer.json` (either in src or in plugins)
// - or you need to add more things in preloading
//
// For the latter, it might be a rabbit hole as it might lead to add more and more things in preloading that might improve
// performances or just bloat the memory. In case of doubt, just exclude the file and check again with
// `make composer preload MODE=Prod` before pushing because the error handler bellow only report one warning at time.
set_error_handler(static fn ($errno, $errstr, $errfile, $errline) => die("$errstr $errfile L$errline\n"), E_ALL);

require __DIR__ . '/../../../preload.php';
