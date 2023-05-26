<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Config;

use BackendLogger;
use BackendSystem;
use ForgeAccess;
use ProjectManager;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigManager;
use Tuleap\BrowserDetection\BrowserDeprecationMessage;
use Tuleap\DB\DBAuthUserConfig;
use Tuleap\DB\DBConfig;
use Tuleap\Event\Dispatchable;
use Tuleap\HelpDropdown\HelpDropdownPresenterBuilder;
use Tuleap\Http\Client\FilteredOutboundHTTPResponseAlerter;
use Tuleap\Http\Client\OutboundHTTPRequestProxy;
use Tuleap\Http\Client\OutboundHTTPRequestSettings;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\InviteBuddy\InvitationPurger;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\HomePage\NewsCollectionBuilder;
use Tuleap\Layout\HomePage\StatisticsCollectionBuilder;
use Tuleap\Log\LogToGraylog2;
use Tuleap\Mail\Transport\MailTransportBuilder;
use Tuleap\Project\Admin\Export\ProjectExportController;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\Request\RequestInstrumentation;
use Tuleap\ServerHostname;
use Tuleap\System\ServiceControl;
use Tuleap\SystemEvent\Massmail;
use Tuleap\User\UserSuspensionManager;
use Tuleap\Widget\MyProjects;
use User_UserStatusManager;

final class GetConfigKeys implements Dispatchable, ConfigClassProvider
{
    public const NAME = 'getConfigKeys';

    public const CORE_CLASSES_WITH_CONFIG_KEYS = [
        ProjectManager::class,
        User_UserStatusManager::class,
        ForgeAccess::class,
        ProjectVisibilityConfigManager::class,
        Prometheus::class,
        NewsCollectionBuilder::class,
        StatisticsCollectionBuilder::class,
        DefaultProjectVisibilityRetriever::class,
        ServiceControl::class,
        UserSuspensionManager::class,
        MyProjects::class,
        BackendLogger::class,
        LogToGraylog2::class,
        InviteBuddyConfiguration::class,
        HelpDropdownPresenterBuilder::class,
        BrowserDeprecationMessage::class,
        DBConfig::class,
        DBAuthUserConfig::class,
        BackendSystem::class,
        ConfigurationVariables::class,
        ServerHostname::class,
        ProjectExportController::class,
        MailTransportBuilder::class,
        MercureClient::class,
        BaseLayout::class,
        Massmail::class,
        InvitationPurger::class,
        \ThemeVariant::class,
        OutboundHTTPRequestSettings::class,
        OutboundHTTPRequestProxy::class,
        FilteredOutboundHTTPResponseAlerter::class,
        RequestInstrumentation::class,
    ];

    /**
     * @var class-string[]
     */
    private array $annotated_classes;

    /**
     * @var array<string, ConfigKeyMetadata>
     */
    private array $white_listed_keys = [];

    public function __construct()
    {
        $this->annotated_classes = self::CORE_CLASSES_WITH_CONFIG_KEYS;
    }

    /**
     * Declare a class that holds constants with Tuleap\Config\ConfigKey or Tuleap\Config\FeatureFlagConfigKey attributes
     *
     * @param class-string $class_name
     */
    public function addConfigClass(string $class_name): void
    {
        $this->annotated_classes[] = $class_name;
    }

    /**
     * @return string[]
     */
    public function getKeysThatCanBeModified(): array
    {
        $this->initWhiteList();
        $keys = [];
        foreach ($this->white_listed_keys as $key => $metadata) {
            if ($metadata->can_be_modified) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    public function canBeModified(string $key): bool
    {
        $this->initWhiteList();
        return isset($this->white_listed_keys[$key]) && $this->white_listed_keys[$key]->can_be_modified;
    }

    /**
     * @return array<string, ConfigKeyMetadata>
     */
    public function getSortedKeysWithMetadata(): array
    {
        $this->initWhiteList();
        $keys = $this->white_listed_keys;
        ksort($keys, SORT_NATURAL);
        return $keys;
    }

    /**
     * @throws UnknownConfigKeyException
     */
    public function getKeyMetadata(string $key): ConfigKeyMetadata
    {
        $this->initWhiteList();
        if (! isset($this->white_listed_keys[$key])) {
            throw new UnknownConfigKeyException($key);
        }
        return $this->white_listed_keys[$key];
    }

    private function initWhiteList(): void
    {
        foreach ($this->annotated_classes as $class_name) {
            $this->findTlpConfigConst($class_name);
        }
    }

    /**
     * Parse given class and extract constants that address a config key
     *
     * @param class-string $class_name
     * @throws \ReflectionException
     */
    private function findTlpConfigConst(string $class_name): void
    {
        $reflected_class = new \ReflectionClass($class_name);
        $category        = $this->getClassCategory($reflected_class);
        foreach ($reflected_class->getReflectionConstants(\ReflectionClassConstant::IS_PUBLIC) as $const) {
            $key               = '';
            $summary           = '';
            $can_be_modified   = true;
            $is_secret         = false;
            $is_hidden         = false;
            $has_default_value = false;
            $secret_validator  = null;
            $value_validator   = null;
            foreach ($const->getAttributes() as $attribute) {
                $attribute_object = $attribute->newInstance();
                if ($attribute_object instanceof ConfigKey) {
                    $const_value = $const->getValue();
                    if (is_string($const_value)) {
                        $key     = $const_value;
                        $summary = $attribute_object->summary;
                    }
                }
                if ($attribute_object instanceof FeatureFlagConfigKey) {
                    $const_value = $const->getValue();
                    if (is_string($const_value)) {
                        $key     = \ForgeConfig::FEATURE_FLAG_PREFIX . $const_value;
                        $summary = $attribute_object->summary;
                    }
                }
                if ($attribute_object instanceof ConfigCannotBeModifiedInterface) {
                    $can_be_modified = false;
                }
                if ($attribute_object instanceof ConfigKeySecret) {
                    $is_secret = true;
                }
                if ($attribute_object instanceof ConfigKeySecretValidator) {
                    $secret_validator = $attribute_object->validator_class_name::buildSelf();
                }
                if ($attribute_object instanceof ConfigKeyValueValidator) {
                    $value_validator = $attribute_object->validator_class_name::buildSelf();
                }
                if ($attribute_object instanceof ConfigKeyHidden) {
                    $is_hidden = true;
                }
                if ($attribute_object instanceof ConfigKeyType) {
                    $has_default_value = $attribute_object->hasDefaultValue();
                }
            }
            if (! $key) {
                continue;
            }
            $this->white_listed_keys[$key] = new ConfigKeyMetadata(
                $summary,
                $can_be_modified,
                $is_secret,
                $is_hidden,
                $has_default_value,
                $secret_validator,
                $value_validator,
                $category,
            );
        }
    }

    private function getClassCategory(\ReflectionClass $reflected_class): ?string
    {
        $class_attributes = $reflected_class->getAttributes(ConfigKeyCategory::class);
        if (\count($class_attributes) !== 1) {
            return null;
        }

        $category_attribute = $class_attributes[0]->newInstance();
        assert($category_attribute instanceof ConfigKeyCategory);
        return $category_attribute->name;
    }
}
