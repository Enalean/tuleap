<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

use DateTimeImmutable;
use HTTPRequest;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class AccessKeyCreationController implements DispatchableWithRequest
{
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        if ($current_user->isAnonymous()) {
            throw new ForbiddenException(_('Unauthorized action for anonymous'));
        }

        (new \CSRFSynchronizerToken('/account/index.php'))->check();

        $access_key_creator = new AccessKeyCreator(
            new LastAccessKeyIdentifierStore(
                new AccessKeySerializer(),
                (new KeyFactory)->getEncryptionKey(),
                $_SESSION
            ),
            new AccessKeyDAO(),
            new SplitTokenVerificationStringHasher(),
            new AccessKeyCreationNotifier($request->getServerUrl(), \Codendi_HTMLPurifier::instance())
        );

        $description     = $request->get('access-key-description') ?: '';
        $expiration_date = $this->getExpirationDate($request, $layout);

        try {
            $access_key_creator->create($current_user, $description, $expiration_date);
            $layout->redirect('/account/#account-access-keys');
        } catch (AccessKeyAlreadyExpiredException $exception) {
            $layout->addFeedback(
                \Feedback::ERROR,
                _("You cannot create an already expired access key.")
            );

            $layout->redirect('/account/');
        }
    }

    private function getExpirationDate(HTTPRequest $request, BaseLayout $layout): ?DateTimeImmutable
    {
        $expiration_date = null;

        $provided_expiration_date = $request->get('access-key-expiration-date');
        if ($provided_expiration_date !== '') {
            $expiration_date = \DateTimeImmutable::createFromFormat('Y-m-d', $provided_expiration_date);

            if (! $expiration_date) {
                $layout->addFeedback(
                    \Feedback::ERROR,
                    _("Expiration date is not well formed.")
                );

                $layout->redirect('/account/');
            }

            $expiration_date = $expiration_date->setTime(23, 59, 59);
        }

        return $expiration_date;
    }
}
