<?php
// ## export sf front page news in RSS

require_once('pre.php');
require_once('www/news/news_utils.php');
require_once('common/rss/RSS.class.php');
require('rss_utils.inc');

//First, check for valid group_id
$request = HTTPRequest::instance();
if ($request->exist('group_id')) {
    $pm = ProjectManager::instance();
    $project = $pm->getProject($request->get('group_id'));
    if (!$project) {
        $rss = new RSS(array(
            'title'       => $Language->getText('export_rss_sfnews', 'news', ForgeConfig::get('sys_name')),
            'description' => $Language->getText('export_rss_sfnews', 'highlights', ForgeConfig::get('sys_name')),
            'link'        => $request->getServerUrl(),
            'language'    => 'en-us',
            'copyright'   => $Language->getText(
                'export_rss_sfnewreleases',
                'copyright',
                array(ForgeConfig::get('sys_long_org_name'), ForgeConfig::get('sys_name'),date('Y',time()))
            ),
            'pubDate'     => gmdate('D, d M Y G:i:s',time()).' GMT',
        ));
        $rss->addItem(array(
            'title'       => 'Error',
            'description' => 'Project not found',
            'link'        => $request->getServerUrl()
        ));
        $rss->display();
        exit;
    }
}

header("Content-Type: text/xml");
print '<?xml version="1.0"  encoding="UTF-8" ?>
<?xml-stylesheet type="text/xsl"  href="/export/rss.xsl" ?>
<rss version="0.91">
';
// ## default limit
$limit          = 10;
$validator_uint = new Valid_UInt('limit');
if ($request->valid($validator_uint)) {
    $limit = $request->get('limit');
}
if ($limit > 100) {
    $limit = 100;
}

//
// Database news_bytes table:
// column is_approved value:
// 0 => viewable within the project
// 1 => approved (viewable within the project AND on the web-site front page)
// 4 => deleted (not viewable at all)
// Private news and public news are managed with permissions (stored in permissions table)
//
if (isset($group_id) && $group_id) {
    $project = new Project($group_id);
    // We want only project news, not deleted
    $where_clause = " is_approved<>4 AND group_id=" . db_ei($group_id) . " ";
} else {
    // We want only approved news (=1 => automatically <>4)
    $where_clause = " is_approved = 1 ";
}

$res = db_query('SELECT forum_id,summary,date,details,group_id FROM news_bytes '
	.'WHERE '.$where_clause.' ORDER BY date DESC LIMIT '. db_ei($limit));

// ## one time output

print " <channel>\n";
print "  <copyright>".$Language->getText(
        'export_rss_sfnewreleases',
        'copyright',
        array(ForgeConfig::get('sys_long_org_name'),ForgeConfig::get('sys_name'),date('Y',time())))."</copyright>\n";
print "  <pubDate>".gmdate('D, d M Y G:i:s',time())." GMT</pubDate>\n";

if (isset($group_id) && $group_id) {
  print "  <description>".$Language->getText('export_rss_sfnews',
          'highlights',
          ForgeConfig::get('sys_name'))." - ".$project->getPublicName()."</description>\n";
    print "  <link>". $request->getServerUrl() ."/project/?group_id=". urlencode($group_id) . "</link>\n";
    print "  <title> ".$Language->getText('export_rss_sfnews', 'news', ForgeConfig::get('sys_name'))." - ".$project->getPublicName()."</title>\n";
} else {
    print "  <description>".$Language->getText('export_rss_sfnews', 'highlights', ForgeConfig::get('sys_name'))."</description>\n";
    print "  <link>". $request->getServerUrl() ."</link>\n";
    print "  <title> ".$Language->getText('export_rss_sfnews','news', ForgeConfig::get('sys_name')
        )."</title>\n";
}
print "  <language>en-us</language>\n";
// ## item outputs

while ($row = db_fetch_array($res)) {
	$forum_id = $row['forum_id'];
    $group_id = isset($group_id) ? $group_id:ForgeConfig::get('sys_news_group');
	if (news_check_permission($forum_id,$group_id)) {
	    print "  <item>\n";
	    print "   <title>".htmlspecialchars($row['summary'])."</title>\n";
	    // if news group, link is main page
	    if ($row['group_id'] != ForgeConfig::get('sys_news_group')) {
		    print "   <link>". $request->getServerUrl() ."/forum/forum.php?forum_id=". urlencode($forum_id) . "</link>\n";
	    } else {
		    print "   <link>". $request->getServerUrl() ."/</link>\n";
	    }
	    print "   <description>".rss_description($row['details'])."</description>\n";
	    print "  </item>\n";
	}    
}
// ## end output
print " </channel>\n</rss>";
