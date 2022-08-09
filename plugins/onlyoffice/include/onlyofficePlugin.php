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
 */

declare(strict_types=1);

use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\Download\DocmanFileDownloadController;
use Tuleap\Docman\Download\DocmanFileDownloadResponseGenerator;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\SessionWriteCloseMiddleware;
use Tuleap\OnlyOffice\Download\DownloadDocumentWithTokenMiddleware;
use Tuleap\OnlyOffice\Download\OnlyOfficeDownloadDocumentTokenDAO;
use Tuleap\OnlyOffice\Download\OnlyOfficeDownloadDocumentTokenVerifier;
use Tuleap\OnlyOffice\Download\PrefixOnlyOfficeDocumentDownload;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../../docman/include/docmanPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class onlyofficePlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-onlyoffice', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-onlyoffice', 'ONLYOFFICE integration'),
                    dgettext('tuleap-onlyoffice', 'Allow to open documents into ONLYOFFICE')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['docman'];
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(CollectRoutesEvent::NAME);
        return parent::getHooksAndCallbacks();
    }

    public function collectRoutesEvent(CollectRoutesEvent $routes): void
    {
        $routes->getRouteCollector()->addGroup(
            '/onlyoffice',
            function (FastRoute\RouteCollector $r): void {
                $r->get('/document_download', $this->getRouteHandler('routeGetDocumentDownload'));
            }
        );
    }

    public function routeGetDocumentDownload(): DocmanFileDownloadController
    {
        $logger           = BackendLogger::getDefaultLogger();
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $token_middleware = new DownloadDocumentWithTokenMiddleware(
            new PrefixedSplitTokenSerializer(new PrefixOnlyOfficeDocumentDownload()),
            new OnlyOfficeDownloadDocumentTokenVerifier(
                new OnlyOfficeDownloadDocumentTokenDAO(),
                new DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection()),
                new SplitTokenVerificationStringHasher(),
                $logger,
            ),
            UserManager::instance()
        );
        return new DocmanFileDownloadController(
            new SapiStreamEmitter(),
            new Docman_ItemFactory(),
            new DocmanFileDownloadResponseGenerator(
                new Docman_VersionFactory(),
                new BinaryFileResponseBuilder($response_factory, HTTPFactoryBuilder::streamFactory())
            ),
            $token_middleware,
            $logger,
            new SessionWriteCloseMiddleware(),
            $token_middleware,
        );
    }
}
