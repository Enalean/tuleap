<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\REST;

use Luracast\Restler\Data\ApiMethodInfo;
use Luracast\Restler\InvalidAuthCredentials;
use Luracast\Restler\RestException;
use Psr\Log\LoggerInterface;
use Rest_Exception_InvalidTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\User\AccessKey\AccessKeyException;
use Tuleap\User\OAuth2\OAuth2Exception;
use User_LoginException;

class RESTAuthenticationFlowIsAllowed
{
    /** @var UserManager */
    private $user_manager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(UserManager $user_manager, LoggerInterface $logger)
    {
        $this->user_manager = $user_manager;
        $this->logger       = $logger;
    }

    /**
     * @throws RestException
     */
    public function isAllowed(?ApiMethodInfo $api_method_info): bool
    {
        try {
            if ($this->requestIsOption() || $this->currentUserIsNotAnonymous($api_method_info)) {
                return true;
            }
        } catch (\LogicException $exception) {
            throw $exception;
        } catch (User_LoginException $exception) {
            $this->logger->debug('Cannot log user', ['exception' => $exception]);
            throw new InvalidAuthCredentials(403, $exception->getMessage());
        } catch (Rest_Exception_InvalidTokenException $exception) {
            $this->logger->debug('REST token is not valid', ['exception' => $exception]);
            throw new InvalidAuthCredentials(401, $exception->getMessage());
        } catch (AccessKeyException $exception) {
            $this->logger->debug('Access key is not valid', ['exception' => $exception]);
            throw new InvalidAuthCredentials(401, 'Invalid access key');
        } catch (OAuth2Exception $exception) {
            $this->logger->debug('OAuth2 access token is not valid', ['exception' => $exception]);
            throw new InvalidAuthCredentials(401, 'Invalid OAuth2 access token');
        } catch (SplitTokenException $exception) {
            $this->logger->debug('Split token cannot be parsed correctly', ['exception' => $exception]);
            throw new InvalidAuthCredentials(401, 'Key or token incorrectly formatted');
        }

        return false;
    }

    private function requestIsOption(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS';
    }

    private function currentUserIsNotAnonymous(?ApiMethodInfo $api_method_info): bool
    {
        $user = $this->user_manager->getCurrentUser($api_method_info);
        return ! $user->isAnonymous();
    }
}
