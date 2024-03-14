<?php

namespace App\Helpers;

class Auth
{
	public static function VerifyHash($passwd, $hash)
	{
		if (!preg_match('/^{(.*)}(.*)$/', $hash, $m) || count($m) != 3) //full string, algorhythm, hash
		{
			// invalid hash
			return false;
		}
		$algo = $m[1];
		$hash = $m[2];
		unset($m);

		switch (strtoupper($algo)) {
			case 'CRYPT':
				$hash = base64_decode($hash);
				$salt = substr($hash, 63);
				$hash = substr($hash, 0, 63);
				return $hash === crypt($passwd, '$5$' . $salt);

			case 'BCRYPT':
				$hash = base64_decode($hash);
				$hash = substr($hash, 0, 60);
				return password_verify($passwd, $hash);

			case 'MD5':
				$hash = bin2hex(base64_decode($hash));
				return $hash === md5($passwd);

			case 'SHA':
				$hash = bin2hex(base64_decode($hash));
				return $hash === sha1($passwd);

			case 'SMD5':
				$hash = bin2hex(base64_decode($hash));
				$salt = substr($hash, 32);
				$hash = substr($hash, 0, 32);
				return $hash === md5($passwd . $salt);

			case 'SSHA':
				$hash = bin2hex(base64_decode($hash));
				$salt = substr($hash, 40);
				$hash = substr($hash, 0, 40);
				return $hash === sha1($passwd . $salt);
		}
	}

}
