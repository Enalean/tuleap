<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\Transfer\Basic;

use HTTPRequest;
use Tuleap\Authentication\SplitToken\IncorrectSizeVerificationStringException;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationException;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationTokenHeaderSerializer;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationVerifier;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeUpload;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;

class LFSBasicTransferUploadController implements DispatchableWithRequestNoAuthz
{
    /**
     * @var \gitlfsPlugin
     */
    private $plugin;
    /**
     * @var ActionAuthorizationTokenHeaderSerializer
     */
    private $authorization_token_unserializer;
    /**
     * @var ActionAuthorizationVerifier
     */
    private $authorization_verifier;
    /**
     * @var \Tuleap\GitLFS\Authorization\Action\AuthorizedAction
     */
    private $authorized_action;

    public function __construct(
        \gitlfsPlugin $plugin,
        ActionAuthorizationTokenHeaderSerializer $authorization_token_unserializer,
        ActionAuthorizationVerifier $authorization_verifier
    ) {
        $this->plugin                           = $plugin;
        $this->authorization_token_unserializer = $authorization_token_unserializer;
        $this->authorization_verifier           = $authorization_verifier;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        http_response_code(501);
    }

    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('gitlfs');
        if ($this->authorized_action !== null) {
            throw new \RuntimeException(
                'This controller expects to process only one request and then thrown away. One request seems to already have been processed.'
            );
        }
        $authorization_header = $request->getFromServer('HTTP_AUTHORIZATION');
        if ($authorization_header === false) {
            return false;
        }

        try {
            $authorization_token = $this->authorization_token_unserializer->getSplitToken(
                new ConcealedString($authorization_header)
            );
        } catch (IncorrectSizeVerificationStringException $ex) {
            return false;
        } catch (InvalidIdentifierFormatException $ex) {
            return false;
        }

        try {
            $this->authorized_action = $this->authorization_verifier->getAuthorization(
                new \DateTimeImmutable(),
                $authorization_token,
                $variables['oid'],
                new ActionAuthorizationTypeUpload()
            );
        } catch (ActionAuthorizationException $ex) {
            return false;
        }

        $repository = $this->authorized_action->getRepository();
        $project    = $repository->getProject();
        if ($repository === null || ! $project->isActive() || ! $this->plugin->isAllowed($project->getID()) ||
            ! $repository->isCreated()) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }

        return true;
    }
}
