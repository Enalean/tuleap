<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use ForgeConfig;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use ProjectManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Project\ProjectAdministratorsIncludingDelegationDAO;
use Tuleap\Project\UserRemover;
use Tuleap\Request\DispatchablePSR15Compatible;
use UserManager;

final class RemoveFromProjectController extends DispatchablePSR15Compatible
{
    public const CSRF_TOKEN_NAME = 'remove_yourself_from_project';

    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UserRemover
     */
    private $user_remover;
    /**
     * @var ProjectAdministratorsIncludingDelegationDAO
     */
    private $project_administrators_including_delegation_dao;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        CSRFSynchronizerToken $csrf_token,
        UserManager $user_manager,
        ProjectManager $project_manager,
        UserRemover $user_remover,
        ProjectAdministratorsIncludingDelegationDAO $project_administrators_including_delegation_dao,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory                                = $response_factory;
        $this->csrf_token                                      = $csrf_token;
        $this->user_manager                                    = $user_manager;
        $this->project_manager                                 = $project_manager;
        $this->user_remover                                    = $user_remover;
        $this->project_administrators_including_delegation_dao = $project_administrators_including_delegation_dao;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->csrf_token->check('/my/');

        $user = $this->user_manager->getCurrentUser();

        $project_id = (int) $request->getAttribute('project_id');
        $project    = $this->project_manager->getProject($project_id);

        $has_been_removed = $this->user_remover->removeUserFromProject($project_id, $user->getId(), false);

        if (! $has_been_removed) {
            return $this->buildRedirectResponse();
        }

        $administrators = $this->project_administrators_including_delegation_dao->searchAdministratorEmailsIncludingDelegatedAccess($project_id);

        if ($administrators === []) {
            return $this->buildRedirectResponse();
        }

        $project_name = $project->getPublicName();

        $mail = new \Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo(implode(',', $administrators));
        $mail->setSubject(sprintf(_("%s : user %s removed from project '%s'"), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME), $user->getUserName(), $project_name));
        $link_members = sprintf('%s/project/%s/admin/members', \Tuleap\ServerHostname::HTTPSUrl(), urlencode((string) $project_id));
        $mail->setBodyText(
            sprintf(
                _("This message is being sent to notify the administrator(s) of\nproject '%s' that user %s has chosen to\nremove him/herself from the project.\n\nFollow this link to see the current members of your project:\n%s\n\n"),
                $project_name,
                $user->getUserName(),
                $link_members
            )
        );
        $mail->send();

        return $this->buildRedirectResponse();
    }

    private function buildRedirectResponse(): ResponseInterface
    {
        return $this->response_factory->createResponse(302)->withHeader('Location', '/my/');
    }
}
