<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Codendi_HTMLPurifier;
use Luracast\Restler\RestException;
use Tracker_ArtifactFactory;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentations\DefinitionRepresentation;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentations\DefinitionRepresentationBuilder;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\CachingTrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use UserManager;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\Dao;

class DefinitionsResource
{
    /** @var UserManager */
    private $user_manager;

    /** @var ArtifactFactory */
    private $testmanagement_artifact_factory;

    /** @var DefinitionRepresentationBuilder */
    private $definition_representation_builder;
    /**
     * @var ConfigConformanceValidator
     */
    private $conformance_validator;

    public function __construct()
    {
        $config                      = new Config(new Dao(), \TrackerFactory::instance());
        $this->conformance_validator = new ConfigConformanceValidator($config);
        $artifact_dao                = new ArtifactDao();
        $artifact_factory            = Tracker_ArtifactFactory::instance();

        $this->user_manager                    = UserManager::instance();
        $this->testmanagement_artifact_factory = new ArtifactFactory(
            $config,
            $artifact_factory,
            $artifact_dao
        );

        $retriever = new RequirementRetriever($artifact_factory, $artifact_dao, $config);

        $purifier                                = Codendi_HTMLPurifier::instance();
        $this->definition_representation_builder = new DefinitionRepresentationBuilder(
            Tracker_FormElementFactory::instance(),
            $this->conformance_validator,
            $retriever,
            $purifier,
            CommonMarkInterpreter::build($purifier),
            new ArtifactRepresentationBuilder(
                Tracker_FormElementFactory::instance(),
                Tracker_ArtifactFactory::instance(),
                new TypeDao(),
                new ChangesetRepresentationBuilder(
                    UserManager::instance(),
                    Tracker_FormElementFactory::instance(),
                    new CommentRepresentationBuilder(
                        CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance())
                    ),
                    new PermissionChecker(new CachingTrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentUGroupEnabledDao())))
                )
            ),
        );
    }

    /**
     * @url OPTIONS {id}
     *
     */
    protected function optionsId(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get a definition
     *
     * Get a definition by id
     * <br/>
     * <br/>
     * Test steps can be rendered as two formats: HTML or Text. Test steps rendered as HTML can have two source formats: HTML itself or CommonMark (Markdown).<br/>
     * Test steps that are already written in HTML have the following structure:
     * <pre><code>{<br/>
     *   &quot;description&quot;: &quot;&lt;p&gt;HTML description&lt;/p&gt;&quot;,<br/>
     *   &quot;description_format&quot;: &quot;html&quot;<br/>
     *   &quot;expected_results&quot;: &quot;HTML expected results&quot;,<br/>
     *   &quot;expected_results_format&quot;: &quot;html&quot;,<br/>
     *   "rank": 1<br/>
     * }</code></pre>
     * <p>Test steps that are written in CommonMark format (Markdown) have an additional "commonmark" property that contains the source.
     * Notice that they also have format "html":</p>
     * <pre><code>{<br/>
     *   &quot;description&quot;: &quot;&lt;p&gt;&lt;strong&gt;Markdown&lt;/strong&gt; description&lt;/p&gt;&quot;,<br/>
     *   &quot;description_format&quot;: &quot;html&quot;<br/>
     *   &quot;commonmark_description&quot;: &quot;\*\*Markdown\*\* description&quot;,<br/>
     *   &quot;expected_results&quot;: &quot;&lt;strong&gt;Markdown&lt;/strong&gt; expected results&quot;,<br/>
     *   &quot;expected_results_format&quot;: &quot;html&quot;,<br/>
     *   &quot;commonmark_expected_results&quot;: &quot;\*\*Markdown\*\* expected results&quot;,<br/>
     *   "rank": 1<br/>
     * }</code></pre>
     *
     * @url GET {id}
     *
     * @param int $id Id of the definition
     *
     * @return DefinitionRepresentation
     *
     * @throws RestException 403
     */
    protected function getId(int $id)
    {
        $user       = $this->user_manager->getCurrentUser();
        $definition = $this->testmanagement_artifact_factory->getArtifactByIdUserCanView($user, $id);
        if ($definition === null) {
            throw new RestException(404, 'The test definition does not exist or is not visible');
        }

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $definition->getTracker()->getProject()
        );

        $changeset = $definition->getLastChangeset();

        if (! $this->conformance_validator->isArtifactADefinition($definition)) {
            throw new RestException(400);
        }

        $representation = $this->definition_representation_builder->getDefinitionRepresentation($user, $definition, $changeset);
        return $representation;
    }
}
