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

use Tuleap\Cryptography\KeyFactory;
use Tuleap\MFA\Enrollment\EnrollmentDisplayController;
use Tuleap\MFA\Enrollment\EnrollmentRegisterController;
use Tuleap\MFA\Enrollment\TOTP\TOTPEnroller;
use Tuleap\MFA\Enrollment\TOTP\TOTPEnrollmentDAO;
use Tuleap\MFA\Enrollment\TOTP\TOTPRetriever;
use Tuleap\MFA\Enrollment\TrialAuthenticationDisplayController;
use Tuleap\MFA\Enrollment\TrialAuthenticationVerifierController;
use Tuleap\MFA\OTP\TOTPModeBuilder;
use Tuleap\MFA\OTP\TOTPValidator;

require_once __DIR__ . '/../vendor/autoload.php';

class mfaPlugin  extends Plugin // @codingStandardsIgnoreLine
{
    public const NAME = 'mfa';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-mfa', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\MFA\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function routeGetEnroll(): EnrollmentDisplayController
    {
        return new EnrollmentDisplayController(
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/'),
            $this->getTOTPEnroller()
        );
    }

    public function routePostEnroll(): EnrollmentRegisterController
    {
        return new EnrollmentRegisterController(
            $this->getTOTPEnroller()
        );
    }

    public function routeGetEnrollTest(): TrialAuthenticationDisplayController
    {
        return new TrialAuthenticationDisplayController(
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/'),
            $this->getTOTPEnroller()
        );
    }

    public function routePostEnrollTest(): TrialAuthenticationVerifierController
    {
        return new TrialAuthenticationVerifierController(
            new TOTPRetriever(
                new TOTPEnrollmentDAO(),
                (new KeyFactory())->getEncryptionKey(),
                TOTPModeBuilder::build()
            ),
            new TOTPValidator()
        );
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (FastRoute\RouteCollector $r) {
            $r->get('/enroll', $this->getRouteHandler('routeGetEnroll'));
            $r->post('/enroll', $this->getRouteHandler('routePostEnroll'));
            $r->get('/enroll/test', $this->getRouteHandler('routeGetEnrollTest'));
            $r->post('/enroll/test', $this->getRouteHandler('routePostEnrollTest'));
        });
    }

    /**
     * @return TOTPEnroller
     */
    private function getTOTPEnroller()
    {
        return new TOTPEnroller(
            new TOTPEnrollmentDAO(),
            (new KeyFactory())->getEncryptionKey(),
            new TOTPValidator(),
            TOTPModeBuilder::build()
        );
    }
}
