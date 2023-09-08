<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class User_LoginController extends MVC2_Controller
{
    public function __construct(Codendi_Request $request)
    {
        parent::__construct('user', $request);
    }

    public function index($presenter)
    {
        $renderer = TemplateRendererFactory::build()->getRenderer($presenter->getTemplateDir());
        $renderer->renderToPage($presenter->getTemplate(), $presenter);
    }

    public function confirmHash()
    {
        $user_manager = UserManager::instance();
        $confirm_hash = $this->request->get('confirm_hash');
        $success      = $user_manager->getUserByConfirmHash($confirm_hash) !== null;
        if ($success) {
            // Get user status: if already set to 'R' (restricted) don't change it!
            $user = $user_manager->getUserByConfirmHash($confirm_hash);
            if ($user->getStatus() == PFUser::STATUS_RESTRICTED || $user->getStatus() == PFUser::STATUS_VALIDATED_RESTRICTED) {
                $user->setStatus(PFUser::STATUS_RESTRICTED);
            } else {
                $user->setStatus(PFUser::STATUS_ACTIVE);
            }
            $user_manager->updateDb($user);

            $user_manager->removeConfirmHash($confirm_hash);

            $GLOBALS['Response']->addFeedback('info', _('Your account has been successfully confirmed.'));
        } else {
            $GLOBALS['Response']->addFeedback('error', _('Invalid confirmation hash.'));
        }
    }
}
