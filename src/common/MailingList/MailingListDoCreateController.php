<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\MailingList;

use EventManager;
use Feedback;
use ForgeConfig;
use HTTPRequest;
use MailingListDao;
use PFUser;
use Project;
use Rule_Email;
use Service;
use SodiumException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;

class MailingListDoCreateController implements DispatchableWithRequest
{
    /**
     * @var ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var ProjectAdministratorChecker
     */
    private $administrator_checker;
    /**
     * @var MailingListDao
     */
    private $dao;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var MailingListDomainBuilder
     */
    private $list_domain_builder;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        MailingListDao $dao,
        EventManager $event_manager,
        MailingListDomainBuilder $list_domain_builder,
    ) {
        $this->project_retriever     = $project_retriever;
        $this->administrator_checker = $administrator_checker;
        $this->dao                   = $dao;
        $this->event_manager         = $event_manager;
        $this->list_domain_builder   = $list_domain_builder;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->project_retriever->getProjectFromId($variables['id']);
        if (! $project->usesMail()) {
            throw new NotFoundException();
        }
        $service = $project->getService(Service::ML);
        if (! ($service instanceof ServiceMailingList)) {
            throw new NotFoundException();
        }

        $current_user = $request->getCurrentUser();
        $this->administrator_checker->checkUserIsProjectAdministrator($current_user, $project);

        MailingListAdministrationController::getCSRF($project)->check();

        $list_password = $this->generatePassword();

        $list_name = $this->getValidListName($request, $layout, $current_user, $project);
        if (! $list_name) {
            $GLOBALS['Response']->redirect(MailingListAdministrationController::getUrl($project));

            return;
        }

        $list_id = $this->dao->create(
            (int) $project->getID(),
            $list_name,
            (bool) $request->getValidated('is_public', 'int', 0),
            $list_password,
            (int) $current_user->getId(),
            htmlspecialchars($request->getValidated('description', 'string', '')),
        );

        if (! $list_id) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Error Adding List'));
            $GLOBALS['Response']->redirect(MailingListAdministrationController::getUrl($project));

            return;
        }

        $GLOBALS['Response']->addFeedback(Feedback::INFO, _('List added'));

        $this->event_manager->processEvent('mail_list_create', ['group_list_id' => $list_id,]);

        $this->sendThePasswordToTheUser($request, $list_name, $list_password, $current_user);
        $GLOBALS['Response']->redirect(MailingListAdministrationController::getUrl($project));
    }

    private function getValidListName(
        HTTPRequest $request,
        BaseLayout $layout,
        PFUser $current_user,
        Project $project,
    ): ?string {
        $list_name = $request->getValidated('list_name', 'string', '');
        if (! $list_name || strlen($list_name) < ForgeConfig::get('sys_lists_name_min_length')) {
            $layout->addFeedback(
                Feedback::ERROR,
                _('Must provide list name that is 4 or more characters long')
            );

            return null;
        }

        if (! preg_match('/(^([a-zA-Z\_0-9\.-]*))$/', $list_name)) {
            $layout->addFeedback(
                Feedback::ERROR,
                _('List name contains bad characters. Authorized characters are: letters, numbers, -, _, .')
            );

            return null;
        }

        if ($current_user->isSuperUser()) {
            $new_list_name = strtolower($list_name);
        } else {
            $new_list_name = ForgeConfig::get('sys_lists_prefix')
                . strtolower($project->getUnixName() . '-' . $list_name)
                . ForgeConfig::get('sys_lists_suffix');
        }

        $rule = new Rule_Email();
        if (! $rule->isValid($this->getListEmailAddress($new_list_name))) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Invalid List Name'));

            return null;
        }

        if ($this->dao->isThereAnExistingListInTheProject($new_list_name, (int) $project->getID())) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('List already exists'));

            return null;
        }

        return $new_list_name;
    }

    private function getListServerUrl(HTTPRequest $request): string
    {
        return 'https://' . ForgeConfig::get('sys_lists_host');
    }

    /**
     * @param string $sys_lists_domain
     */
    private function sendThePasswordToTheUser(
        HTTPRequest $request,
        string $new_list_name,
        string $list_password,
        PFUser $current_user,
    ): void {
        $message = sprintf(
            _(
                'A mailing list will be created on %1$s in a few minutes
and you are the list administrator.

Your mailing list info is at:
%3$s

List administration can be found at:
%4$s

Your list password is: %5$s
You are encouraged to change this password as soon as possible.

Thank you for using %1$s.

 -- The %1$s Team'
            ),
            ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME),
            $this->getListEmailAddress($new_list_name),
            $this->getListServerUrl($request) . "/mailman/listinfo/$new_list_name",
            $this->getListServerUrl($request) . "/mailman/admin/$new_list_name",
            $list_password
        );

        $hdrs  = "From: " . ForgeConfig::get('sys_email_admin') . ForgeConfig::get('sys_lf');
        $hdrs .= 'Content-type: text/plain; charset=utf-8' . ForgeConfig::get('sys_lf');

        mail($current_user->getEmail(), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME) . " " . _('New mailing list'), $message, $hdrs);

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            sprintf(_('Email sent with details to: %1$s'), $current_user->getEmail()),
        );
    }

    /**
     * @throws SodiumException
     */
    private function generatePassword(): string
    {
        return \sodium_bin2base64(random_bytes(12), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }

    private function getListEmailAddress(string $new_list_name): string
    {
        return $new_list_name . '@' . $this->list_domain_builder->build();
    }
}
