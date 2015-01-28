<?php

if (!defined("IN_PROCESS")) die("You must call `php process.php`, not this file!");

$extraHeader = <<<TAG
#
# Hosts pointed to $destIp

/ip dns static


TAG;

$headerFinished = false;

while ($line = fgets($fpRead)) {
	$line = trim($line);
	$line = str_replace("\t", "    ", $line);
	
	if ($line == '') {
		continue;
	}

	if (!$headerFinished) {
		if ($line[0] == '#') {
			// Pass the header straight through
			fputs($fpWrite, "$line\r\n");
			continue;
		} // else we've finished reading the header
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
	if (preg_match('/^(\S+)(?:\s*#\s*(.+))?$/S', $line, $matches)) {
		$addLn($matches[1], @$matches[2]);
	} else { echo '.'; }
}
