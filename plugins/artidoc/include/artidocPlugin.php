<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\ArtidocRetriever;
use Tuleap\Artidoc\Adapter\Document\ArtidocWithContextDecorator;
use Tuleap\Artidoc\Adapter\Document\CurrentUserHasArtidocPermissionsChecker;
use Tuleap\Artidoc\Adapter\Document\SearchArtidocDocumentDao;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\ArtidocController;
use Tuleap\Artidoc\Document\ArtidocBreadcrumbsProvider;
use Tuleap\Artidoc\Document\ArtidocDao;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContextRetriever;
use Tuleap\Artidoc\Document\ConfiguredTrackerRetriever;
use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\Artidoc\Document\Tracker\SuitableTrackerForDocumentChecker;
use Tuleap\Artidoc\Document\Tracker\SuitableTrackersForDocumentRetriever;
use Tuleap\Artidoc\REST\ResourcesInjector;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Docman\Item\CloneOtherItemPostAction;
use Tuleap\Docman\Item\GetDocmanItemOtherTypeEvent;
use Tuleap\Docman\Item\Icon\DocumentIconPresenterEvent;
use Tuleap\Docman\Item\Icon\GetIconForItemEvent;
use Tuleap\Docman\Item\OtherDocumentHrefEvent;
use Tuleap\Docman\ItemType\GetItemTypeAsText;
use Tuleap\Docman\Reference\DocumentIconPresenter;
use Tuleap\Docman\REST\v1\Folders\FilterItemOtherTypeProvider;
use Tuleap\Docman\REST\v1\GetOtherDocumentItemRepresentationWrapper;
use Tuleap\Docman\REST\v1\MoveItem\MoveOtherItemUriRetriever;
use Tuleap\Docman\REST\v1\Others\OtherTypePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Others\VerifyOtherTypeIsSupported;
use Tuleap\Docman\REST\v1\Search\SearchRepresentationOtherType;
use Tuleap\Document\RecentlyVisited\RecentlyVisitedDocumentDao;
use Tuleap\Document\RecentlyVisited\VisitedOtherDocumentHref;
use Tuleap\Document\Tree\OtherItemTypeDefinition;
use Tuleap\Document\Tree\OtherItemTypes;
use Tuleap\Document\Tree\SearchCriterionListOptionPresenter;
use Tuleap\Document\Tree\TypeOptionsCollection;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\ListeningToEventName;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Tracker\Artifact\Event\ArtifactDeleted;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../docman/include/docmanPlugin.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ArtidocPlugin extends Plugin implements PluginWithConfigKeys
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-artidoc', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-artidoc', 'Artidoc'),
                    dgettext('tuleap-artidoc', 'Artifacts as Documents'),
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['tracker', 'docman'];
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup('/artidoc', function (FastRoute\RouteCollector $r) {
            $r->get('/{id:\d+}[/]', $this->getRouteHandler('routeController'));
        });
    }

    public function routeController(): DispatchableWithRequest
    {
        $tracker_factory     = TrackerFactory::instance();
        $docman_item_factory = new Docman_ItemFactory();
        $dao                 = $this->getArtidocDao();
        $logger              = BackendLogger::getDefaultLogger();

        $form_element_factory = Tracker_FormElementFactory::instance();
        return new ArtidocController(
            new ArtidocWithContextRetriever(
                new ArtidocRetriever(new SearchArtidocDocumentDao(), $docman_item_factory),
                CurrentUserHasArtidocPermissionsChecker::withCurrentUser(HTTPRequest::instance()->getCurrentUser()),
                new ArtidocWithContextDecorator(
                    ProjectManager::instance(),
                    new DocumentServiceFromAllowedProjectRetriever($this),
                ),
            ),
            new ConfiguredTrackerRetriever(
                $dao,
                $tracker_factory,
                $logger,
            ),
            new SuitableTrackersForDocumentRetriever(
                new SuitableTrackerForDocumentChecker(
                    $form_element_factory,
                ),
                $tracker_factory,
            ),
            new ArtidocBreadcrumbsProvider($docman_item_factory),
            $logger,
            new FileUploadDataProvider(
                new FrozenFieldDetector(
                    new TransitionRetriever(
                        new StateFactory(
                            \TransitionFactory::instance(),
                            new SimpleWorkflowDao()
                        ),
                        new TransitionExtractor()
                    ),
                    new FrozenFieldsRetriever(
                        new FrozenFieldsDao(),
                        $form_element_factory
                    )
                ),
                $form_element_factory
            ),
            EventManager::instance(),
            new RecentlyVisitedDocumentDao(),
        );
    }

    #[ListeningToEventClass]
    public function getDocmanItemOtherTypeEvent(GetDocmanItemOtherTypeEvent $event): void
    {
        if ($event->type !== ArtidocDocument::TYPE) {
            return;
        }

        if (! isset($event->row['group_id'])) {
            return;
        }

        if (! $this->isAllowed($event->row['group_id'])) {
            return;
        }

        $event->setInstance(new ArtidocDocument($event->row));
    }

    #[ListeningToEventClass]
    public function getOtherDocumentItemRepresentationWrapper(GetOtherDocumentItemRepresentationWrapper $event): void
    {
        if ($event->item instanceof ArtidocDocument) {
            $event->setItemRepresentationArguments(
                ArtidocDocument::TYPE,
                new OtherTypePropertiesRepresentation($this->getArtidocHref($event->item)),
            );
        }
    }

    #[ListeningToEventClass]
    public function otherDocumentHrefEvent(OtherDocumentHrefEvent $event): void
    {
        if ($event->item instanceof ArtidocDocument) {
            $event->setHref($this->getArtidocHref($event->item));
        }
    }

    #[ListeningToEventClass]
    public function visitedOtherDocumentHref(VisitedOtherDocumentHref $event): void
    {
        if ($event->item instanceof ArtidocDocument) {
            $event->setHref($this->getArtidocHref($event->item));
        }
    }

    private function getArtidocHref(ArtidocDocument $document): string
    {
        return '/artidoc/' . urlencode((string) $document->getId());
    }

    #[ListeningToEventClass]
    public function searchRepresentationOtherType(SearchRepresentationOtherType $event): void
    {
        if ($event->item instanceof ArtidocDocument) {
            $event->setType(ArtidocDocument::TYPE);
        }
    }

    #[ListeningToEventClass]
    public function otherItemTypes(OtherItemTypes $event): void
    {
        $event->addType(
            ArtidocDocument::TYPE,
            new OtherItemTypeDefinition(
                'fa-solid fa-tlp-artidoc document-other-type-badge tlp-swatch-peggy-pink',
                dgettext('tuleap-artidoc', 'Artidoc'),
            )
        );
    }

    #[ListeningToEventClass]
    public function moveOtherItemUriRetriever(MoveOtherItemUriRetriever $event): void
    {
        if ($event->item instanceof ArtidocDocument) {
            $event->setMoveUri('/api/artidoc/' . urlencode((string) $event->item->getId()));
        }
    }

    #[ListeningToEventClass]
    public function cloneOtherItemPostAction(CloneOtherItemPostAction $event): void
    {
        if ($event->source instanceof ArtidocDocument && $event->target instanceof ArtidocDocument) {
            $this->getArtidocDao()->cloneItem($event->source->getId(), $event->target->getId());
        }
    }

    #[ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        (new ResourcesInjector())->populate($params['restler']);
    }

    #[ListeningToEventClass]
    public function typeOptionsCollection(TypeOptionsCollection $collection): void
    {
        (new DocumentServiceFromAllowedProjectRetriever($this))
            ->getDocumentServiceFromAllowedProject($collection->project)
            ->match(
                fn () => $collection->addOptionAfter(
                    'folder',
                    new SearchCriterionListOptionPresenter(ArtidocDocument::TYPE, dgettext('tuleap-artidoc', 'Artidoc'))
                ),
                static fn () => null
            );
    }

    #[ListeningToEventClass]
    public function filterItemOtherTypeProvider(FilterItemOtherTypeProvider $provider): void
    {
        if ($provider->name === ArtidocDocument::TYPE) {
            $provider->setValue(ArtidocDocument::TYPE);
        }
    }

    #[ListeningToEventClass]
    public function getItemTypeAsText(GetItemTypeAsText $event): void
    {
        $event->addOtherTypeLabel(ArtidocDocument::TYPE, dgettext('tuleap-artidoc', 'Artidoc'));
    }

    #[ListeningToEventClass]
    public function checkOtherTypeIsSupported(VerifyOtherTypeIsSupported $event): void
    {
        if ($event->type === ArtidocDocument::TYPE) {
            $event->flagAsSupported();
        }
    }

    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(ArtidocController::class);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerArtifactDeleted(ArtifactDeleted $artifact_deleted): void
    {
        $this->getArtidocDao()
            ->deleteSectionsByArtifactId($artifact_deleted->getArtifact()->getId());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getIconForItemEvent(GetIconForItemEvent $event): void
    {
        if ($event->item instanceof ArtidocDocument) {
            $event->setIcon('artidoc');
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function documentIconPresenterEvent(DocumentIconPresenterEvent $event): void
    {
        if ($event->icon === 'artidoc') {
            $event->setPresenter(new DocumentIconPresenter('fa-solid fa-tlp-artidoc', 'peggy-pink'));
        }
    }

    private function getArtidocDao(): ArtidocDao
    {
        return new ArtidocDao(
            new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory()),
            new UUIDFreetextIdentifierFactory(new DatabaseUUIDV7Factory()),
        );
    }
}
