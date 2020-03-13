<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\ProjectCreationDataServiceFromXmlInheritor;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;

class ProjectCreationData //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    /**
     * @var bool
     */
    private $is_built_from_xml = false;
    private $logger;
    /**
     * @var DefaultProjectVisibilityRetriever
     */
    private $default_project_visibility_retriever;
    private $data_services;
    private $data_fields;
    private $full_name;
    private $unix_name;
    private $is_test;
    private $is_template;
    private $short_description;
    /**
     * @var TemplateFromProjectForCreation
     */
    private $built_from_template;
    private $trove_data;
    private $inherit_from_template = true;
    private $access;

    public function __construct(DefaultProjectVisibilityRetriever $default_project_visibility_retriever, ?\Psr\Log\LoggerInterface $logger = null)
    {
        if ($logger === null) {
            $this->logger = new \Psr\Log\NullLogger();
        } else {
            $this->logger = new WrapperLogger($logger, self::class);
        }

        $this->default_project_visibility_retriever = $default_project_visibility_retriever;
    }

    /**
     * Returns true if the data should be inherited from template (in DB)
     *
     * This is mostly useful for XML import where "the true" come from XML
     * and not from the predefined template.
     *
     * @return bool
     */
    public function projectShouldInheritFromTemplate()
    {
        return $this->inherit_from_template;
    }

    public function getFullName()
    {
        return $this->full_name;
    }

    public function setFullName($name)
    {
        $this->full_name = $name;
    }

    public function getUnixName()
    {
        return $this->unix_name;
    }

    public function setUnixName($name)
    {
        $this->unix_name = $name;
    }

    public function getAccess()
    {
        return $this->access;
    }

    public function isTest()
    {
        return $this->is_test;
    }

    public function isTemplate()
    {
        return $this->is_template;
    }

    public function setIsTemplate()
    {
        $this->is_template = true;
    }

    public function getShortDescription()
    {
        return $this->short_description;
    }

    public function getBuiltFromTemplateProject(): TemplateFromProjectForCreation
    {
        return $this->built_from_template;
    }

    public function getTroveData()
    {
        return $this->trove_data;
    }

    /**
     * @param $group_desc_id int id of the description field to return
     * @return ?string the value of the field requested, null if the field isnt set
     */
    public function getField($group_desc_id)
    {
        if (!isset($this->data_fields['form_' . $group_desc_id])) {
            return null;
        }
        return $this->data_fields['form_' . $group_desc_id];
    }

    /**
     * @return array with:
     *     is_used => boolean telling if the service is used
     */
    public function getServiceInfo($service_id): ?array
    {
        return isset($this->data_services[$service_id]) ?
            $this->data_services[$service_id] :
            null;
    }

    /**
     * $data['project']['form_unix_name']
     * $data['project']['form_full_name']
     * $data['project']['form_short_description']
     * $data['project']['is_test']
     * $data['project']['is_public']
     * $data['project']['allow_restricted']
     * $data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]]
     * foreach($data['project']['trove'] as $root => $values);
     * $data['project']['services'][$arr['service_id']]['is_used'];
     */
    public static function buildFromFormArray(
        DefaultProjectVisibilityRetriever $default_project_visibility_retriever,
        TemplateFromProjectForCreation $template_from_project_for_creation,
        array $data
    ) {
        $instance = new ProjectCreationData($default_project_visibility_retriever);
        $instance->fromForm($template_from_project_for_creation, $data);
        return $instance;
    }

    private function fromForm(TemplateFromProjectForCreation $template_from_project_for_creation, array $data)
    {
        $project = isset($data['project']) ? $data['project'] : array();

        $this->unix_name           = isset($project['form_unix_name'])         ? $project['form_unix_name']         : null;
        $this->full_name           = isset($project['form_full_name'])         ? $project['form_full_name']         : null;
        $this->short_description   = isset($project['form_short_description']) ? $project['form_short_description'] : null;
        $this->built_from_template = $template_from_project_for_creation;
        $this->is_test             = isset($project['is_test'])                ? $project['is_test']                : null;
        $this->setAccessFromProjectData($project);
        $this->trove_data          = isset($project['trove']) ? $project['trove'] : [];
        $this->data_services       = isset($project['services'])               ? $project['services']               : array();
        $this->data_fields         = $project;
    }

    private function getAccessFromProjectArrayData(array $project)
    {
        if ((int) ForgeConfig::get(ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY) === 0) {
            return $this->default_project_visibility_retriever->getDefaultProjectVisibility();
        }

        if (! isset($project['is_public'])) {
            return $this->default_project_visibility_retriever->getDefaultProjectVisibility();
        }

        $is_public                       = (string) $project['is_public'] === '1';
        $are_restricted_enabled          = ForgeConfig::areRestrictedUsersAllowed();
        $should_project_allow_restricted = isset($project['allow_restricted']) && $project['allow_restricted'];

        if ($is_public) {
            if ($are_restricted_enabled && $should_project_allow_restricted) {
                return Project::ACCESS_PUBLIC_UNRESTRICTED;
            }
            return Project::ACCESS_PUBLIC;
        }

        if ($are_restricted_enabled && !$should_project_allow_restricted) {
            return Project::ACCESS_PRIVATE_WO_RESTRICTED;
        }

        return Project::ACCESS_PRIVATE;
    }

    public static function buildFromXML(
        SimpleXMLElement $xml,
        ?XML_RNGValidator $xml_validator = null,
        ?ServiceManager $service_manager = null,
        ?\Psr\Log\LoggerInterface $logger = null,
        ?DefaultProjectVisibilityRetriever $default_project_visibility_retriever = null,
        ?ExternalFieldsExtractor $external_fields_extractor = null
    ) {
        $default_project_visibility_retriever = $default_project_visibility_retriever ?? new DefaultProjectVisibilityRetriever();

        $instance = new ProjectCreationData($default_project_visibility_retriever, $logger);
        $instance->fromXML($xml, $xml_validator, $service_manager, $external_fields_extractor);
        return $instance;
    }

    private function fromXML(
        SimpleXMLElement $xml,
        ?XML_RNGValidator $xml_validator = null,
        ?ServiceManager $service_manager = null,
        ?ExternalFieldsExtractor $external_fields_extractor = null,
        ?ProjectCreationDataServiceFromXmlInheritor $service_inheritor = null
    ) {
        if (empty($xml_validator)) {
            $xml_validator = new XML_RNGValidator();
        }
        if (empty($service_manager)) {
            $service_manager = ServiceManager::instance();
        }
        if (empty($external_fields_extractor)) {
            $external_fields_extractor = new ExternalFieldsExtractor(new EventManager());
        }

        if (empty($service_inheritor)) {
            $service_inheritor = new ProjectCreationDataServiceFromXmlInheritor($service_manager);
        }

        $this->logger->debug("Start import from XML, validate RNG");
        $rng_path = realpath(dirname(__FILE__) . '/../xml/resources/project/project.rng');

        $partial_element = new SimpleXMLElement((string) $xml->asXML());
        $external_fields_extractor->extractExternalFieldFromProjectElement($partial_element);
        $xml_validator->validate($partial_element, $rng_path);
        $this->logger->debug("RNG validated, feed the data");

        $long_description_tagname = 'long-description';

        $attrs = $xml->attributes();
        $this->unix_name     = (string) $attrs['unix-name'];
        $this->full_name     = (string) $attrs['full-name'];
        $this->short_description   = (string) $attrs['description'];
        $this->built_from_template = TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate();
        $this->is_test       = (bool) false;
        $this->trove_data    = array();
        $this->data_services = array();
        $this->data_fields   = array(
            'form_101' => (string) $xml->$long_description_tagname
        );
        $this->is_built_from_xml = true;

        switch ($attrs['access']) {
            case 'unrestricted':
                if (! ForgeConfig::areRestrictedUsersAllowed()) {
                    throw new \Tuleap\Project\XML\Import\ImportNotValidException('Project access set to unrestricted but Restricted users not allowed on this platform');
                }
                $this->access = Project::ACCESS_PUBLIC_UNRESTRICTED;
                break;
            case 'private-wo-restr':
                if (! ForgeConfig::areRestrictedUsersAllowed()) {
                    throw new \Tuleap\Project\XML\Import\ImportNotValidException('Project access set to private-wo-restr but Restricted users not allowed on this platform');
                }
                $this->access = Project::ACCESS_PRIVATE_WO_RESTRICTED;
                break;
            case 'public':
                $this->access = Project::ACCESS_PUBLIC;
                break;
            case 'private':
                $this->access = Project::ACCESS_PRIVATE;
                break;

            default:
                $this->access = $this->default_project_visibility_retriever->getDefaultProjectVisibility();
        }

        $this->data_services = $service_inheritor->markUsedServicesFromXML($xml, $this->built_from_template->getProject());

        $this->inherit_from_template = isset($attrs['inherit-from-template']) && (bool) $attrs['inherit-from-template'] === true;
        $this->logger->debug("Data gathered from XML");
    }

    public function unsetProjectServiceUsage($service_id)
    {
        if (isset($this->data_services[$service_id])) {
            $this->data_services[$service_id]['is_used'] = '0';
        }
    }

    public function forceServiceUsage($service_id)
    {
        if (isset($this->data_services[$service_id])) {
            $this->data_services[$service_id]['is_used'] = '1';
        }
    }

    public function setShortDescription($short_description): void
    {
        $this->short_description = $short_description;
    }

    /**
     * @param array $project
     *
     */
    public function setAccessFromProjectData(array $project): string
    {
        return $this->access = $this->getAccessFromProjectArrayData($project);
    }

    public function isIsBuiltFromXml(): bool
    {
        return $this->is_built_from_xml;
    }

    public function getDataServices(): ?array
    {
        return $this->data_services;
    }
}
