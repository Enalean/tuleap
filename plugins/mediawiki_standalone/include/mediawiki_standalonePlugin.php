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

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\DisableCacheMiddleware;
use Tuleap\Http\Server\RejectNonHTTPSRequestMiddleware;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\Layout\ServiceUrlCollector;
use Tuleap\MediawikiStandalone\OAuth2\MediawikiStandaloneOAuth2ConsentChecker;
use Tuleap\MediawikiStandalone\OAuth2\RejectAuthorizationRequiringConsent;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\MediawikiStandalone\Service\ServiceActivationHandler;
use Tuleap\MediawikiStandalone\Service\ServiceActivationProjectServiceBeforeActivationEvent;
use Tuleap\MediawikiStandalone\Service\ServiceActivationServiceDisabledCollectorEvent;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2ServerCore\App\AppFactory;
use Tuleap\OAuth2ServerCore\App\AppMatchingClientIDFilterAppTypeRetriever;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationCodeResponseFactory;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationEndpointController;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PKCE\PKCEInformationExtractor;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PromptParameterValuesExtractor;
use Tuleap\OAuth2ServerCore\AuthorizationServer\RedirectURIBuilder;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeCreator;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\PrefixOAuth2AuthCode;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\Scope\OAuth2AuthorizationCodeScopeDAO;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OpenIDConnectEmailScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OpenIDConnectProfileScope;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeSaver;
use Tuleap\OAuth2ServerCore\Scope\ScopeExtractor;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\User\OAuth2\Scope\CoreOAuth2ScopeBuilderFactory;
use Tuleap\User\OAuth2\Scope\OAuth2ProjectReadScope;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeBuilderCollector;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class mediawiki_standalonePlugin extends Plugin
{
    public const SERVICE_SHORTNAME = 'plugin_mediawiki_standalone';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-mediawiki_standalone', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-mediawiki_standalone', 'MediaWiki Standalone'),
                    '',
                    dgettext('tuleap-mediawiki_standalone', 'Standalone MediaWiki instances integration with Tuleap')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(ServiceUrlCollector::NAME);
        $this->addHook(ProjectServiceBeforeActivation::NAME);
        $this->addHook(ServiceDisabledCollector::NAME);
        $this->addHook(CollectRoutesEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getServiceShortname(): string
    {
        return self::SERVICE_SHORTNAME;
    }

    public function serviceClassnames(array &$params): void
    {
        $params['classnames'][$this->getServiceShortname()] = MediawikiStandaloneService::class;
    }

    public function serviceUrlCollector(ServiceUrlCollector $collector): void
    {
        if ($collector->getServiceShortname() === $this->getServiceShortname()) {
            $collector->setUrl('/to_be_defined');
        }
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        (new ServiceActivationHandler())->handle(new ServiceActivationProjectServiceBeforeActivationEvent($event));
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        (new ServiceActivationHandler())->handle(new ServiceActivationServiceDisabledCollectorEvent($event));
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $route_collector = $event->getRouteCollector();

        $route_collector->addRoute(['GET', 'POST'], '/mediawiki/oauth2_authorize', $this->getRouteHandler('routeAuthorizationEndpoint'));
    }

    public function routeAuthorizationEndpoint(): \Tuleap\Request\DispatchableWithRequest
    {
        $response_factory           = HTTPFactoryBuilder::responseFactory();
        $stream_factory             = HTTPFactoryBuilder::streamFactory();
        $uri_factory                = HTTPFactoryBuilder::URIFactory();
        $redirect_uri_builder       = new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory());
        $url_redirect               = new \URLRedirect(\EventManager::instance());
        $scope_builder              = AggregateAuthenticationScopeBuilder::fromBuildersList(
            CoreOAuth2ScopeBuilderFactory::buildCoreOAuth2ScopeBuilder(),
            AggregateAuthenticationScopeBuilder::fromEventDispatcher(\EventManager::instance(), new OAuth2ScopeBuilderCollector())
        );
        $authorization_code_creator = new OAuth2AuthorizationCodeCreator(
            new PrefixedSplitTokenSerializer(new PrefixOAuth2AuthCode()),
            new SplitTokenVerificationStringHasher(),
            new OAuth2AuthorizationCodeDAO(),
            new OAuth2ScopeSaver(new OAuth2AuthorizationCodeScopeDAO()),
            new DateInterval('PT1M'),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );

        $logger = \Tuleap\OAuth2ServerCore\OAuth2ServerRoutes::getOAuth2ServerLogger();
        return new AuthorizationEndpointController(
            new RejectAuthorizationRequiringConsent(
                new AuthorizationCodeResponseFactory(
                    $response_factory,
                    $authorization_code_creator,
                    $redirect_uri_builder,
                    $url_redirect,
                    $uri_factory
                ),
                $logger
            ),
            \UserManager::instance(),
            new AppFactory(
                new AppMatchingClientIDFilterAppTypeRetriever(new AppDao(), self::SERVICE_SHORTNAME),
                \ProjectManager::instance()
            ),
            new ScopeExtractor($scope_builder),
            new AuthorizationCodeResponseFactory(
                $response_factory,
                $authorization_code_creator,
                $redirect_uri_builder,
                $url_redirect,
                $uri_factory
            ),
            new PKCEInformationExtractor(),
            new PromptParameterValuesExtractor(),
            new MediawikiStandaloneOAuth2ConsentChecker(self::allowedOAuth2Scopes()),
            $logger,
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_SHORTNAME),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new DisableCacheMiddleware()
        );
    }

    /**
     * @return non-empty-list<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>>
     */
    private static function allowedOAuth2Scopes(): array
    {
        return [
          OAuth2SignInScope::fromItself(),
          OpenIDConnectEmailScope::fromItself(),
          OpenIDConnectProfileScope::fromItself(),
          OAuth2ProjectReadScope::fromItself(),
        ];
    }
}
