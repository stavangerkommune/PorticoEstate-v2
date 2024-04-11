<?php

$d = dir(realpath(SRC_ROOT_PATH . '/modules'));
while ($entry = $d->read()) {
	// skip the . and .. directories
	if ($entry == '.' || $entry == '..') {
		continue;
	}

	if (is_dir(SRC_ROOT_PATH . '/modules/' . $entry . '/routes')) {
		$f = SRC_ROOT_PATH . '/modules/' . $entry . '/routes/Routes.php';

		if (file_exists($f)) {
			require $f;
		}
	}
}
$d->close();
