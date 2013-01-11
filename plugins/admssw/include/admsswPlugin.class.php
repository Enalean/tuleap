<?php

/**
 * admsswPlugin Class
 *
 * Copyright 2012, Olivier Berger & Institut Mines-Telecom
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


//require_once('common/include/ProjectManager.class.php');
//require_once('common/include/rdfutils.php');

class admsswPlugin extends Plugin {
	public function __construct($id=0) {
		$this->Plugin($id) ;
		$this->name = "admssw";
		$this->text = "ADMS.SW"; // To show in the tabs, use...

		$this->_addHook("project_rdf_metadata"); // will provide some RDF metadata for the project's DOAP profile to 'doaprdf' plugin		
		$this->_addHook("alt_representations");
		$this->_addHook("script_accepted_types");
		$this->_addHook("content_negociated_projects_list");
	}


	/**
	 * Declares itself as accepting RDF XML on /projects/...
	 * @param unknown_type $params
	 */
	function project_rdf_metadata (&$params) {
	
		# TODO : create another resource
		$group_id=$params['group'];
	
		$new_prefixes = array('admssw' => 'http://purl.org/adms/sw/',
				'rad' => 'http://www.w3.org/ns/radion#',
				'schema' => 'http://schema.org/');
		foreach($new_prefixes as $s => $u)
		{
			if (! isset($params['prefixes'][$u])) {
				$params['prefixes'][$u] = $s;
			}
		}
			
		$res = $params['in_Resource'];
	
		// we could save the type doap:Project in such case, as there's an equivalence, but not sure all consumers do reasoning
		$types = array('doap:Project', 'admssw:SoftwareProject');
		rdfutils_setPropToUri($res, 'rdf:type', $types);
		
		$tags_list = NULL;
		if (forge_get_config('use_project_tags')) {
			$group = group_get_object($group_id);
			$tags_list = $group->getTags();
		}
		// connect to FusionForge internals
		$pm = ProjectManager::instance();
		$project = $pm->getProject($group_id);
		
		$tags = array();
		if($tags_list) {
			$tags = split(', ',$tags_list);
			
			// reuse the same as dcterms:subject until further specialization of adms.sw keywords
			$res->setProp('rad:keyword', $tags);
		}
			
		$project_description = $project->getDescription();
		if($project_description) {
				// it seems that doap:description is not equivalent to dcterms:description, so repeat
				$res->setProp('dcterms:description', $project_description);
			}
				
		$res->setProp('rdfs:comment', "Generated with the doaprdf and admssw plugins of fusionforge");

		rdfutils_setPropToUri($res, 'dcterms:isPartOf', util_make_url ("/projects"));
		
		$admins = $project->getAdmins() ;
		$members = $project->getUsers() ;
		$contributors_uris = array();
		
		foreach ($admins as $u) {
			$contributor_uri = util_make_url_u ($u->getUnixName(),$u->getID());
			$contributor_uri = rtrim($contributor_uri, '/');
			$contributor_uri = $contributor_uri . '#person';
			if (! in_array($contributor_uri, $contributors_uris) ) {
				$contributors_uris[] = $contributor_uri;
			}
		}
		foreach ($members as $u) {
			$contributor_uri = util_make_url_u ($u->getUnixName(),$u->getID());
			$contributor_uri = rtrim($contributor_uri, '/');
			$contributor_uri = $contributor_uri . '#person';
			if (! in_array($contributor_uri, $contributors_uris) ) {
				$contributors_uris[] = $contributor_uri;
			}
		}
		rdfutils_setPropToUri($res, 'schema:contributor', $contributors_uris);
		
		$params['out_Resources'][] = $res;

	}
	
	
	/**
	 * Declares a link to itself in the link+meta HTML headers
	 * @param unknown_type $params
	 */
	function alt_representations (&$params) {
		$script_name = $params['script_name'];
		$php_self = $params['php_self'];
		$php_self = rtrim($php_self, '/');
		if ($php_self == '/projects') {
			$params['return'][] = '<link rel="meta" type="application/rdf+xml" title="ADMS.SW RDF Data" href=""/>';
		}
	}
	
	
	/**
	 * Declares itself as accepting RDF XML on /projects ...
	 * @param unknown_type $params
	 */
	function script_accepted_types (&$params) {
		$script = $params['script'];
		if ($script == 'projects_list') {
			$params['accepted_types'][] = 'application/rdf+xml';
		}
	}
	
	/**
	 * Outputs the public projects list as ADMS.SW for /projects
	 * @param unknown_type $params
	 */
	function content_negociated_projects_list (&$params) {
		
		$accept = $params['accept'];
			
		if($accept == 'application/rdf+xml') {
				
				
			// We will return RDF+XML
			$params['content_type'] = 'application/rdf+xml';
	
			// Construct an ARC2_Resource containing the project's RDF (DOAP) description
			$ns = array(
					'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
					'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
					'doap' => 'http://usefulinc.com/ns/doap#',
					'dcterms' => 'http://purl.org/dc/terms/',
					'admssw' => 'http://purl.org/adms/sw/',
					'adms' => 'http://www.w3.org/ns/adms#'
			);
	
			$conf = array(
					'ns' => $ns
			);
				
			$res = ARC2::getResource($conf);
			$res->setURI( util_make_url ("/projects") );
	
			// $res->setRel('rdf:type', 'doap:Project');
			rdfutils_setPropToUri($res, 'rdf:type', 'admssw:SoftwareRepository');
				
			//$res->setProp('doap:name', $projectname);
			$res->setProp('adms:accessURL', util_make_url ("/softwaremap/") );
			$forge_name = forge_get_config ('forge_name');
			$ff = new FusionForge();
			$res->setProp('dcterms:description', 'Public projects in the '. $ff->software_name .' Software Map on '. $forge_name );
			$res->setProp('rdfs:label', $forge_name .' public projects');
			$res->setProp('adms:supportedSchema', 'ADMS.SW v1.0');
			
			// same as for trove's full list
			$projects = get_public_active_projects_asc();
			$proj_uris = array();
			foreach ($projects as $row_grp) {
				$proj_uri = util_make_url_g(strtolower($row_grp['unix_group_name']),$row_grp['group_id']);
				$proj_uris[] = $proj_uri;
			}
			if(count($proj_uris)) {
				rdfutils_setPropToUri($res, 'dcterms:hasPart', $proj_uris);
			}
			
			$conf = array(
					'ns' => $ns,
					'serializer_type_nodes' => true
			);
				
			$ser = ARC2::getRDFXMLSerializer($conf);
				
			/* Serialize a resource index */
			$doc = $ser->getSerializedIndex($res->index);
	
			$params['content'] = $doc . "\n";
		
		}
	}
	
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
