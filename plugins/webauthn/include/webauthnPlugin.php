<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class WebAuthnPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-webauthn', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): Tuleap\WebAuthn\Plugin\PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\WebAuthn\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    private function getTemplateRenderer(): TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates');
    }

    private function getViteAssets(string $application): \Tuleap\Layout\IncludeViteAssets
    {
        return new \Tuleap\Layout\IncludeViteAssets(
            __DIR__ . "/../scripts/${application}/frontend-assets",
            "/assets/webauthn/$application"
        );
    }

    private function getWebAuthnCredentialSourceDao(): \Tuleap\WebAuthn\Source\WebAuthnCredentialSourceDao
    {
        return new \Tuleap\WebAuthn\Source\WebAuthnCredentialSourceDao();
    }

    public function getAccountSettings(): Tuleap\Request\DispatchableWithRequest
    {
        return new Tuleap\WebAuthn\Controllers\AccountController(
            $this->getTemplateRenderer(),
            EventManager::instance(),
            $this->getViteAssets('account'),
            $this->getWebAuthnCredentialSourceDao()
        );
    }

    #[Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (FastRoute\RouteCollector $r) {
            $r->get('/account', $this->getRouteHandler('getAccountSettings'));
        });
    }

    #[Tuleap\Plugin\ListeningToEventClass]
    public function accountTabPresenterCollection(Tuleap\User\Account\AccountTabPresenterCollection $collection): void
    {
        $collection->add(new Tuleap\User\Account\AccountTabPresenter(
            dgettext('tuleap-webauthn', 'Passkeys'),
            $this->getPluginPath() . '/account',
            $collection->getCurrentHref()
        ));
    }
}
