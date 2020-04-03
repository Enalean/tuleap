<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\CLI\Events;

use BackendLogger;
use phpDocumentor\Reflection\DocBlockFactory;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigManager;
use Tuleap\Event\Dispatchable;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\layout\HomePage\NewsCollectionBuilder;
use Tuleap\layout\HomePage\StatisticsCollectionBuilder;
use Tuleap\Log\LogToGraylog2;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\Registration\ProjectRegistrationPresenterBuilder;
use Tuleap\User\UserSuspensionManager;
use Tuleap\System\ServiceControl;
use Tuleap\Widget\MyProjects;

final class GetWhitelistedKeys implements Dispatchable
{
    public const NAME = 'getWhitelistedKeys';

    private const TLP_CONFIG_ATTRIBUTE = 'tlp-config-key';

    /**
     * @var class-string[]
     */
    private $annotated_classes = [
        \ProjectManager::class,
    ];

    /**
     * @var array<string, bool|string>
     */
    private $white_listed_keys = [
        \ForgeAccess::ANONYMOUS_CAN_SEE_CONTACT => true,
        \ForgeAccess::ANONYMOUS_CAN_SEE_SITE_HOMEPAGE => true,
        ProjectVisibilityConfigManager::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY => true,
        Prometheus::CONFIG_PROMETHEUS_PLATFORM => true,
        Prometheus::CONFIG_PROMETHEUS_NODE_EXPORTER                                    => true,
        NewsCollectionBuilder::CONFIG_DISPLAY_NEWS                                     => true,
        StatisticsCollectionBuilder::CONFIG_DISPLAY_STATISTICS                         => true,
        DefaultProjectVisibilityRetriever::CONFIG_SETTING_NAME                         => true,
        ServiceControl::FORGECONFIG_INIT_MODE                                          => true,
        UserSuspensionManager::CONFIG_NOTIFICATION_DELAY                               => true,
        MyProjects::CONFIG_DISABLE_CONTACT                                             => true,
        ProjectRegistrationPresenterBuilder::FORGECONFIG_CAN_USE_DEFAULT_SITE_TEMPLATE => true,
        BackendLogger::CONFIG_LOGGER                                                   => true,
        UserSuspensionManager::CONFIG_INACTIVE_EMAIL                                   => true,
        LogToGraylog2::CONFIG_GRAYLOG2_SERVER                                          => true,
        LogToGraylog2::CONFIG_GRAYLOG2_PORT                                            => true,
        LogToGraylog2::CONFIG_GRAYLOG2_SSL                                             => true,
        LogToGraylog2::CONFIG_GRAYLOG2_DEBUG                                           => true,
    ];

    /**
     * @var DocBlockFactory
     */
    private $doc_block_factory;

    public function __construct(DocBlockFactory $doc_block_factory)
    {
        $this->doc_block_factory = $doc_block_factory;
    }

    public static function build(): self
    {
        return new self(DocBlockFactory::createInstance());
    }

    public function addPluginsKeys(string $key_name): void
    {
        $this->white_listed_keys[$key_name] = true;
    }

    /**
     * @return string[]
     */
    public function getWhiteListedKeys(): array
    {
        $this->initWhiteList();
        return array_keys($this->white_listed_keys);
    }

    public function isKeyWhiteListed(string $key): bool
    {
        $this->initWhiteList();
        return isset($this->white_listed_keys[$key]);
    }

    public function getSortedKeysWithMetadata(): \Generator
    {
        $this->initWhiteList();
        $keys = $this->white_listed_keys;
        ksort($keys, SORT_NATURAL);
        foreach ($keys as $key => $metadata) {
            yield $key => ($metadata === true ? '' : $metadata);
        }
    }

    private function initWhiteList(): void
    {
        foreach ($this->annotated_classes as $class_name) {
            $this->addConfigProviderClass($class_name);
        }
    }

    /**
     * Parse given class and extract constants that address a config key
     *
     * @param class-string $class_name
     * @throws \ReflectionException
     */
    private function addConfigProviderClass(string $class_name): void
    {
        $reflected_class = new \ReflectionClass($class_name);
        foreach ($reflected_class->getReflectionConstants() as $const) {
            $const_comment = $const->getDocComment();
            if ($const_comment) {
                $doc = $this->doc_block_factory->create($const_comment);
                $const_value = $const->getValue();
                if ($doc->hasTag(self::TLP_CONFIG_ATTRIBUTE) && is_string($const_value)) {
                    $this->white_listed_keys[$const_value] = $doc->getSummary();
                }
            }
        }
    }
}
