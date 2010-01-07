<?php
/*
Read support for git-issues
http://github.com/jwiegley/git-issues/tree/master
*/

class IssuePlugin extends VGPlugin
{
	function __construct() {
		global $conf;
		if (isset($conf['plugin_issue'])) {
			$this->register_action('issue');
			$this->register_hook('pagenav');
			$this->register_hook('summary');
		}
	}

	function action($action) {
		global $page;
		$page['project'] = validate_project($_REQUEST['p']);
		$page['issues']=$this->git_get_issue_list($page['project']);
		if (isset($_REQUEST['h'])){
			$page['issue']=$this->git_get_issue($page['project'], $_REQUEST['h']);
			$this->display_plugin_template('issue');
		}else{
			$this->display_plugin_template('summary');
		}
	}
	
	function hook_summary() {
		global $page;
		$page['issues']=$this->git_get_issue_list($page['project']);
		$this->display_plugin_template('summary', FALSE);
	}
	
	/*
	Get a list of git-issues
	*/
	function git_get_issue_list($project)
	{
		$cmd = "issues list";
		$output = run_git($project, $cmd);
	
		preg_match_all('|(\S+)|',$output[0], $header);
		$header=$header[0];
		$header[0]='num';
	
		foreach(array_slice($output, 2) as $issue){
			$i = trim($issue);
			if (!empty($i)){
				$r=array();
				preg_match_all('|(\S+)\s+(\S+)\s+(.+)\s\s\s+?(\S+)\s+(\S+)\s+(\S+)|', $issue, $data);
				$data=array_slice($data, 1);
				foreach($header as $field){
					$d = array_shift($data);
					$r[strtolower($field)]=trim($d[0]);
				}
				$result[]=$r;
			}
		
		}
		return $result;
	}

	/**
	 * Get one issue
	 */
	function git_get_issue($project, $hash)
	{
		$cmd = "issues show $hash";
		$output = run_git($project, $cmd);
	
		$result = array();
		foreach($output as $line){
			$title=trim(substr($line, 0, 15));
			$val= trim(substr($line, 17));
			if (substr($title,0,1) != "["){
				$result[strtolower($title)]=$val;
			}else{
				$result['comments']=array();
				foreach(array_slice(explode("'",$line),1,-1) as $comment){
					if ($comment!=', '){
						preg_match_all('|(.+)/(.+)/(.+)|',$comment,$out);
						$data = $out[3][0];
						if ($data != 'issue.xml'){
							// comment_88ecfef5ac88156c0e49bfca907ff4a07629fae8_2008-12-30T03:06:19.937396_Is this working?.xml
							preg_match_all('|comment_.+_(.+)_(.+)\.xml|', $data, $out);
							$d=explode('T',$out[1][0]);
							$result['comments'][]=array('date'=>$d[0], 'time'=>$d[1], 'text'=>$out[2][0]);
						}
					}
				}			
			}
		}
	
		return $result;
	}


	function hook($type) {
		global $page;
		switch($type){
			case 'pagenav':
				$page['links']['issues'] = array('a' => 'issue');
				break;
			case 'summary':
				$this->hook_summary();
				break;
		}
		
	}
}

