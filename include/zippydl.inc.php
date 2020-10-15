<?php

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');   

    return round(pow(1024, $base - floor($base)), $precision) .''. $suffixes[floor($base)];
}

class zippyshare {
	public $downloaded_count = 0;
	public $skipped_count = 0;
	public $error_count = 0;
	public $bytesdownloaded = 0;
	public $regex_pattern = '/https?:\/\/www([\d]*)\.zippyshare\.com\/v\/([\w\d]*)\/file\.html/m';

	function download_file($url, $catalog = ".") {
		$url = trim($url);
		$parse_url = parse_url($url);

		Info("Checking \"".$url."\" ");

		if(explode(".", $parse_url['host'])['1'] != "zippyshare") {
			Error($url." is not a zippyshare url.");
		}

		$data = file_get_contents($url);

		if(!strpos($data, "does not exist") === true) {
			$dom = new SelectorDOM($data);
			$script = $dom->select('script:contains(dlbutton)')['0']['text'];
			$eval = explode("\n", trim($script));

			//NEW//

			$getmathpath = eval('return '.explode("+", end(explode("+(", end(explode(" = ", $eval['5'])))))[0].';');
			$restmath = $getmathpath + 11;

			$filename = end(explode("5/5)+\"/", end(explode("+(", end(explode(" = ", $eval['5']))))));
            $filename = substr($filename, 0, -2);
			
			$d_part = explode("+(", end(explode(" = ", $eval['5'])))[0];
			$d_part = str_replace("\"", NULL, $d_part);


			$dl = $parse_url['scheme']."://".$parse_url['host'].$d_part.$restmath."/".$filename;
			$filename = str_replace("%e2%80%93", "-", $filename);
			$filename = rawurldecode($filename);
			$extension = end(explode(".", $filename));

			if($extension != "rar") {
				if($catalog != ".") {
					if(!file_exists("downloads2/".$catalog)) mkdir("downloads2/".$catalog, 0777, true);
				}

				if(!file_exists("downloads2/".$catalog."/".$filename)) {
					Info("Downloading...");
					$saveto = "downloads2/".$catalog."/".$filename;
					file_put_contents($saveto, file_get_contents($dl));

					$bytes = strlen(file_get_contents("downloads2/".$catalog."/".$filename));

					Success($filename."\" has been downloaded. Size: ".formatBytes($bytes));
					$this->bytesdownloaded = $this->bytesdownloaded+$bytes;
					$this->downloaded_count++;
				} else {
					Warning("Skipping \"".$filename."\". File already downloaded.");
					$this->skipped_count++;
				}
			} else {
				Warning("Skipping \"".$filename."\". RAR File.");
				$this->skipped_count++;
			}
		} else {
			Error("Skipping \"".$url."\". File not Found.");
			$this->error_count++;
		}
	}
}

?>