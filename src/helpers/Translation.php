<?php

use App\modules\phpgwapi\services\Translation;

/**
 * Translate a string to a user's prefer language - convience method
 *
 * @param string $key phrase to translate (note: %n are replaces with $mn)
 * @param string $m1 substitution string
 * @param string $m1 substitution string
 * @param string $m2 substitution string
 * @param string $m3 substitution string
 * @param string $m4 substitution string
 * @param string $m5 substitution string
 * @param string $m6 substitution string
 * @param string $m7 substitution string
 * @param string $m8 substitution string
 * @param string $m9 substitution string
 * @param string $m10 substitution string
 * @returns string translated phrase
 */
function lang($key, $m1 = '', $m2 = '', $m3 = '', $m4 = '', $m5 = '', $m6 = '', $m7 = '', $m8 = '', $m9 = '', $m10 = '')
{
	static $translation = null;
	if (is_array($m1))
	{
		$vars = $m1;
	}
	else
	{
		$vars = array($m1, $m2, $m3, $m4, $m5, $m6, $m7, $m8, $m9, $m10);
	}

	// Support DOMNodes from XSL templates
	foreach ($vars as &$var)
	{
		if (is_object($var) && $var instanceof DOMNode)
		{
			$var = $var->nodeValue;
		}
	}

	if (!$translation)
	{
		$translation = Translation::getInstance();
	}

	return $translation->translate($key, $vars);
}
