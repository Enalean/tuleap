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
use Logger;
use LogicException;
use PFUser;
use Project;
use ProjectManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\NotFoundException;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

final class DocmanFileDownloadController extends DispatchablePSR15Compatible implements DispatchableWithProject
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
        EmitterInterface $emitter,
        ProjectManager $project_manager,
        Docman_ItemFactory $item_factory,
        DocmanFileDownloadResponseGenerator $file_download_response_generator,
        Logger $logger,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct(
            $emitter,
            ...$middleware_stack
        );
        $this->project_manager                  = $project_manager;
        $this->item_factory                     = $item_factory;
        $this->file_download_response_generator = $file_download_response_generator;
        $this->logger                           = $logger;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
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
            $attribute_version_id = $request->getAttribute('version_id');
            /** @var PFUser $current_user */
            $current_user = $request->getAttribute(RESTCurrentUserMiddleware::class);
            return $this->file_download_response_generator->generateResponse(
                $request,
                $current_user,
                $this->item,
                $attribute_version_id !== null ? (int) $attribute_version_id : null
            );
        } catch (VersionNotFoundException $exception) {
            $this->logger->debug($exception->getMessage());
            throw new NotFoundException(dgettext('tuleap-docman', 'The requested version of this file cannot be found.'));
        } catch (FileDownloadException $exception) {
            $this->logger->debug($exception->getMessage());
            throw new NotFoundException(dgettext('tuleap-docman', 'The file cannot be found.'));
        }
    }

    public function getProject(array $variables) : Project
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
