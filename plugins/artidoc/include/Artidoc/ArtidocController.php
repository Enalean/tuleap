<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc;

use HTTPRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Artidoc\Document\ArtidocBreadcrumbsProvider;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Document\Field\ConfiguredFieldCollectionBuilder;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Document\ConfiguredTrackerRetriever;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Document\Field\ConfiguredFieldRepresentation;
use Tuleap\Artidoc\Document\Tracker\DocumentTrackerRepresentation;
use Tuleap\Artidoc\Document\Tracker\SuitableTrackersForDocumentRetriever;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Docman\ServiceDocman;
use Tuleap\Document\RecentlyVisited\RecordVisit;
use Tuleap\Export\Pdf\Template\GetPdfTemplatesEvent;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\NeverThrow\Fault;
use Tuleap\Project\ServiceInstrumentation;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;

final readonly class ArtidocController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    #[FeatureFlagConfigKey(<<<'EOF'
    Feature flag to allow display of fields in artidoc documents.
    0 to deactivate (default)
    1 to activate
    EOF
    )]
    #[ConfigKeyString('0')]
    public const FIELDS_FEATURE_FLAG = 'enable_artidoc_fields';

    public function __construct(
        private RetrieveArtidocWithContext $retrieve_artidoc,
        private ConfiguredTrackerRetriever $configured_tracker_retriever,
        private SuitableTrackersForDocumentRetriever $suitable_trackers_retriever,
        private ArtidocBreadcrumbsProvider $breadcrumbs_provider,
        private ConfiguredFieldCollectionBuilder $configured_fields_builder,
        private LoggerInterface $logger,
        private FileUploadDataProvider $file_upload_provider,
        private EventDispatcherInterface $event_dispatcher,
        private RecordVisit $recently_visited_dao,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        ServiceInstrumentation::increment('artidoc');

        $this->retrieve_artidoc->retrieveArtidocUserCanRead((int) $variables['id'])
            ->match(
                fn (ArtidocWithContext $document_information) => $this->renderPage($document_information, $layout, $request->getCurrentUser()),
                function (Fault $fault) {
                    Fault::writeToLogger($fault, $this->logger);
                    throw new NotFoundException();
                }
            );
    }

    private function renderPage(ArtidocWithContext $document_information, BaseLayout $layout, \PFUser $user): void
    {
        $layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../scripts/artidoc/frontend-assets',
                    '/assets/artidoc/artidoc'
                ),
                'src/index.ts'
            )
        );

        if (! $user->isAnonymous()) {
            $this->recently_visited_dao->save((int) $user->getId(), $document_information->document->getId(), \Tuleap\Request\RequestTime::getTimestamp());
        }

        $title   = $document_information->document->getTitle();
        $service = $document_information->getContext(ServiceDocman::class);
        if (! $service instanceof ServiceDocman) {
            throw new \LogicException('Service is missing');
        }

        $project_id          = (int) $service->getProject()->getId();
        $permissions_manager = \Docman_PermissionsManager::instance($project_id);
        $user_can_write      = $permissions_manager->userCanWrite($user, $document_information->document->getId());

        $allowed_max_size = \ForgeConfig::getInt('sys_max_size_upload');

        $service->displayHeader(
            $title,
            $this->breadcrumbs_provider->getBreadcrumbs($document_information, $user),
            [],
            HeaderConfigurationBuilder::get($title)
                ->inProject($service->getProject(), \DocmanPlugin::SERVICE_SHORTNAME)
                ->withBodyClass(['has-sidebar-with-pinned-header', 'reduce-help-button'])
                ->build()
        );

        $configured_tracker = $this->configured_tracker_retriever->getTracker($document_information->document);
        $configured_fields  = $configured_tracker ? $this->configured_fields_builder->buildFromArtidoc($document_information, $user)->getFields($configured_tracker) : [];

        \TemplateRendererFactory::build()
            ->getRenderer(__DIR__)
            ->renderToPage(
                'artidoc',
                new ArtidocPresenter(
                    $document_information->document->getId(),
                    $project_id,
                    $user_can_write,
                    $title,
                    $this->getTrackerRepresentation($configured_tracker, $user),
                    array_map(
                        fn (\Tracker $tracker): DocumentTrackerRepresentation => $this->getTrackerRepresentation($tracker, $user),
                        $this->suitable_trackers_retriever->getTrackers($document_information, $user),
                    ),
                    array_map(
                        fn (ConfiguredField $configured_field): ConfiguredFieldRepresentation => ConfiguredFieldRepresentation::fromConfiguredField($configured_field),
                        $configured_fields,
                    ),
                    $allowed_max_size,
                    $this->event_dispatcher->dispatch(new GetPdfTemplatesEvent($user))->getTemplates(),
                    \ForgeConfig::getFeatureFlag(self::FIELDS_FEATURE_FLAG) === '1',
                )
            );
        $service->displayFooter();
    }

    /**
     * @psalm-return ($tracker is null ? null : DocumentTrackerRepresentation)
     */
    private function getTrackerRepresentation(?\Tracker $tracker, \PFUser $user): ?DocumentTrackerRepresentation
    {
        if ($tracker) {
            return DocumentTrackerRepresentation::fromTracker($this->file_upload_provider, $tracker, $user);
        }

        return null;
    }
}
