<?php
header('Content-Type: application/rss+xml');
echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
?>
<rss version="2.0">
	<channel>
		<title><?php echo $page['rss_title']; ?></title>
		<link><?php echo $page['rss_link']; ?></link>
		<description><?php echo $page['rss_description']; ?></description>

		<language>en-us</language>
		<pubDate><?php echo $page['rss_pubDate']; ?></pubDate>
		<ttl><?php echo $page['rss_ttl']; ?></ttl>

<?php
foreach ($page['rss_items'] as $item) {
	echo "\t\t<item>\n";
	echo "\t\t\t<title>". xmlentities_wrapper($item['title']) ."</title>\n";
	echo "\t\t\t<link>$item[link]</link>\n";
	echo "\t\t\t<description>". xmlentities_wrapper($item['description']) ."</description>\n";
	echo "\t\t\t<pubDate>$item[pubdate]</pubDate>\n";
	echo "\t\t\t<guid>$item[guid]</guid>\n";
	echo "\t\t</item>\n\n";
}
?>

	</channel>
</rss>
