<?PHP
header("Content-Type: text/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';
ini_set("include_path", ini_get("include_path").":../php-lib");
include('getid3/getid3.lib.php');
include('getid3/getid3.php');

$getID3 = new getid3;

$title   = 'Podcast Directory';
$desc    = 'A description';
$baseref = 'http://[yoururl]';  // path where this script is.


$icon    = 'http://icons.iconarchive.com/icons/iconshock/antique/256/radio-icon.png'; // url of icon
$abouturl= '';

$name   = "Aq";
$email  = "aq@gkhs.net";
$author = "$email ($name)";

$subtitle = "Yes";
$summary = "";

#Should come from http://www.apple.com/itunes/podcasts/specs.html#categories
$itunes_category = "Comedy"; // Split subcategories by "/", eg: Baking/Cakes/Chocolate


echo <<<EOW
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
    <channel>
        <!-- begin RSS 2.0 tags -->
        <title>$title</title>
        <link>$abouturl</link>
        <language>en-uk</language>

        <description>$desc</description>
        <ttl>720</ttl>
        <itunes:author>$author</itunes:author>
        
         <itunes:subtitle>$subtitle</itunes:subtitle>
    <itunes:summary>$summary</itunes:summary>
    <itunes:owner>
           <itunes:name>$name</itunes:name>
           <itunes:email>$email</itunes:email>
    </itunes:owner>
    
    <itunes:image href="$icon"/>
    
    
EOW;

$categories = explode("/", $itunes_category);

$catxml = "";

while ($category = array_pop($categories)){
  if($catxml == ""){
    $catxml = '<itunes:category text="'.$category.'"/>'."\n";
  } else {
    $catxml = '<itunes:category text="'.$category.'">'."\n".$catxml."</itunes:category>\n";
  }
}

echo $catxml."\n\n";


if ($icon){
echo <<<EOW
        <image>
            <url>$icon</url>
            <title>$title</title>

            <link>$abouturl</link>
            <width>100</width>
            <height>100</height>
        </image>
        
EOW;
}

$item = <<<EOW
        <item>
            <!-- begin RSS 2.0 tags -->
            <title>%s</title>

            <guid>%s</guid>
            <pubDate>%s</pubDate>
            <author>$author</author>
            <link>%s</link>
            <enclosure url="%s" length="%s" type="%s" />

            <description>%s</description>
            
            <itunes:duration>%s</itunes:duration>
            
        </item>
EOW;

$dir  ='./';

$output = array();

$files = array();

if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
      while (($file = readdir($dh)) !== false) {
	    if($file[0] == '.' || $file == "index.php"){
		    continue;
	    }
	    $files[] = $file;
	  }
    closedir($dh);
	}
}

rsort($files);


foreach($files as $file){
  $filetype  = mime_content_type($dir.$file);
  $mime = explode("/", $filetype);
  if ($mime[0] != "audio"){
    continue;
  }
  $idthree = $getID3->analyze($dir.$file);
  getid3_lib::CopyTagsToComments($idthree);
  $url       = $baseref.$file;
  $url = htmlentities($url);
  $filesize  = filesize($dir.$file);
  $filemtime = filemtime($dir.$file);
    
  $title = $idthree['comments']['title'][0] .' - ' .$idthree['comments']['artist'][0] ;
  $title = htmlentities($title);
  $desc =  $idthree['comments']['comments']['0'];
  $duration = $idthree['playtime_string'];
  
  $title = trim($title);	
  if(empty($title)){
  $title = 'Untitled Thing';
  }
  $output[] = sprintf($item, $title, $url, date('r', $filemtime), $url, $url, $filesize, $filetype, $desc, $duration);
}

foreach($output as $line){
	echo $line;
}

echo <<<EOW
    </channel>
</rss>
EOW;
