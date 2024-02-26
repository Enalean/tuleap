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

namespace Tuleap\Project;

use EventManager;
use LogicException;
use Project;
use Psr\Log\LoggerInterface;
use ServiceManager;
use SimpleXMLElement;
use Tuleap\XML\SimpleXMLElementBuilder;
use Tuleap\Project\Admin\Categories\CategoryCollection;
use Tuleap\Project\Admin\DescriptionFields\ProjectRegistrationSubmittedFieldsCollection;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use WrapperLogger;
use XML_RNGValidator;

class ProjectCreationData
{
    private bool $is_built_from_xml = false;
    private LoggerInterface $logger;
    private DefaultProjectVisibilityRetriever $default_project_visibility_retriever;
    private ?array $data_services;
    private ProjectRegistrationSubmittedFieldsCollection $data_fields;
    private string $full_name;
    private string $unix_name;
    private bool $is_test;
    private ?bool $is_template         = null;
    private ?string $short_description = null;
    private TemplateFromProjectForCreation $built_from_template;
    private string $icon_codepoint = "";

    private CategoryCollection $trove_data;
    private bool $inherit_from_template = true;
    private string $access              = "";

    public function __construct(DefaultProjectVisibilityRetriever $default_project_visibility_retriever, ?\Psr\Log\LoggerInterface $logger = null)
    {
        if ($logger === null) {
            $this->logger = new \Psr\Log\NullLogger();
        } else {
            $this->logger = new WrapperLogger($logger, self::class);
        }

        $this->default_project_visibility_retriever = $default_project_visibility_retriever;

        $this->trove_data  = new CategoryCollection();
        $this->data_fields = ProjectRegistrationSubmittedFieldsCollection::buildFromArray([]);
    }

    /**
     * Returns true if the data should be inherited from template (in DB)
     *
     * This is mostly useful for XML import where "the true" come from XML
     * and not from the predefined template.
     *
     */
    public function projectShouldInheritFromTemplate(): bool
    {
        return $this->inherit_from_template;
    }

    public function getFullName(): string
    {
        return $this->full_name;
    }

    public function setFullName(string $name): void
    {
        $this->full_name = $name;
    }

    public function getUnixName(): string
    {
        return $this->unix_name;
    }

    public function setUnixName(string $name): void
    {
        $this->unix_name = $name;
    }

    public function getAccess(): string
    {
        return $this->access;
    }

    public function isTest(): bool
    {
        return $this->is_test;
    }

    public function isTemplate(): ?bool
    {
        return $this->is_template;
    }

    public function setIsTemplate(): void
    {
        $this->is_template = true;
    }

    public function getShortDescription(): ?string
    {
        return $this->short_description;
    }

    public function getBuiltFromTemplateProject(): TemplateFromProjectForCreation
    {
        return $this->built_from_template;
    }

    public function getTroveData(): CategoryCollection
    {
        return $this->trove_data;
    }

    public function getFieldValue(int $group_desc_id): ?string
    {
        foreach ($this->data_fields->getSubmittedFields() as $submitted_field) {
            if ($submitted_field->getFieldId() === $group_desc_id) {
                return $submitted_field->getFieldValue();
            }
        }

        return null;
    }

