<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\Git\Webhook\WebhookSettingsPresenter;
use Tuleap\Git\Webhook\CreateWebhookButtonPresenter;
use Tuleap\Git\Webhook\WebhookPresenter;
use Tuleap\Git\Webhook\SectionOfWebhooksPresenter;
use Tuleap\Git\Git\Hook\WebHookFactory;

class GitViews_RepoManagement_Pane_Hooks extends GitViews_RepoManagement_Pane
{
    const ID = 'hooks';
    const CSRF_TOKEN_ID = 'GIT-WEBHOOK-SETTINGS';

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
    const ADDITIONAL_WEBHOOKS = 'plugin_git_settings_additional_webhooks';


    public function __construct(GitRepository $repository, Codendi_Request $request, WebHookFactory $webhook_factory)
    {
        parent::__construct($repository, $request);
        $this->webhook_factory = $webhook_factory;
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
        return $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_title');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent()
    {
        $description    = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_desc');

        $create_buttons       = array();
        $sections             = array();
        $additional_html_bits = array();

        EventManager::instance()->processEvent(
            self::ADDITIONAL_WEBHOOKS,
            array(
                'request'              => $this->request,
                'repository'           => $this->repository,
                'description'          => &$description,
                'create_buttons'       => &$create_buttons,
                'sections'             => &$sections,
                'additional_html_bits' => &$additional_html_bits
            )
        );

        $this->addCustomWebhooks($sections, $create_buttons);

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates/settings');

        return $renderer->renderToString(
            'hooks',
            new WebhookSettingsPresenter(
                $this->getTitle(),
                $description,
                $create_buttons,
                $sections
            )
        ) . implode('', $additional_html_bits);
    }

    private function addCustomWebhooks(array &$sections, array &$create_buttons)
    {
        $create_buttons[] = new CreateWebhookButtonPresenter();
        $csrf = new CSRFSynchronizerToken(self::CSRF_TOKEN_ID);

        $label               = $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_generic');
        $webhooks_presenters = array();

        $webhooks = $this->webhook_factory->getWebHooksForRepository($this->repository);
        if (count($webhooks) === 0) {
            return;
        }
        foreach ($webhooks as $webhook) {
            $webhook_logs = array();

            $webhooks_presenters[] = new WebhookPresenter(
                $this->repository,
                $webhook->getId(),
                $webhook->getUrl(),
                $webhook_logs,
                $csrf
            );
        }
        $sections[] = new SectionOfWebhooksPresenter($label, $webhooks_presenters);
    }
}
