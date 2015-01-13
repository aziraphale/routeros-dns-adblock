<?php

$files = [
	"peter-lowe" 			=> ["240.0.0.1"],
	"mvps" 					=> ["240.0.0.2"],
	"hphosts" 				=> ["240.0.0.3"],
	"dan-pollock" 			=> ["240.0.0.4"],
	"spam404" 				=> ["240.0.0.5"],
	"malwaredomains.com" 	=> ["240.0.0.6"],
	"malwaredomainlist.com"	=> ["240.0.0.7"],
	"custom"				=> ["240.0.0.255"],
];

// Might be a bit memory-intensive/slow... not strictly necessary, as RouterOS will just display a warning on duplicates
define('SKIP_DUPLICATES', false);

// Maybe faster? -- actually it's not :(
define('SKIP_DUPLICATES_CRC32', false);

define('PER_FILE_LIMIT', 3000);

define('IN_PROCESS', 1);
$totalTimeStart = microtime(true);
$totalHosts = 0;
$totalFiles = 0;
$hostsList = [];

echo "NOTE: Removing duplicate hosts is " . (SKIP_DUPLICATES ? "ENABLED" : "DISABLED") . (SKIP_DUPLICATES_CRC32 ? ' (via crc32)' : '') . ".\r\n\r\n";

foreach ($files as $type => $details) {
	list($destIp) = $details;

	$startTime = microtime(true);
	echo
		str_pad($type, 39, " ", STR_PAD_RIGHT) .
		" => " .
		str_pad($destIp, 15, " ", STR_PAD_RIGHT) .
		" ... ";

	$hosts = 0;
	$hostsInThisFile = 0;
	$fileNum = 0;
	$fpRead = fopen("source.$type.txt", 'rb');
	$fpWrite = fopen("script.$type-$fileNum.rsc", 'wb');

	$addLn = function($name, $comment = null) use ($type, $destIp, &$fpWrite, &$hosts, &$hostsList, &$hostsInThisFile, &$fileNum) {
		if (SKIP_DUPLICATES) {
			$searchName = crc32($name);
			if (in_array($searchName, $hostsList)) {
				return;
			} else {
				$hostsList[] = $searchName;
			}
		}

		if ($hostsInThisFile >= PER_FILE_LIMIT) {
			// Switch to a new file
			fclose($fpWrite);
			++$fileNum;
			$hostsInThisFile = 0;
			$fpWrite = fopen("script.$type-$fileNum.rsc", 'wb');
			fputs($fpWrite, "# Continuation...\r\n\r\n");
			fputs($fpWrite, "/ip dns static\r\n\r\n");
		}

		if (!empty($comment)) {
			// Includes a comment
			fputs($fpWrite, sprintf(
				"add address=%s name=\"%s\" comment=\"%s\"\r\n",
				$destIp,
				$name,
				addcslashes($comment, '?"')
			));
		} else {
			// No comment
			fputs($fpWrite, sprintf(
				"add address=%s name=\"%s\"\r\n",
				$destIp,
				$name
			));
		}
		++$hosts;
		++$hostsInThisFile;
	};

	include "process.$type.php";

	fclose($fpRead);
	fclose($fpWrite);

	$duration = (microtime(true) - $startTime) * 1000;
	printf("%d hosts (%.2fms) (%d files)\r\n", $hosts, $duration, ($fileNum+1));

	$totalHosts += $hosts;
	$totalFiles += ($fileNum + 1);
}

echo "\r\n";
$totalDuration = (microtime(true) - $totalTimeStart) * 1000;
printf("Total duration: %.2fms\r\n", $totalDuration);
printf("Total hosts:    %d\r\n", $totalHosts);
printf("Total files:    %d\r\n", $totalFiles);
printf("Peak RAM use:   %.2f MB\r\n", (memory_get_peak_usage(true) / (1024*1024)));