    /**
     * @return array with:
     *     is_used => boolean telling if the service is used
     */
    public function getServiceInfo(int $service_id): ?array
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
        array $data,
    ): self {
        $instance = new self($default_project_visibility_retriever);
        $instance->fromForm($template_from_project_for_creation, $data);
        return $instance;
    }

    private function fromForm(TemplateFromProjectForCreation $template_from_project_for_creation, array $data): void
    {
        $project = isset($data['project']) ? $data['project'] : [];

        $this->unix_name           = isset($project['form_unix_name']) ? $project['form_unix_name'] : "";
        $this->full_name           = isset($project['form_full_name']) ? $project['form_full_name'] : "";
        $this->short_description   = isset($project['form_short_description']) ? $project['form_short_description'] : null;
        $this->built_from_template = $template_from_project_for_creation;
        $this->is_test             = isset($project['is_test']) ? $project['is_test'] : false;
        $this->setAccessFromProjectData($project);
        $this->trove_data    = isset($project['trove']) ? $project['trove'] : new CategoryCollection();
        $this->data_services = isset($project['services'])               ? $project['services']               : [];
    }

    private function getAccessFromProjectArrayData(array $project): string
    {
        if (! isset($project['is_public'])) {
            return $this->default_project_visibility_retriever->getDefaultProjectVisibility();
        }

        $is_public                                      = (string) $project['is_public'] === '1';
        $should_project_allow_restricted_explicitly_set = isset($project['allow_restricted']);
        $should_project_allow_restricted                = $should_project_allow_restricted_explicitly_set && $project['allow_restricted'];

        if ($is_public) {
            if ($should_project_allow_restricted_explicitly_set && $should_project_allow_restricted) {
                return Project::ACCESS_PUBLIC_UNRESTRICTED;
            }
            return Project::ACCESS_PUBLIC;
        }

        if ($should_project_allow_restricted_explicitly_set && ! $should_project_allow_restricted) {
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
        ?ExternalFieldsExtractor $external_fields_extractor = null,
        ?ProjectCreationDataServiceFromXmlInheritor $service_inheritor = null,
    ): self {
        $default_project_visibility_retriever = $default_project_visibility_retriever ?? new DefaultProjectVisibilityRetriever();

        $instance = new self($default_project_visibility_retriever, $logger);
        $instance->fromXML($xml, $xml_validator, $service_manager, $external_fields_extractor, $service_inheritor);
        return $instance;
    }

    private function fromXML(
        SimpleXMLElement $xml,
        ?XML_RNGValidator $xml_validator = null,
        ?ServiceManager $service_manager = null,
        ?ExternalFieldsExtractor $external_fields_extractor = null,
        ?ProjectCreationDataServiceFromXmlInheritor $service_inheritor = null,
    ): void {
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

        $partial_element = SimpleXMLElementBuilder::buildSimpleXMLElementToLoadHugeFiles((string) $xml->asXml());
        $external_fields_extractor->extractExternalFieldFromProjectElement($partial_element);
        $xml_validator->validate($partial_element, $rng_path);
        $this->logger->debug("RNG validated, feed the data");

        $attrs = $xml->attributes();
        if (! isset($attrs)) {
            throw new LogicException("XML seems valid from rng standpoint, but does not have attributes later");
        }
        $this->unix_name           = (string) $attrs['unix-name'];
        $this->full_name           = (string) $attrs['full-name'];
        $this->short_description   = (string) $attrs['description'];
        $this->built_from_template = TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate();
        $this->is_test             = (bool) false;
        $this->trove_data          = new CategoryCollection();
        $this->data_services       = [];
        $this->is_built_from_xml   = true;
        $this->icon_codepoint      = ($attrs['icon-codepoint']) ? (string) $attrs['icon-codepoint'] : "";

        $requested_access_level = (string) $attrs['access'];
        if (in_array($requested_access_level, [Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PRIVATE_WO_RESTRICTED, Project::ACCESS_PUBLIC, Project::ACCESS_PRIVATE], true)) {
            $this->access = $requested_access_level;
        } else {
            $this->access = $this->default_project_visibility_retriever->getDefaultProjectVisibility();
        }

        $this->data_services = $service_inheritor->markUsedServicesFromXML($xml, $this->built_from_template->getProject());

        $this->inherit_from_template = isset($attrs['inherit-from-template']) && (bool) $attrs['inherit-from-template'] === true;
        $this->logger->debug("Data gathered from XML");
    }

    public function unsetProjectServiceUsage(int $service_id): void
    {
        if (isset($this->data_services[$service_id])) {
            $this->data_services[$service_id]['is_used'] = '0';
        }
    }

    public function forceServiceUsage(int $service_id): void
    {
        if (isset($this->data_services[$service_id])) {
            $this->data_services[$service_id]['is_used'] = '1';
        }
    }

    public function setShortDescription(string $short_description): void
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

    public function setTroveData(CategoryCollection $trove_data): void
    {
        $this->trove_data = $trove_data;
    }

    public function setDataFields(ProjectRegistrationSubmittedFieldsCollection $data_fields): void
    {
        $this->data_fields = $data_fields;
    }

    public function getDataFields(): ProjectRegistrationSubmittedFieldsCollection
    {
        return $this->data_fields;
    }

    public function getIconCodePoint(): string
    {
        return $this->icon_codepoint;
    }
}
