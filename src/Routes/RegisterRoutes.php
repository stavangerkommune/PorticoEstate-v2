<?php

$d = dir(realpath(SRC_ROOT_PATH . '/Modules'));
while ($entry = $d->read()) {
	// skip the . and .. directories
	if ($entry == '.' || $entry == '..') {
		continue;
	}

	if (is_dir(SRC_ROOT_PATH . '/Modules/' . $entry . '/Routes')) {
		$f = SRC_ROOT_PATH . '/Modules/' . $entry . '/Routes/Routes.php';

		if (file_exists($f)) {
			require $f;
		}
	}
}
$d->close();
