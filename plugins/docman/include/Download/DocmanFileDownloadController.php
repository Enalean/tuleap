<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Download;

use Docman_File;
use Docman_Item;
use Docman_ItemFactory;
use HTTPRequest;
use Logger;
use LogicException;
use Project;
use ProjectManager;
use function session_write_close;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final class DocmanFileDownloadController implements DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var DocmanFileDownloadResponseGenerator
     */
    private $file_download_response_generator;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Docman_Item|null
     */
    private $item;

    public function __construct(
        ProjectManager $project_manager,
        Docman_ItemFactory $item_factory,
        DocmanFileDownloadResponseGenerator $file_download_response_generator,
        Logger $logger
    ) {
        $this->project_manager                  = $project_manager;
        $this->item_factory                     = $item_factory;
        $this->file_download_response_generator = $file_download_response_generator;
        $this->logger                           = $logger;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables) : void
    {
        if ($this->item === null) {
            throw new LogicException(
                'self::getProject() must be called before starting the request processing'
            );
        }

        if (! $this->item instanceof Docman_File) {
            throw new NotFoundException(dgettext('tuleap-docman', 'The file cannot be found.'));
        }

        try {
            $file_response = $this->file_download_response_generator->generateResponse(
                $request->getCurrentUser(),
                $this->item,
                isset($variables['version_id']) ? (int) $variables['version_id'] : null
            );
        } catch (VersionNotFoundException $exception) {
            $this->logger->debug($exception->getMessage());
            throw new NotFoundException(dgettext('tuleap-docman', 'The requested version of this file cannot be found.'));
        } catch (FileDownloadException $exception) {
            $this->logger->debug($exception->getMessage());
            throw new NotFoundException(dgettext('tuleap-docman', 'The file cannot be found.'));
        }

        session_write_close();
        $file_response->send();
    }

    public function getProject(\HTTPRequest $request, array $variables) : Project
    {
        if ($this->item !== null) {
            throw new LogicException(
                'Controller seems to be reused, due to potential side effects please throw away the instance after the first use'
            );
        }

        $this->item = $this->item_factory->getItemFromDb($variables['file_id']);
        if ($this->item === null) {
            throw new NotFoundException(dgettext('tuleap-docman', 'The file cannot be found.'));
        }

        $project = $this->project_manager->getProject($this->item->getGroupId());
        if ($project === null) {
            throw new NotFoundException(dgettext('tuleap-docman', 'The file cannot be found.'));
        }

        return $project;
    }
}
