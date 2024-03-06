<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Project\REST\v1;

use BackendLogger;
use EventManager;
use ForgeConfig;
use Luracast\Restler\RestException;
use MailPresenterFactory;
use PaginatedWikiPagesFactory;
use PFUser;
use Project;
use ProjectCreator;
use ProjectManager;
use ProjectUGroup;
use ProjectXMLImporter;
use Psr\Log\LoggerInterface;
use ServiceManager;
use TemplateRendererFactory;
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Label\Label;
use Tuleap\Label\PaginatedCollectionsOfLabelsBuilder;
use Tuleap\Label\REST\LabelRepresentation;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Layout\Logo\CachedCustomizedLogoDetector;
use Tuleap\Layout\Logo\CustomizedLogoDetector;
use Tuleap\Layout\Logo\FileContentComparator;
use Tuleap\Layout\ProjectSidebar\ProjectSidebarConfigRepresentation;
use Tuleap\Layout\ProjectSidebarToolsBuilder;
use Tuleap\NeverThrow\Fault;
use Tuleap\Project\Admin\Access\UserCanAccessProjectAdministrationVerifier;
use Tuleap\Project\Admin\Categories\CategoryCollectionConsistencyChecker;
use Tuleap\Project\Admin\DescriptionFields\ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Banner\BannerCreator;
use Tuleap\Project\Banner\BannerDao;
use Tuleap\Project\Banner\BannerPermissionsChecker;
use Tuleap\Project\Banner\BannerRemover;
use Tuleap\Project\Banner\BannerRetriever;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DeletedProjectStatusChangeException;
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Event\GetProjectWithTrackerAdministrationPermission;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Flags\ProjectFlagsDao;
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\Label\LabelsCurlyCoatedRetriever;
use Tuleap\Project\PaginatedProjects;
use Tuleap\Project\ProjectBackground\ProjectBackgroundDao;
use Tuleap\Project\ProjectBackground\ProjectBackgroundName;
use Tuleap\Project\ProjectBackground\ProjectBackgroundPermissionsChecker;
use Tuleap\Project\ProjectBackground\ProjectBackgroundUpdater;
use Tuleap\Project\ProjectBackground\UserCanModifyProjectBackgroundPermission;
use Tuleap\Project\ProjectCreationDataServiceFromXmlInheritor;
use Tuleap\Project\ProjectCreationNotifier;
use Tuleap\Project\ProjectStatusMapper;
use Tuleap\Project\Registration\ProjectDescriptionMandatoryException;
use Tuleap\Project\Registration\ProjectInvalidFullNameException;
use Tuleap\Project\Registration\ProjectInvalidShortNameException;
use Tuleap\Project\Registration\ProjectRegistrationBaseChecker;
use Tuleap\Project\Registration\ProjectRegistrationCheckerAggregator;
use Tuleap\Project\Registration\ProjectRegistrationCheckerBlockErrorSet;
use Tuleap\Project\Registration\ProjectRegistrationPermissionsChecker;
use Tuleap\Project\Registration\ProjectRegistrationRESTChecker;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\Template\InvalidTemplateException;
use Tuleap\Project\Registration\Template\NoTemplateProvidedFault;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\Upload\FileOngoingUploadDao;
use Tuleap\Project\Registration\Template\Upload\ProjectFileToUploadCreator;
use Tuleap\Project\REST\HeartbeatsRepresentation;
use Tuleap\Project\REST\MinimalUserGroupRepresentationWithAdditionalInformation;
use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\Project\REST\UserGroupAdditionalInformationEvent;
use Tuleap\Project\REST\v1\Project\ProjectFromArchiveRepresentation;
use Tuleap\Project\REST\v1\Project\PostProjectCreated;
use Tuleap\Project\REST\v1\Project\ProjectCreationDataPOSTProjectBuilder;
use Tuleap\Project\REST\v1\Project\ProjectRepresentationBuilder;
use Tuleap\Project\Status\CannotDeletedDefaultAdminProjectException;
use Tuleap\Project\Status\SwitchingBackToPendingException;
use Tuleap\Project\XML\InvalidXMLContentFault;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\Reference\REST\ReferenceRepresentationBuilder;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Event\ProjectGetSvn;
use Tuleap\REST\Event\ProjectOptionsSvn;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\I18NRestMultipleMessagesException;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\v1\PhpWikiPageRepresentation;
use Tuleap\Sanitizer\URISanitizer;
use Tuleap\User\ForgeUserGroupPermission\RestProjectManagementPermission;
use Tuleap\Widget\Event\GetProjectsWithCriteria;
use TuleapRegisterMail;
use UGroupManager;
use URLVerification;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;
use Valid_HTTPURI;
use Valid_LocalURI;
use Wiki;
use WikiDao;
use XMLImportHelper;

/**
 * Wrapper for project related REST methods
 */
class ProjectResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 50;

    /** @var LabelsCurlyCoatedRetriever */
    private $labels_retriever;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var EventManager */
    private $event_manager;

    /**
     * @var JsonDecoder
     */
    private $json_decoder;

    /**
     * @var User_ForgeUserGroupPermissionsManager
     */
    private $forge_ugroup_permissions_manager;

    /**
     * @var BannerCreator
     */
    private $banner_creator;

    /**
     * @var BannerPermissionsChecker
     */
    private $banner_permissions_checker;

    /**
     * @var BannerRetriever
     */
    private $banner_retriever;

    public function __construct()
    {
        $this->user_manager    = UserManager::instance();
        $this->project_manager = ProjectManager::instance();
        $this->ugroup_manager  = new UGroupManager();
        $this->json_decoder    = new JsonDecoder();
        $this->event_manager   = EventManager::instance();

        $this->forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );

        $label_dao = new LabelDao();

        $this->labels_retriever = new LabelsCurlyCoatedRetriever(
            new PaginatedCollectionsOfLabelsBuilder(),
            $label_dao
        );

        $banner_dao                       = new BannerDao();
        $this->banner_permissions_checker = new BannerPermissionsChecker();
        $this->banner_creator             = new BannerCreator($banner_dao);
        $this->banner_retriever           = new BannerRetriever($banner_dao);
    }

    /**
     * Creates a new Project
     *
     * Creates a new project in Tuleap.
     * <br>
     * <br>
     * Here is an example of JSON content to ask for a project creation with a Tuleap project template:
     * <br>
     * <pre>
     * {<br>
     * &nbsp; "shortname": "project-shortname",<br>
     * &nbsp; "description": "Project Description",<br>
     * &nbsp; "label": "Project Label",<br>
     * &nbsp; "is_public": true,<br>
     * &nbsp; "allow_restricted": true,<br>
     * &nbsp; "template_id": 100,<br>
     * &nbsp; "categories":[<br>
     * &nbsp;&nbsp; {<br>
     * &nbsp;&nbsp;&nbsp; "category_id": 4,<br>
     * &nbsp;&nbsp;&nbsp; "value_id": 5<br>
     * &nbsp;&nbsp; }<br>
     * &nbsp;],<br>
     * &nbsp; "fields": [<br>
     * &nbsp;&nbsp; {<br>
     * &nbsp;&nbsp;&nbsp; "field_id": 1,<br>
     * &nbsp;&nbsp;&nbsp; "value": "Project custom field value"<br>
     * &nbsp;&nbsp; }<br>
     * &nbsp;]<br>
     * }<br>
     * </pre>
     *
     * Here is an example of JSON content to ask for a project creation with an XML template:
     * <br>
     * <pre>
     * {<br>
     * &nbsp; "shortname": "project-shortname",<br>
     * &nbsp; "description": "Project Description",<br>
     * &nbsp; "label": "Project Label",<br>
     * &nbsp; "is_public": true,<br>
     * &nbsp; "allow_restricted": true,<br>
     * &nbsp; "xml_template_name": "empty",<br>
     * &nbsp; "categories":[<br>
     * &nbsp;&nbsp; {<br>
     * &nbsp;&nbsp;&nbsp; "category_id": 4,<br>
     * &nbsp;&nbsp;&nbsp; "value_id": 5<br>
     * &nbsp;&nbsp; }<br>
     * &nbsp;],<br>
     * &nbsp; "fields": [<br>
     * &nbsp;&nbsp; {<br>
     * &nbsp;&nbsp;&nbsp; "field_id": 1,<br>
     * &nbsp;&nbsp;&nbsp; "value": "Project custom field value"<br>
     * &nbsp;&nbsp; }<br>
     * &nbsp;]<br>
     * }<br>
     * </pre>
     *
     * If both template_id and xml_template_name are provided, only template_id will be taken into account.
     * <br>
     * <br>
     *
     * To see if your project creation data are correctly set, you can use the ?dry_run=true option.
     * <br>
     * All errors (if found) will be returned at once.
     *
     * @url    POST
     * @status 201
     *
     * @param ProjectPostRepresentation $post_representation {@from body}
     * @param bool $dry_run {@from query} {@required false}
     *
     *
     * @throws RestException 204
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 429
     */
    protected function post(ProjectPostRepresentation $post_representation, bool $dry_run = false): ProjectRepresentation|ProjectFromArchiveRepresentation
    {
        $this->options();
        $this->checkAccess();

        $user = $this->user_manager->getCurrentUser();

        try {
            $creation_data_post_project_builder = new ProjectCreationDataPOSTProjectBuilder(
                $this->project_manager,
                TemplateFactory::build(),
                new XMLFileContentRetriever(),
                ServiceManager::instance(),
                new ProjectCreationDataServiceFromXmlInheritor(
                    ServiceManager::instance(),
                ),
                $this->getBackendLogger(),
                $this->getURLVerification()
            );

            $creation_data = $creation_data_post_project_builder->buildProjectCreationDataFromPOSTRepresentation(
                $post_representation,
                $user
            );
        } catch (InvalidTemplateException $exception) {
            if ($dry_run) {
                throw new I18NRestMultipleMessagesException(400, [$exception->getI18NMessage()]);
            }
            throw new RestException(400, $exception->getMessage());
        } catch (\XML_ParseException $exception) {
            $logger = \Tuleap\REST\RESTLogger::getLogger();
            $logger->error(sprintf('POST /projects (%s): %s', $post_representation->shortname, $exception->getMessage()));
            $logger->debug($exception->getIndentedXml());
            foreach ($exception->getErrors() as $error) {
                $logger->error($error->getMessage() . ' (Type: ' . $error->getType() . ') Line: ' . $error->getLine() . ' Column: ' . $error->getColumn());
                $logger->error('Error L' . $error->getLine() . ': ' . $exception->getSourceXMLForError($error));
            }
            throw new RestException(500, 'Invalid XML. Server side error');
        }

        if ($creation_data === null) {
            throw new RestException(
                400,
                "Impossible to build Project data creation with the provided JSON content."
            );
        }

        if ($dry_run === true) {
            $checker = new ProjectRegistrationCheckerBlockErrorSet(
                new ProjectRegistrationPermissionsChecker(
                    new ProjectRegistrationUserPermissionChecker(
                        new \ProjectDao()
                    ),
                ),
                new ProjectRegistrationCheckerAggregator(
                    new ProjectRegistrationBaseChecker(
                        new \Rule_ProjectName(),
                        new \Rule_ProjectFullName(),
                    ),
                    new ProjectRegistrationRESTChecker(
                        new DefaultProjectVisibilityRetriever(),
                        new CategoryCollectionConsistencyChecker(
                            new \TroveCatFactory(new \TroveCatDao())
                        ),
                        new ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker(
                            new DescriptionFieldsFactory(
                                new DescriptionFieldsDao()
                            )
                        )
                    )
                )
            );

            $errors_collection = $checker->collectAllErrorsForProjectRegistration($user, $creation_data);
            if (count($errors_collection->getErrors()) > 0) {
                throw new I18NRestMultipleMessagesException(400, $errors_collection->getI18nErrorsMessages());
            }

            throw new RestException(204);
        }

        try {
            return $this->getRestProjectCreator()->create($post_representation, $creation_data, $user)
                ->match(
                    function (PostProjectCreated $created): ProjectRepresentation|ProjectFromArchiveRepresentation {
                        $project = $created->getProject();
                        if ($project !== null) {
                            $this->getProjectCreationNotifier()->notifySiteAdmin($project);
                            return $created->getProjectRepresentation();
                        }

                        $project_from_archive_representation = $created->getProjectFromArchiveRepresentation();
                        if ($project_from_archive_representation !== null) {
                            return $project_from_archive_representation;
                        }

                        throw new RestException(500, "Failed to return a representation");
                    },
                    function (Fault $fault): void {
                        if ($fault instanceof InvalidXMLContentFault || $fault instanceof NoTemplateProvidedFault) {
                            throw new RestException(400, (string) $fault);
                        }

                        throw new RestException(500, (string) $fault);
                    }
                );
        } catch (ProjectInvalidShortNameException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (ProjectInvalidFullNameException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (ProjectDescriptionMandatoryException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function getProjectCreationNotifier(): ProjectCreationNotifier
    {
        return new ProjectCreationNotifier(
            new TuleapRegisterMail(
                new MailPresenterFactory(),
                TemplateRendererFactory::build()->getRenderer(
                    ForgeConfig::get('codendi_dir') . '/src/templates/mail/'
                ),
                UserManager::instance(),
                new LocaleSwitcher(),
                "mail-project-register-admin"
            ),
            $this->getBackendLogger()
        );
    }

    /**
     * Get projects
     *
     * Get the public projects and the projects the current user is member of.
     *
     * <p>
     * If current user is site administrator, then returns all active projects.
     * <br>
     * <br>
     * ?query is optional. When filled, it is a json object with either:
     * <ul>
     *   <li>a property "shorname" to search on shortname with exact match.
     *     Example: <pre>{"shortname": "guinea-pig"}</pre>
     *   </li>
     *   <li>a property "is_member_of" to search projects the current user is member of.
     *     Example: <pre>{"is_member_of": true}</pre>
     *   </li>
     *   <li>a property "is_admin_of" to search projects the current user is admin of.
     *     Example: <pre>{"is_admin_of": true}</pre>
     *   </li>
     *   <li>a property "is_tracker_admin" to search projects the current user is administrator of at least one tracker.
     *     Example: <pre>{"is_tracker_admin": true}</pre>
     *   </li>
     *   <li>a property "with_status" to search projects the current user is member of with a specific status.
     *     Example: <pre>{"with_status": "deleted"}</pre>
     *   </li>
     *   <li>a property "with_time_tracking" to search projects with enabled timetracking.
     *     Example: <pre>{"with_time_tracking": true}</pre>
     *   </li>
     * </ul>
     * </p>
     *
     * <p>
     *   <strong>/!\</strong> Please note that {"is_member_of": false} is not supported and will result
     *   in a 400 Bad Request error.
     * </p>
     * <p>
     *   <strong>/!\</strong> Please note that {"is_tracker_admin": false} is not supported and will result
     *   in a 400 Bad Request error.
     * </p>
     * <p>
     *   <strong>/!\</strong> Please note that querying with { "with_status" } will throw a 403 Forbidden Error
     *   if you are not member of the REST project management delegation.
     * </p>
     *
     * @url    GET
     * @access hybrid
     * @oauth2-scope read:project
     *
     * @param int    $limit  Number of elements displayed per page {@min 0} {@max 50}
     * @param int    $offset Position of the first element to display {@min 0}
     * @param string $query  JSON object of search criteria properties {@from path}
     *
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return array {@type Tuleap\Project\REST\ProjectRepresentation}
     */
    public function get($limit = 10, $offset = 0, $query = '')
    {
        $this->checkAccess();

        $query = trim($query);
        $user  = $this->user_manager->getCurrentUser();

        if ($this->json_decoder->looksLikeJson($query)) {
            $paginated_projects = $this->getMyAndPublicProjectsFromExactMatch($query, $user, $offset, $limit);
        } elseif (! empty($query)) {
            throw new RestException(400, 'query parameter must be a json object or empty');
        } else {
            $paginated_projects = $this->getMyAndPublicProjects($user, $offset, $limit);
        }

        return $this->sendProjectRepresentations($paginated_projects, $limit, $offset, $user);
    }

    private function sendProjectRepresentations(
        PaginatedProjects $paginated_projects,
        $limit,
        $offset,
        PFUser $current_user,
    ) {
        $project_representations = [];
        foreach ($paginated_projects->getProjects() as $project) {
            $project_representations[] = $this->getProjectRepresentation($project, $current_user);
        }

        $this->sendAllowHeadersForProject();
        $this->sendPaginationHeaders($limit, $offset, $paginated_projects->getTotalSize());

        return $project_representations;
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        $this->sendAllowHeadersForProject();
    }

    /**
     * Get projects which I am member of, public projects (if I'm not a member of
     * a project but I'm in a static group of this project, this one will not be
     * retrieve)
     *
     * @return Tuleap\Project\PaginatedProjects
     */
    private function getMyAndPublicProjects(PFUser $user, $offset, $limit)
    {
        return $this->project_manager->getMyAndPublicProjectsForREST($user, $offset, $limit);
    }

    /**
     * @param string $query
     * @param int    $offset
     * @param int    $limit
     *
     * @return PaginatedProjects
     *
     * @throws RestException
     * @throws \Tuleap\REST\Exceptions\InvalidJsonException
     */
    private function getMyAndPublicProjectsFromExactMatch($query, PFUser $user, $offset, $limit)
    {
        $json_query = $this->json_decoder->decodeAsAnArray('query', $query);
        $checker    = new GetProjectsQueryChecker($this->event_manager);
        $checker->checkQuery($json_query, $this->isUserARestProjectManager($user));

        if (isset($json_query['shortname'])) {
            return $this->project_manager->getMyAndPublicProjectsForRESTByShortname(
                $json_query['shortname'],
                $user,
                $offset,
                $limit
            );
        } elseif (isset($json_query['is_tracker_admin'])) {
            $event = new GetProjectWithTrackerAdministrationPermission($user, $limit, $offset);
            $this->event_manager->processEvent($event);

            return $event->getPaginatedProjects();
        } elseif (isset($json_query['with_status'])) {
            $with_status = $json_query['with_status'];
            return $this->project_manager->getProjectsWithStatusForREST(
                ProjectStatusMapper::getProjectStatusFlagFromStatusLabel($with_status),
                $user,
                $offset,
                $limit
            );
        } elseif (isset($json_query['is_member_of'])) {
            return $this->project_manager->getMyProjectsForREST(
                $user,
                $offset,
                $limit
            );
        } elseif (isset($json_query['is_admin_of'])) {
            return $this->project_manager->getProjectICanAdminForREST(
                $user,
                $offset,
                $limit
            );
        } else {
            $get_projects = new GetProjectsWithCriteria($json_query, $limit, $offset);
            $this->event_manager->processEvent($get_projects);

            return $get_projects->getProjectsWithCriteria();
        }
    }

    /**
     * Get project
     *
     * Get the definition of a given project
     *
     * @url    GET {id}
     * @access hybrid
     * @oauth2-scope read:project
     *
     * @param int $id Id of the project
     *
     *
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return ProjectRepresentation
     */
    public function getId($id)
    {
        $this->checkAccess();

        $this->sendAllowHeadersForProject();

        $user = $this->user_manager->getCurrentUser();

        if ($this->isUserDelegatedRestProjectManager($user)) {
            return $this->getProjectRepresentation(
                $this->getProjectForRestProjectManager($id),
                $user
            );
        }

        return $this->getProjectRepresentation($this->getProjectForUser($id), $user);
    }

    /**
     * @url    OPTIONS {id}
     *
     * @param int $id Id of the project
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function optionsId($id)
    {
        $this->sendAllowHeadersForProject();
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return Project
     */
    private function getProjectForUser($id)
    {
        $project = $this->project_manager->getProject($id);
        $user    = $this->user_manager->getCurrentUser();

        ProjectAuthorization::userCanAccessProject($user, $project, $this->getURLVerification());

        return $project;
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return Project
     */
    private function getProjectForRestProjectManager($project_id)
    {
        $project = $this->project_manager->getProject($project_id);

        if ($project->isError()) {
            throw new RestException(404, "Project does not exist");
        }

        return $project;
    }

    private function getProjectRepresentation(Project $project, PFUser $current_user): ProjectRepresentation
    {
        $builder = new ProjectRepresentationBuilder(EventManager::instance(), $this->getDescriptionFieldsFactory());
        return $builder->build($project, $current_user);
    }

    /**
     * Get heartbeats
     *
     * Get the latest activities of a given project
     *
     * @url    GET {id}/heartbeats
     * @access hybrid
     *
     * @param int $id Id of the project
     *
     * @return HeartbeatsRepresentation {@type HeartbeatsRepresentation}
     */
    public function getHeartbeats($id)
    {
        $this->checkAccess();

        $project = $this->getProjectForUser($id);
        $user    = $this->user_manager->getCurrentUser();
        $event   = new HeartbeatsEntryCollection($project, $user);
        $this->event_manager->processEvent($event);

        $heartbeats = HeartbeatsRepresentation::build($event);

        $this->sendAllowHeadersForHeartBeat();

        return $heartbeats;
    }

    /**
     * @url OPTIONS {id}/heartbeats
     *
     * @param int $id Id of the project
     */
    public function optionsHeartbeats($id)
    {
        $this->sendAllowHeadersForHeartBeat();
    }

    /**
     * Get labels
     *
     * Get labels used by the project
     *
     * <p><code>query</code> parameter allows you to search for a particular label with wildcard</p>
     *
     * @url    GET {id}/labels
     * @access hybrid
     * @oauth2-scope read:project
     *
     * @param int    $id     Id of the project
     * @param string $query  Search particular label, if not used, returns all project labels
     * @param int    $limit  Number of elements displayed per page {@from path} {@min 1} {@max 50}
     * @param int    $offset Position of the first element to display {@from path} {@min 0}
     *
     * @return array {@type LabelRepresentation}
     */
    public function getLabels($id, $query = '', $limit = self::MAX_LIMIT, $offset = 0)
    {
        $this->checkAccess();

        $project               = $this->getProjectForUser($id);
        $collection            = $this->labels_retriever->getPaginatedMatchingLabelsForProject(
            $project,
            $query,
            $limit,
            $offset
        );
        $labels_representation = array_map(
            function (Label $label) {
                $representation = new LabelRepresentation();
                $representation->build($label);

                return $representation;
            },
            $collection->getLabels()
        );

        $this->sendAllowHeadersForLabels();
        Header::sendPaginationHeaders($limit, $offset, $collection->getTotalSize(), self::MAX_LIMIT);

        return [
            'labels' => $labels_representation,
        ];
    }

    /**
     * @url OPTIONS {id}/labels
     *
     * @param int $id Id of the project
     */
    public function optionsLabels($id)
    {
        $this->sendAllowHeadersForLabels();
    }

    /**
     * Project partial update
     *
     * Partial update of a Project
     * <br/>
     *
     * <br/>
     * This partial update allows a REST project manager to toggle the status of a given project from active to suspended and conversely.
     * <br/>
     *
     * <br/>
     * Example:
     * <pre>
     * {
     *   "status": "suspended"
     * }
     * </pre>
     *
     * @url    PATCH {id}
     * @access hybrid
     * @status 200
     *
     * @param int                        $id             Id of the project
     * @param PATCHProjectRepresentation $patch_resource {@from body} {@type Tuleap\Project\REST\v1\PATCHProjectRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function patchProject($id, PATCHProjectRepresentation $patch_resource)
    {
        $this->checkAccess();

        $user = $this->user_manager->getCurrentUser();

        if (! $this->isUserARestProjectManager($user)) {
            throw new RestException(403, 'You are not allowed to change the status of a project');
        }

        if ($id > 0 && $id <= Project::DEFAULT_TEMPLATE_PROJECT_ID) {
            throw new RestException(403, 'You are not allowed to change the status of a system project.');
        }

        $project = $this->getProjectForRestProjectManager($id);

        try {
            $this->project_manager->updateStatus(
                $project,
                ProjectStatusMapper::getProjectStatusFlagFromStatusLabel($patch_resource->status)
            );
        } catch (DeletedProjectStatusChangeException $exception) {
            throw new RestException(403, 'A deleted project cannot be reactivated.');
        } catch (SwitchingBackToPendingException $exception) {
            throw new RestException(403, 'An already activated project cannot be reset to pending.');
        } catch (CannotDeletedDefaultAdminProjectException $exception) {
            throw new RestException(403, 'Cannot delete the default Administration project.');
        }

        $this->sendAllowHeadersForProject();
    }

    /**
     * @url OPTIONS {id}/user_groups
     *
     * @param int $id Id of the project
     */
    public function optionsUserGroups($id)
    {
        $this->sendAllowHeadersForUserGroups();
    }

    /**
     * Get user_groups
     *
     * Get the user_groups of a given project.
     * <br/>
     * <strong>query</strong> is optional with json format:
     * <pre>{"with_system_user_groups": true}</pre>
     * With "system_user_groups = true", system user groups may be returned (if allowed by platform).
     * Otherwise, these groupes are excluded.
     *
     * @url GET {id}/user_groups
     * @access hybrid
     * @oauth2-scope read:project
     *
     * @param int    $id    Id of the project
     * @param string $query JSON object of filtering options {@from path} {@required false}
     *
     * @return array {@type Tuleap\Project\REST\v1\UserGroupRepresentation}
     * @throws I18NRestException 400
     * @throws \Tuleap\REST\Exceptions\InvalidJsonException
     */
    public function getUserGroups($id, $query = '')
    {
        $project = $this->getProjectForUser($id);
        $this->userCanSeeUserGroups($id);

        $queryRepresentation = $this->getUserGroupQueryParser()->parse($query);
        if ($queryRepresentation->isWithSystemUserGroups()) {
            $ugroups = $this->ugroup_manager->getAvailableUGroups($project);
        } else {
            $ugroups = $this->ugroup_manager->getUGroups($project, ProjectUGroup::SYSTEM_USER_GROUPS);
        }
        $user_groups = $this->getUserGroupsRepresentations($ugroups, $id);

        $this->sendAllowHeadersForUserGroups();

        return $user_groups;
    }

    private function getUserGroupsRepresentations(array $ugroups, $project_id)
    {
        $user_groups = [];

        foreach ($ugroups as $ugroup) {
            $event          = $this->event_manager->dispatch(
                new UserGroupAdditionalInformationEvent($ugroup, $this->user_manager->getCurrentUser())
            );
            $representation = new MinimalUserGroupRepresentationWithAdditionalInformation(
                (int) $project_id,
                $ugroup,
                $event->additional_information
            );
            $user_groups[]  = $representation;
        }

        return $user_groups;
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return bool
     */
    private function userCanSeeUserGroups($project_id)
    {
        $project = $this->project_manager->getProject($project_id);
        $user    = $this->user_manager->getCurrentUser();
        ProjectAuthorization::userCanAccessProject($user, $project, $this->getURLVerification());

        return true;
    }

    /**
     * @url    OPTIONS {id}/svn
     *
     * @param int $id Id of the project
     *
     * @throws RestException 404
     */
    public function optionsSvn($id)
    {
        $event = new ProjectOptionsSvn();

        $this->event_manager->processEvent($event);

        if ($event->isPluginActivated()) {
            $this->sendAllowHeadersForSvn();
        } else {
            throw new RestException(404, 'SVN plugin not activated');
        }
    }

    /**
     * Get svn
     *
     * Get info about project SVN repositories
     *
     * <br>
     * <pre>
     * "repositories": [{<br>
     *   &nbsp;"id" : 90,<br>
     *   &nbsp;"project": {...},<br>
     *   &nbsp;"uri": "svn/90",<br>
     *   &nbsp;"name": "repo",<br>
     *  }<br>
     * ...<br>
     * ]
     * </pre>
     *
     * <br/>
     * <br/>
     * ?query must be a json object to search on name with exact match: {"name": "repository01"}
     *
     * @url    GET {id}/svn
     * @access hybrid
     *
     * @param int    $id     Id of the project
     * @param string $query  Optional search string in json format {@from query}
     * @param int    $limit  Number of elements displayed per page {@from path}
     * @param int    $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\REST\v1\SvnRepositoryRepresentationBase}
     *
     * @throws RestException 404
     */
    public function getSvn($id, $query = '', $limit = 10, $offset = 0)
    {
        $this->checkAccess();

        $project                = $this->getProjectForUser($id);
        $repository_name_filter = $this->getRepositoryNameFromQuery($query);

        $event = new ProjectGetSvn($project, $repository_name_filter, 'v1', $limit, $offset);

        $this->event_manager->processEvent($event);

        if (! $event->isPluginActivated()) {
            throw new RestException(404, 'SVN plugin not activated');
        }

        $this->sendAllowHeadersForSvn();
        $this->sendPaginationHeaders($limit, $offset, $event->getTotalRepositories());

        return ['repositories' => $event->getRepositoriesRepresentations()];
    }

    /**
     * Put banner
     *
     * Put the banner message to be displayed
     *
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"message": "A message to be displayed on the project"<br>
     *  }<br>
     * </pre>
     *
     * @url PUT {id}/banner
     *
     * @param int $id id of the project
     * @param BannerRepresentation $banner banner to be displayed {@from body}
     * @throws RestException
     */
    protected function putBanner($id, BannerRepresentation $banner): void
    {
        $this->checkAccess();

        if (empty($banner->message)) {
            throw new RestException(400, 'Message cannot be empty');
        }

        $project           = $this->getProjectForUser($id);
        $update_permission = $this->banner_permissions_checker->getEditBannerPermission(
            $this->user_manager->getCurrentUser(),
            $project
        );

        if (! $update_permission) {
            throw new RestException(403);
        }

        $this->banner_creator->addBanner(
            $update_permission,
            $banner->message
        );
    }

    /**
     * Delete the banner message
     *
     * @url DELETE {id}/banner
     *
     * @param int $id id of the project
     * @throws RestException 403
     */
    protected function deleteBanner(int $id): void
    {
        $project           = $this->getProjectForUser($id);
        $delete_permission = $this->banner_permissions_checker->getEditBannerPermission(
            $this->user_manager->getCurrentUser(),
            $project
        );

        if ($delete_permission === null) {
            throw new RestException(403);
        }

        $banner_remover = new BannerRemover(new BannerDao());
        $banner_remover->deleteBanner($delete_permission);
    }

     /**
      * Get banner
      *
      * Get the banner
      *
      * @url GET {id}/banner
      * @access hybrid
      * @oauth2-scope read:project
      *
      * @param int $id id of the project
      * @throws RestException
      */
    public function getBanner($id): BannerRepresentation
    {
        $this->checkAccess();

        $project = $this->getProjectForUser($id);
        $banner  = $this->banner_retriever->getBannerForProject($project);

        if (! $banner) {
            throw new RestException(404, 'No banner set for this project');
        }

        return new BannerRepresentation($banner);
    }

    /**
     * Put a header background to the project
     *
     * @url PUT {id}/header_background
     *
     * @param int $id id of the project
     * @param HeaderBackgroundRepresentation $header_background_representation Header background to be displayed {@from body}
     * @throws RestException
     */
    protected function putBackgroundHeader(int $id, HeaderBackgroundRepresentation $header_background_representation): void
    {
        $this->checkAccess();

        (new ProjectBackgroundUpdater(new ProjectBackgroundDao()))->updateProjectBackground(
            $this->getModifyHeaderBackgroundPermission($id),
            ProjectBackgroundName::fromIdentifier($header_background_representation->identifier)
        );
    }

    /**
     * Delete the header background
     *
     * @url DELETE {id}/header_background
     *
     * @param int $id id of the project
     * @throws RestException
     */
    protected function deleteHeaderBackground(int $id): void
    {
        $this->checkAccess();

        (new ProjectBackgroundUpdater(new ProjectBackgroundDao()))->deleteProjectBackground(
            $this->getModifyHeaderBackgroundPermission($id),
        );
    }

    private function getModifyHeaderBackgroundPermission(int $project_id): UserCanModifyProjectBackgroundPermission
    {
        $project = $this->getProjectForUser($project_id);

        $update_permission = (new ProjectBackgroundPermissionsChecker())->getModifyProjectBackgroundPermission(
            $project,
            $this->user_manager->getCurrentUser()
        );

        if ($update_permission === null) {
            throw new RestException(403);
        }

        return $update_permission;
    }

    private function getRepositoryNameFromQuery($query)
    {
        if ($query === '') {
            return '';
        }

        if ($query && ! $this->json_decoder->looksLikeJson($query)) {
            throw new RestException(400, 'Query must be in Json');
        }

        $json_query = $this->json_decoder->decodeAsAnArray('query', $query);
        if (! isset($json_query['name'])) {
            throw new RestException(400, 'You can only search on "name"');
        }

        return $json_query['name'];
    }

    /**
     * @url OPTIONS {id}/phpwiki
     *
     * @param int $id Id of the project
     */
    public function optionsWiki($id)
    {
        $this->sendAllowHeadersForWiki();
    }

    /**
     * Get PhpWiki pages
     *
     * Get info about project non empty PhpWiki pages.
     *
     * @url    GET {id}/phpwiki
     *
     * @access hybrid
     *
     * @param int    $id       Id of the project
     * @param int    $limit    Number of elements displayed per page {@from path}
     * @param int    $offset   Position of the first element to display {@from path}
     * @param string $pagename Part of the pagename or the full pagename to search {@from path}
     *
     * @return array {@type Tuleap\REST\v1\PhpWikiPageRepresentation}
     */
    public function getPhpWiki($id, $limit = 10, $offset = 0, $pagename = '')
    {
        $this->checkAccess();
        $this->getProjectForUser($id);

        $current_user = UserManager::instance()->getCurrentUser();

        $this->userCanAccessPhpWikiService($current_user, $id);

        $wiki_pages = [
            'pages' => [],
        ];

        $wiki_pages_factory = new PaginatedWikiPagesFactory(new WikiDao());
        $all_pages          = $wiki_pages_factory->getPaginatedUserPages(
            $current_user,
            $id,
            $limit,
            $offset,
            $pagename
        );

        foreach ($all_pages->getPages() as $page) {
            $representation = PhpWikiPageRepresentation::build($page);

            $wiki_pages['pages'][] = $representation;
        }

        $this->sendAllowHeadersForWiki();
        $this->sendPaginationHeaders($limit, $offset, $all_pages->getTotalSize());

        return $wiki_pages;
    }

    /**
     * @url OPTIONS {id}/project_services
     *
     * @param int $id Id of the project
     */
    public function optionServices(int $id)
    {
        $this->sendAllowHeadersForProjectServices();
    }

    /**
     * Get services
     *
     * Get all services that are available in the projects.
     *
     * @url GET {id}/project_services
     * @access hybrid
     * @oauth2-scope read:project
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path} {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@from path} {@min 0}
     *
     * @return array {@type Tuleap\Project\REST\v1\ServiceRepresentation}
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getServices(int $id, int $limit = 10, int $offset = 0): array
    {
        $this->checkAccess();
        $project = $this->getProjectForUser($id);

        $current_user = $this->user_manager->getCurrentUser();

        $this->sendAllowHeadersForProjectServices();

        $builder  = new ServiceRepresentationCollectionBuilder(ServiceManager::instance());
        $services = $builder->getServiceRepresentationCollectionForProject($project, $current_user);

        $this->sendPaginationHeaders($limit, $offset, count($services));

        return array_slice($services, $offset, $limit);
    }

    private function sendAllowHeadersForProjectServices()
    {
        Header::allowOptionsGet();
    }

    /**
     * @url OPTIONS {id}/3rd_party_integration_data
     *
     * @param int $id ID of the project
     */
    public function optionsThirdPartyIntegrationData(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get data needed for third party integration
     *
     * @url GET {id}/3rd_party_integration_data
     * @access hybrid
     * @oauth2-scope read:project
     *
     * @param int $id ID of the project
     * @param string $currently_active_service The currently active service name
     *
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getThirdPartyIntegrationData(int $id, string $currently_active_service = ''): ThirdPartyIntegrationDataRepresentation
    {
        $this->optionsThirdPartyIntegrationData($id);
        $this->checkAccess();
        $project = $this->getProjectForUser($id);

        $current_user  = $this->user_manager->getCurrentUserWithLoggedInInformation();
        $event_manager = EventManager::instance();

        return (new LocaleSwitcher())->setLocaleForSpecificExecutionContext(
            $current_user->user->getLocale(),
            function () use ($project, $current_user, $event_manager, $currently_active_service): ThirdPartyIntegrationDataRepresentation {
                $config = ProjectSidebarConfigRepresentation::build(
                    $project,
                    $current_user,
                    new BannerRetriever(new BannerDao()),
                    new ProjectFlagsBuilder(new ProjectFlagsDao()),
                    $event_manager,
                    new UserCanAccessProjectAdministrationVerifier(new MembershipDelegationDao()),
                    new FlavorFinderFromFilePresence(),
                    new CachedCustomizedLogoDetector(
                        new CustomizedLogoDetector(new \LogoRetriever(), new FileContentComparator()),
                        \BackendLogger::getDefaultLogger(),
                    ),
                    new GlyphFinder($event_manager),
                    new ProjectSidebarToolsBuilder(
                        $event_manager,
                        ProjectManager::instance(),
                        new URISanitizer(new Valid_HTTPURI(), new Valid_LocalURI())
                    ),
                    $currently_active_service,
                    null,
                );

                $reference_representation_builder = new ReferenceRepresentationBuilder(\ReferenceManager::instance(), $event_manager);

                return new ThirdPartyIntegrationDataRepresentation(
                    ProjectSidebarDataRepresentation::fromConfigRepresentationAndUser($config, $current_user->user),
                    ThirdPartyIntegrationStylesRepresentation::fromUser($current_user->user),
                    $reference_representation_builder->getProjectReferences($project),
                );
            }
        );
    }

    private function userCanAccessPhpWikiService(PFUser $user, $project_id)
    {
        $wiki_service = new Wiki($project_id);

        if (! $wiki_service->isAutorized($user->getId())) {
            throw new RestException(403, 'You are not allowed to access to PhpWiki service');
        }
    }

    private function sendAllowHeadersForProject()
    {
        Header::allowOptionsGetPostPatch();
    }

    private function sendAllowHeadersForSvn()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForLabels()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForUserGroups()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForWiki()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForHeartBeat()
    {
        Header::allowOptionsGet();
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function isUserDelegatedRestProjectManager(PFUser $user)
    {
        return $this->forge_ugroup_permissions_manager->doesUserHavePermission(
            $user,
            new RestProjectManagementPermission()
        );
    }

    /**
     * @return bool
     */
    private function isUserARestProjectManager(PFUser $user)
    {
        return $user->isSuperUser() || $this->isUserDelegatedRestProjectManager($user);
    }

    private function getUserGroupQueryParser(): UserGroupQueryParameterParser
    {
        return new UserGroupQueryParameterParser($this->json_decoder);
    }

    private function getBackendLogger(): LoggerInterface
    {
        return BackendLogger::getDefaultLogger();
    }

    private function getDescriptionFieldsFactory(): DescriptionFieldsFactory
    {
        return new DescriptionFieldsFactory(new DescriptionFieldsDao());
    }

    private function getRestProjectCreator(): RestProjectCreator
    {
        return new RestProjectCreator(
            ProjectCreator::buildSelfRegularValidation(),
            ProjectCreator::buildSelfDelayActivation(),
            ProjectXMLImporter::build(
                new XMLImportHelper(UserManager::instance()),
                ProjectCreator::buildSelfRegularValidation()
            ),
            TemplateFactory::build(),
            new ProjectFileToUploadCreator(new FileOngoingUploadDao()),
            new ProjectRepresentationBuilder(EventManager::instance(), $this->getDescriptionFieldsFactory()),
            new ProjectRegistrationCheckerAggregator(
                new ProjectRegistrationBaseChecker(
                    new \Rule_ProjectName(),
                    new \Rule_ProjectFullName(),
                ),
                new ProjectRegistrationRESTChecker(
                    new DefaultProjectVisibilityRetriever(),
                    new CategoryCollectionConsistencyChecker(
                        new \TroveCatFactory(new \TroveCatDao())
                    ),
                    new ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker(
                        new DescriptionFieldsFactory(
                            new DescriptionFieldsDao()
                        )
                    )
                )
            )
        );
    }

    private function getURLVerification(): URLVerification
    {
        return new URLVerification();
    }
}
