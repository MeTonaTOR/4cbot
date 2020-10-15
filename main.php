<?php

if (php_sapi_name() != "cli") die("This has to be executed from CLI mode.");
error_reporting('E_ALL');

include "include/selector.inc.php";
include "include/consolelog.inc.php";
include "include/curl.inc.php";
include "include/zippydl.inc.php";

$checked_links_count = 0;
$zs_links_count = 0;
$starttime = time();

ini_set('memory_limit', '-1');

echo "   ___      _       _     _                          _ ".PHP_EOL;
echo "  /   |    | |     | |   | |                        | |".PHP_EOL;
echo " / /| | ___| |_   _| |__ | |__  ___ _ __ ___   _ __ | |".PHP_EOL;
echo "/ /_| |/ __| | | | | '_ \| '_ \/ _ \ '__/ __| | '_ \| |".PHP_EOL;
echo "\___  | (__| | |_| | |_) | |_) | __/ |  \__ \_| |_) | |".PHP_EOL;
echo "    |_/\___|_|\__,_|_.__/|_.__/\___|_|  |___(_) .__/|_|".PHP_EOL;
echo "                                              | |  \033[0;32mv1.4\033[0m".PHP_EOL;
echo "                                              |_|      ".PHP_EOL;
echo PHP_EOL;

if(file_exists("zippylinks.json")) {
	unlink("zippylinks.json");
}

if(isset($argv[1])) {
	if((int)$argv[1] != 0) {
		$pagestofetch = (int)$argv[1];
	} elseif($argv[1] == "all") {
		$pagestofetch = "ALL";
	} else {
		$pagestofetch = 1;
	}
} else {
	$pagestofetch = 1;
}

MessageLog("{green}[S] {normal}Pages to load has been set to ".$pagestofetch);

// Approx. 54750 threads to analyze (250 threads per page)
$fc_links["Hardstyle"] 			= "http://www.4clubbers.com.pl/lite/f-132.html"; //29 pages
$fc_links["Handsup"] 			= "http://www.4clubbers.com.pl/lite/f-188.html"; //11 pages
$fc_links["Bigroom"]			= "http://www.4clubbers.com.pl/lite/f-316.html"; //40 pages
$fc_links["House"] 				= "http://www.4clubbers.com.pl/lite/f-189.html"; //67 pages
$fc_links["Progressive"] 		= "http://www.4clubbers.com.pl/lite/f-215.html"; //24 pages
$fc_links["DiscoPolo"] 			= "http://www.4clubbers.com.pl/lite/f-78.html";  //28 pages
$fc_links['Eurodance'] 			= "http://www.4clubbers.com.pl/lite/f-107.html"; //10 pages
$fc_links['Bounce'] 			= "http://www.4clubbers.com.pl/lite/f-311.html"; //10 pages

$save_all_links = array();

$zippyshare = new zippyshare;
foreach($fc_links as $genre => $actual_link) {
	$pageloader = loadPage($actual_link);
	$pages_raw_dom = new SelectorDOM($pageloader);
	$pages_array_dom = $pages_raw_dom->select('#pagenumbers a');

	if(isset($argv[1]) && $argv[1] == "all") {
		$pagestofetch = end($pages_array_dom)['text'];
		MessageLog("{green}[S] {normal}Found ".$pagestofetch." pages for ".$genre);
	}

	$correctlink = $actual_link;
	for($pages = 1; $pages <= $pagestofetch; $pages++) {
		$actual_link = str_replace(".html", "-p-".$pages.".html", $correctlink);
		Success("Loading '".$genre."' page ('".$actual_link."')");
		$fc_mainpage = loadPage($actual_link);

		if($fc_mainpage == NULL) {
			Error("Looks like you are logged out automatically. Please set new cookies to cookies.txt file.");
			die();
		}

		$dom = new SelectorDOM($fc_mainpage);
		$petla = $dom->select('ol li');
		foreach($petla as $links_to_check) {
			$checked_links_count++;
			$folder = trim(str_replace($links_to_check['children']['0']['text'], NULL, $links_to_check['text']));

			if($folder == NULL) {
				$folder = str_replace("\"", NULL, $genre);
			}

			Info("Entering: ".trim(str_replace($folder, "", $links_to_check['text'])));

			$gotolink = $links_to_check['children']['0']['attributes']['href'];

			$fc_thread = loadPage($gotolink);
			$dom2 = new SelectorDOM($fc_thread);

			$fetch_all_zippylinks = $dom2->select('.post')['0']['text'];
			$storelink = array();

			preg_match_all($zippyshare->regex_pattern, $fetch_all_zippylinks, $matches, PREG_SET_ORDER, 0);

			foreach ($matches as $temporaryvariable) {
				$storelink[] = "http://www".$temporaryvariable['1'].".zippyshare.com/v/".$temporaryvariable['2']."/file.html";
			}

			if(count($storelink) == 0) {
				Error("Nothing was found... skipping");
			} else {
				Success("Found ".count($storelink)." links... downloading");

				foreach ($storelink as $key => $value) {
					$value = trim($value);
					$zippyshare->download_file($value, $folder);
					$save_all_links[] = $value;
					$zs_links_count++;
				}
			}

			unset($storelink);
		}
	}
}


$totaltimesecs = time()-$starttime;
list($hours, $minutes, $seconds) = explode(":", gmdate("H:i:s", $totaltimesecs));

echo PHP_EOL;
MessageLog("{normal}Checked links count: ".$checked_links_count);
MessageLog("{bold-blue}Total links found: {normal}".$zs_links_count);

if($zippyshare->downloaded_count == 0) {
	MessageLog("{bold-green}Downloaded tracks: {normal}".$zippyshare->downloaded_count);
} else {
	MessageLog("{bold-green}Downloaded tracks: {normal}".$zippyshare->downloaded_count. " (around ".formatBytes($zippyshare->bytesdownloaded)." of data used)");
}

MessageLog("{bold-yellow}Skipped tracks: {normal}".$zippyshare->skipped_count);
MessageLog("{bold-red}Error downloading count: {normal}".$zippyshare->error_count);

if($hours == "0") {
	MessageLog("{normal}Time: ".$minutes."minutes ".$seconds."seconds.");
} else {
	MessageLog("{normal}Time: ".$hours."hours ".$minutes."minutes ".$seconds."seconds.");
}

$f = fopen('zippylinks.json', 'a');
fwrite($f, json_encode($save_all_links, JSON_PRETTY_PRINT));
fclose($f);

MessageLog("{normal}A file with all links has been saved as 'zippylinks.json'.");
?>