<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Service;

use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;

/**
 * Plugins that implements this interface will automatically be able to register a new Service
 *
 * There is no need to listen explicitly to the hooks, as long as the plugin implements this interface, the hooks will
 * be registered
 */
interface PluginWithService
{
    /**
     * @see Event::SERVICE_CLASSNAMES
     * @param array{classnames: array<string, class-string>, project: \Project} $params
     */
    public function serviceClassnames(array &$params): void;

    /**
     * @see Event::SERVICE_IS_USED
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    public function serviceIsUsed(array $params): void;

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void;

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void;

    public function addMissingService(AddMissingService $event): void;

    public function servicesAllowedForProject(array $params): void;

    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event): void;
}
