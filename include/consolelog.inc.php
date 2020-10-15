<?php

function MessageLog($Message, $EOL = PHP_EOL) {
	$Message = str_replace(
		['{normal}', '{green}', '{yellow}', '{lightred}', '{teal}', '{bold-blue}', '{bold-green}', '{bold-yellow}', '{bold-purple}', '{bold-red}'],
		["\033[0m", "\033[0;32m", "\033[1;33m", "\033[1;31m", "\033[0;36m", "\033[1;34m", "\033[1;32m", "\033[1;33m", "\033[1;35m", "\033[1;31m"],
	$Message, $Count );

	if($Count > 0) {
		$Message .= "\033[0m";
	}

	$Message = "\033[37;1m[".date('d.m.y H:i:s')."] ".$Message.$EOL;
	echo $Message;
}

function Success($message) {
	global $genre, $pages;
	return MessageLog("\033[95;1m[".$genre." - Page ".$pages."] {green}[S] {normal}".$message);
}

function Warning($message) {
	global $genre, $pages;
	return MessageLog("\033[95;1m[".$genre." - Page ".$pages."] {yellow}[W] {normal}".$message);
}

function Error($message) {
	global $genre, $pages;
	return MessageLog("\033[95;1m[".$genre." - Page ".$pages."] {lightred}[E] {normal}".$message);
}

function Info($message) {
	global $genre, $pages;
	return MessageLog("\033[95;1m[".$genre." - Page ".$pages."] {teal}[I] {normal}".$message);
}

?>