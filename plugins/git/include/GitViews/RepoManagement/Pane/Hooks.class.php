<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use Tuleap\Git\Webhook\WebhookSettingsPresenter;
use Tuleap\Git\Webhook\CreateWebhookButtonPresenter;
use Tuleap\Git\Webhook\GenericWebhookPresenter;
use Tuleap\Git\Webhook\SectionOfWebhooksPresenter;
use Tuleap\Git\Webhook\CreateWebhookModalPresenter;
use Tuleap\Git\Webhook\EditWebhookModalPresenter;
use Tuleap\Git\Webhook\WebhookLogPresenter;
use Tuleap\Git\Webhook\WebhookFactory;
use Tuleap\Git\Webhook\WebhookDao;
use Tuleap\Git\Webhook\Webhook;
use GitRepository;
use Codendi_Request;
use CSRFSynchronizerToken;
use EventManager;
use TemplateRendererFactory;

class Hooks extends Pane
{
    public const ID = 'hooks';
    public const CSRF_TOKEN_ID = 'GIT-WEBHOOK-SETTINGS';

    /**
     * Allow plugins to add additional hooks setup for git
     *
     * Parameters:
     *   'repository'           => (Input) GitRepository Git repository currently modified
     *   'request'              => (Input) HTTPRequest   Current request
     *   'descritption'         => (Output) String       The description of the hooks
     *   'create_buttons'       => (Output) Array of CreateWebhookButtonPresenter
     *   'additional_html_bits' => (Output) Array of html string
     */
    public const ADDITIONAL_WEBHOOKS = 'plugin_git_settings_additional_webhooks';

    /**
     * @var WebhookFactory
     */
    private $webhook_factory;

    /**
     * @var WebhookDao
     */
    private $webhook_dao;

    public function __construct(
        GitRepository $repository,
        Codendi_Request $request,
        WebhookFactory $webhook_factory,
        WebhookDao $webhook_dao
    ) {
        parent::__construct($repository, $request);
        $this->webhook_factory = $webhook_factory;
        $this->webhook_dao     = $webhook_dao;
    }

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    public function getIdentifier()
    {
        return self::ID;
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    public function getTitle()
    {
        return dgettext('tuleap-git', 'Webhooks');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent()
    {
        $description            = dgettext('tuleap-git', 'You can define several generic webhooks.');
        $additional_description = '';

        $create_buttons       = array();
        $sections             = array();
        $additional_html_bits = array();

        EventManager::instance()->processEvent(
            self::ADDITIONAL_WEBHOOKS,
            array(
                'request'                => $this->request,
                'repository'             => $this->repository,
                'description'            => &$description,
                'create_buttons'         => &$create_buttons,
                'sections'               => &$sections,
                'additional_html_bits'   => &$additional_html_bits,
                'additional_description' => &$additional_description,
            )
        );

        $csrf = new CSRFSynchronizerToken(self::CSRF_TOKEN_ID);
        $this->addCustomWebhooks($sections, $create_buttons, $csrf);

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates/settings');

        return $renderer->renderToString(
            'hooks',
            new WebhookSettingsPresenter(
                $csrf,
                $this->getTitle(),
                $description,
                $additional_description,
                $create_buttons,
                $sections,
                new CreateWebhookModalPresenter($this->repository),
                new EditWebhookModalPresenter($this->repository)
            )
        ) . implode('', $additional_html_bits);
    }

    private function addCustomWebhooks(array &$sections, array &$create_buttons, CSRFSynchronizerToken $csrf)
    {
        $create_buttons[] = new CreateWebhookButtonPresenter();

        $label               = dgettext('tuleap-git', 'Generic webhooks');
        $webhooks_presenters = array();

        $webhooks = $this->webhook_factory->getWebhooksForRepository($this->repository);
        if (count($webhooks) === 0) {
            return;
        }
        $use_default_edit_modal = true;
        foreach ($webhooks as $webhook) {
            $webhook_logs = $this->getLogsForWebhook($webhook);

            $webhooks_presenters[] = new GenericWebhookPresenter(
                $this->repository,
                $webhook->getId(),
                $webhook->getUrl(),
                $webhook_logs,
                $csrf,
                $use_default_edit_modal
            );
        }
        $sections[] = new SectionOfWebhooksPresenter($label, $webhooks_presenters);
    }

    private function getLogsForWebhook(Webhook $webhook)
    {
        $logs = array();
        foreach ($this->webhook_dao->getLogs($webhook->getId()) as $row) {
            $logs[] = new WebhookLogPresenter(
                format_date($GLOBALS['Language']->getText('system', 'datefmt'), $row['created_on']),
                $this->formatStatus($row['status'])
            );
        }

        return $logs;
    }

    private function formatStatus($status)
    {
        $classname = 'text-success';
        $icon      = 'fa fa-check-circle';
        if ($status[0] !== '2') {
            $classname = 'text-warning';
            $icon      = 'fa fa-exclamation-triangle';
        }

        return '<span class="' . $classname . '" title="' . $this->hp->purify($status) . '">
            <i class="' . $icon . '"></i> ' . $this->hp->purify($status) . '
            </span>';
    }
}
