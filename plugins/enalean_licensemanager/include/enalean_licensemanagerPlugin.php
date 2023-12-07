<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Tuleap\Admin\Homepage\StatisticsBadgePresenter;
use Tuleap\Admin\Homepage\StatisticsPresenter;
use Tuleap\Admin\Homepage\UserCounterDao;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Enalean\LicenseManager\CountDueLicenses\DueLicencesDao;
use Tuleap\Enalean\LicenseManager\CountDueLicenses\LicenseManagerCountDueLicensesCommand;
use Tuleap\Enalean\LicenseManager\LicenseManagerComputedMetricsCollector;
use Tuleap\Enalean\LicenseManager\QuotaLicenseCalculator;
use Tuleap\Enalean\LicenseManager\StatusActivityEmitter;
use Tuleap\Enalean\LicenseManager\Webhook\StatusLogger;
use Tuleap\Enalean\LicenseManager\Webhook\UserCounterPayload;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Instrument\Prometheus\CollectTuleapComputedMetrics;
use Tuleap\Plugin\ListeningToEventName;
use Tuleap\Webhook\Emitter;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class enalean_licensemanagerPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindtextdomain('tuleap-enalean_licensemanager', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(Event::GET_SITEADMIN_HOMEPAGE_USER_STATISTICS);
        $this->addHook(Event::GET_SITEADMIN_WARNINGS);

        $this->addHook(CollectTuleapComputedMetrics::NAME);
        $this->addHook(CLICommandsCollector::NAME);

        return parent::getHooksAndCallbacks();
    }

    /**
     * @return Tuleap\Enalean\LicenseManager\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\Enalean\LicenseManager\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    #[ListeningToEventName('project_admin_activate_user')]
    public function projectAdminActivateUser(array $params): void
    {
        $this->userStatusActivity('project_admin_activate_user', $params);
    }

    #[ListeningToEventName('project_admin_delete_user')]
    public function projectAdminDeleteUser(array $params): void
    {
        $this->userStatusActivity('project_admin_delete_user', $params);
    }

    #[ListeningToEventName('project_admin_suspend_user')]
    public function projectAdminSuspendUser(array $params): void
    {
        $this->userStatusActivity('project_admin_suspend_user', $params);
    }

    private function userStatusActivity($event, array $params)
    {
        $nb_max_users = $this->getMaxUsers();
        if (! $nb_max_users) {
            return;
        }
        $payload = new UserCounterPayload(new UserCounterDao(), $this->getMaxUsers(), $event, $params['user_id']);

        $webhook_emitter = new Emitter(
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            HttpClientFactory::createAsyncClient(),
            new StatusLogger()
        );

        $emitter = new StatusActivityEmitter($webhook_emitter);
        $emitter->emit($payload, $this->getWebhookUrl());
    }

    /** @see Event::GET_SITEADMIN_WARNINGS */
    public function get_siteadmin_warnings(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $nb_max_users = $this->getMaxUsers();
        if (! $nb_max_users) {
            return;
        }

        $nb_used_users = $this->getNbUsedUsersFromEventParams($params);

        if (QuotaLicenseCalculator::isQuotaExceeded($nb_used_users, $nb_max_users)) {
            $params['warnings'][] = $this->getExceededWarning($nb_max_users);
        } elseif (QuotaLicenseCalculator::isQuotaExceedingSoon($nb_used_users, $nb_max_users)) {
            $params['warnings'][] = $this->getExceedingSoonWarning($nb_used_users, $nb_max_users);
        }
    }

    /** @see Event::GET_SITEADMIN_HOMEPAGE_USER_STATISTICS */
    public function get_siteadmin_homepage_user_statistics(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $nb_max_users = $this->getMaxUsers();
        if (! $nb_max_users) {
            return;
        }

        $nb_used_users = $this->getNbUsedUsersFromEventParams($params);

        $nb_alive_users_label = sprintf(
            dngettext(
                'tuleap-enalean_licensemanager',
                '%d user',
                '%d users',
                $nb_used_users
            ),
            $nb_used_users
        );

        $max_allowed_users_label = sprintf(
            dngettext(
                'tuleap-enalean_licensemanager',
                '%d allowed',
                '%d allowed',
                $nb_max_users
            ),
            $nb_max_users
        );

        $level = StatisticsBadgePresenter::LEVEL_SECONDARY;
        if (QuotaLicenseCalculator::isQuotaExceeded($nb_used_users, $nb_max_users)) {
            $level = StatisticsBadgePresenter::LEVEL_DANGER;
        } elseif (QuotaLicenseCalculator::isQuotaExceedingSoon($nb_used_users, $nb_max_users)) {
            $level = StatisticsBadgePresenter::LEVEL_WARNING;
        }

        $params['additional_statistics'][] = new StatisticsPresenter(
            dgettext('tuleap-enalean_licensemanager', 'Allowed users quota'),
            [
                new StatisticsBadgePresenter(
                    "$nb_alive_users_label / $max_allowed_users_label",
                    $level
                ),
            ]
        );
    }

    /**
     * @see CollectTuleapComputedMetrics
     */
    public function collectComputedMetrics(CollectTuleapComputedMetrics $collect_tuleap_computed_metrics)
    {
        $license_manager_collector = new LicenseManagerComputedMetricsCollector(
            $collect_tuleap_computed_metrics->getPrometheus(),
            $this->getMaxUsers()
        );
        $license_manager_collector->collect();
    }

    /**
     * @return int
     */
    private function getMaxUsers()
    {
        $filename = $this->getPluginEtcRoot() . '/max_users.txt';
        if (! is_file($filename)) {
            return 0;
        }

        $nb_max_users = (int) file_get_contents($filename);

        return $nb_max_users;
    }

    /**
     * @return string
     */
    private function getWebhookUrl()
    {
        $filename = $this->getPluginEtcRoot() . '/webhook_url.txt';
        if (! is_file($filename)) {
            return '';
        }

        return trim(file_get_contents($filename));
    }

    /**
     * @param array $params
     * @return int
     */
    private function getNbUsedUsersFromEventParams(array $params)
    {
        $users_by_status = $params['nb_users_by_status'];
        \assert($users_by_status instanceof Tuleap\Admin\Homepage\NbUsersByStatus);
        $nb_used_users = $users_by_status->getNbActive()
            + $users_by_status->getNbPending()
            + $users_by_status->getNbRestricted()
            + $users_by_status->getNbAllValidated();

        return $nb_used_users;
    }

    /**
     * @param int $nb_used_users
     * @param int $nb_max_users
     * @return string
     */
    private function getExceedingSoonWarning($nb_used_users, $nb_max_users)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $title   = dgettext('tuleap-enalean_licensemanager', 'Warning!');
        $message = sprintf(
            dgettext(
                'tuleap-enalean_licensemanager',
                'You will be short of licenses soon (%1$d/%2$d), to purchase additional licenses please contact Enalean sales department (sales@enalean.com).'
            ),
            $nb_max_users - $nb_used_users,
            $nb_max_users
        );

        $warning = '
                <div class="tlp-alert-warning alert alert-warning alert-block">
                    <h4>' . $purifier->purify($title) . '</h4>
                    <p>
                        ' . $purifier->purify($message, CODENDI_PURIFIER_BASIC) . '
                    </p>
                </div>
            ';

        return $warning;
    }

    /**
     * @param int $nb_max_users
     * @return string
     */
    private function getExceededWarning($nb_max_users)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $title   = dgettext('tuleap-enalean_licensemanager', 'Oups!');
        $message = sprintf(
            dgettext(
                'tuleap-enalean_licensemanager',
                'You no longer have available licences (0/%1$d), please contact the Enalean sales department (sales@enalean.com) to purchase additional licenses.'
            ),
            $nb_max_users
        );

        $warning = '
                <div class="tlp-alert-danger alert alert-danger alert-block">
                    <h4>' . $purifier->purify($title) . '</h4>
                    <p>
                        ' . $purifier->purify($message, CODENDI_PURIFIER_BASIC) . '
                    </p>
                </div>
            ';

        return $warning;
    }

    public function collectCLICommands(CLICommandsCollector $commands_collector): void
    {
        $commands_collector->addCommand(
            LicenseManagerCountDueLicensesCommand::NAME,
            function (): LicenseManagerCountDueLicensesCommand {
                return new LicenseManagerCountDueLicensesCommand(
                    new \UserDao(),
                    new DueLicencesDao(),
                    \UserManager::instance(),
                    $this->getPluginEtcRoot()
                );
            }
        );
    }
}
