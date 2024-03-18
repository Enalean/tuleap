<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Mediawiki\XML;

use PFUser;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Mediawiki\MediawikiDataDir;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;

final class XMLMediawikiExportabilityChecker implements CheckXMLMediawikiExportability
{
    public function __construct(private readonly EventDispatcherInterface $event_dispatcher)
    {
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    public function checkMediawikiCanBeExportedToXML(
        ExportXmlProject $event,
        MediawikiDataDir $mediawiki_data_dir,
    ): Ok|Err {
        if (! $event->shouldExportAllData()) {
            return Result::Err(XMLPartialExportFault::buildForXMLExportLogger());
        }

        $project          = $event->getProject();
        $project_name_dir = $mediawiki_data_dir->getMediawikiDir($project);

        if (! $project->usesService(\MediaWikiPlugin::SERVICE_SHORTNAME)) {
            return Result::Err(XMLExportMediawikiServiceNotUsedFault::buildForXMLExportLogger());
        }

        if (! is_dir($project_name_dir)) {
            return Result::Err(XMLExportMediawikiNotInstantiatedFault::buildForXMLExportLogger());
        }

        if ($this->isServiceActivationBlocked($project, $event->getUser())) {
            return Result::Err(XMLExportMediawikiCannotBeActivatedFault::buildForXMLExportLogger());
        }

        return Result::Ok(true);
    }

    protected function isServiceActivationBlocked(Project $project, PFUser $user): bool
    {
        $event = new ProjectServiceBeforeActivation(
            $project,
            \MediaWikiPlugin::SERVICE_SHORTNAME,
            $user
        );
        $this->event_dispatcher->dispatch($event);
        return $event->doesPluginSetAValue() && ! $event->canServiceBeActivated();
    }
}
