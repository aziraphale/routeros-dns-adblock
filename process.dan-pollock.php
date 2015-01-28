<?php

if (!defined("IN_PROCESS")) die("You must call `php process.php`, not this file!");

$extraHeader = <<<TAG
# Hosts pointed to $destIp

/ip dns static


TAG;

$headerFinished = false;
$localhostLines = ['localhost','localhost.localdomain','broadcasthost','local'];

while ($line = fgets($fpRead)) {
	$line = trim($line);
	$line = str_replace("\t", "    ", $line);

	if (!$headerFinished) {
		if ($line == '') {
			$line = '#';
		}
		if ($line[0] == '#') {
			// Pass the header straight through
			fputs($fpWrite, "$line\r\n");
			continue;
		} // else we've finished reading the header
	}
	
	if ($line == '') {
		continue;
	}

	if (!$headerFinished) {
		// First non-header line
		if (!BIND9_OUTPUT) {
			fputs($fpWrite, $extraHeader);
		}
		$headerFinished = true;
	}

	if ($line[0] == '#') {
		// whole-line comment after the header; drop it
		continue;
	}

	// Normal line, in theory
	if (preg_match('/^127\.0\.0\.1\s+(\S+)(?:\s*#\s*(.+))?$/S', $line, $matches)) {
		if (in_array(strtolower($matches[1]), $localhostLines)) {
			// localhost entry...
			continue;
		}

		$addLn($matches[1], @$matches[2]);
	} else { 
		if (strpos($line, 'broadcasthost') === false && strpos($line, 'localhost') === false) {
			echo '.';
			var_dump($line);
		}
	}
}
