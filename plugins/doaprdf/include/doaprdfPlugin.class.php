<?php

/**
 * doaprdfPlugin Class
 *
 * Copyright 2011, Olivier Berger & Institut Telecom
 *
 * This program was developped in the frame of the COCLICO project
 * (http://www.coclico-project.org/) with financial support of the Paris
 * Region council.
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */


require_once 'common/include/ProjectManager.class.php';
require_once 'common/include/rdfutils.php';

class doaprdfPlugin extends Plugin {
	public function __construct($id=0) {
		$this->Plugin($id) ;
		$this->name = "doaprdf";
		$this->text = "DoaPRDF!"; // To show in the tabs, use...
		$this->_addHook("script_accepted_types");
		$this->_addHook("content_negociated_project_home");
		$this->_addHook("alt_representations");

	}

	/**
	 * Declares itself as accepting RDF XML on /projects/...
	 * @param unknown_type $params
	 */
	function script_accepted_types (&$params) {
		$script = $params['script'];
		if ($script == 'project_home') {
			$params['accepted_types'][] = 'application/rdf+xml';
		}
	}

	/**
	 * Outputs project's DOAP profile
	 * @param unknown_type $params
	 */
	function content_negociated_project_home (&$params) {
		$projectname = $params['groupname'];
		$accept = $params['accept'];
		$group_id = $params['group_id'];

		if($accept == 'application/rdf+xml') {
			
			// connect to FusionForge internals
			$pm = ProjectManager::instance();
			$project = $pm->getProject($group_id);
			$project_shortdesc = $project->getPublicName();
			$project_description = $project->getDescription();
			$tags_list = NULL;
			if (forge_get_config('use_project_tags')) {
				$group = group_get_object($group_id);
				$tags_list = $group->getTags();
			}
			
			// We will return RDF+XML
			$params['content_type'] = 'application/rdf+xml';

			// Construct an ARC2_Resource containing the project's RDF (DOAP) description
			$ns = array(
					'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
					'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
					'doap' => 'http://usefulinc.com/ns/doap#',
					'dcterms' => 'http://purl.org/dc/terms/' 
			);
				
			$conf = array(
					'ns' => $ns
			);
			
			$res = ARC2::getResource($conf);
			$res->setURI(util_make_url_g($projectname, $group_id));
				
			// $res->setRel('rdf:type', 'doap:Project');
			rdfutils_setPropToUri($res, 'rdf:type', 'doap:Project');
							
			$res->setProp('doap:name', $projectname);
			$res->setProp('doap:shortdesc', $project_shortdesc);
			if($project_description) {
				$res->setProp('doap:description', $project_description);
			}
			$res->setProp('doap:homepage', $project->getHomePage());
			$tags = array();
			if($tags_list) {
				$tags = split(', ',$tags_list);
				$res->setProp('dcterms:subject', $tags);
			}
			
			// Now, we need to collect complementary RDF descriptiosn of the project via other plugins 
			// invoke the 'project_rdf_metadata' hook so as to complement the RDF description
			$hook_params = array();
			$hook_params['prefixes'] = array();
			foreach($ns as $prefix => $url) {
				$hook_params['prefixes'][$url] = $prefix;
			}
			$hook_params['group'] = $group_id;
			// pass the resource in case it could be useful (read-only in principle)
			$hook_params['in_Resource'] = $res;
			$hook_params['out_Resources'] = array();
			plugin_hook_by_reference('project_rdf_metadata', $hook_params);

			// add new prefixes to the list
			foreach($hook_params['prefixes'] as $url => $prefix) {
				if (!isset($ns[$prefix])) {
					$ns[$prefix] = $url;
				}
			}

			// merge the two sets of triples
			$merged_index = $res->index;
			foreach($hook_params['out_Resources'] as $out_res) {
				$merged_index = ARC2::getMergedIndex($merged_index, $out_res->index);
			}

			$conf = array(
					'ns' => $ns,
					'serializer_type_nodes' => true
			);
			
			$ser = ARC2::getRDFXMLSerializer($conf);
			
			/* Serialize a resource index */
			$doc = $ser->getSerializedIndex($merged_index);

			$params['content'] = $doc . "\n";
		}
	}
	
	/**
	 * Declares a link to itself in the link+meta HTML headers
	 * @param unknown_type $params
	 */
	function alt_representations (&$params) {
		$script_name = $params['script_name'];
		$php_self = $params['php_self'];
		// really trigger only for real projects descriptions, not for the projects index
		if ( ($script_name == '/projects') && (($php_self != '/projects') && ($php_self != '/projects/')) ) {
			$params['return'][] = '<link rel="meta" type="application/rdf+xml" title="DOAP RDF Data" href=""/>';
		}
	}
	
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
