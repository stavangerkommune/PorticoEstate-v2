<?php

namespace App\Helpers;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Response;
Use ErrorException;
use Throwable;

/**
 * Class MyErrorHandler
 * @package App\Helpers
 */


class ErrorHandler {
	protected $responseFactory;

	public function __construct(ResponseFactoryInterface $responseFactory) {
		$this->responseFactory = $responseFactory;
	}

	public function __invoke(Throwable $exception, bool $displayErrorDetails): Response {
		$response = $this->responseFactory->createResponse();

		if ($exception instanceof ErrorException && $exception->getSeverity() === E_USER_ERROR) {
			// Handle user errors
			$response->getBody()->write("A user error occurred!");
		} else {
			// Handle other exceptions
			$response->getBody()->write("An exception occurred!");
		}

		return $response;
	}
}

$serverSettings  = \App\Services\Settings::getInstance()->get('server');
if (isset($serverSettings['log_levels']['global_level'])) {
	switch ($serverSettings['log_levels']['global_level']) {
		case 'F': // Fatal
		case 'E': // Error
			error_reporting(E_ERROR | E_USER_ERROR | E_PARSE);
			break;

		case 'W': // Warn
		case 'I': // Info
			error_reporting(E_ERROR | E_USER_ERROR | E_WARNING | E_USER_WARNING | E_PARSE);
			break;

		case 'N': // Notice
		case 'D': // Debug
			error_reporting(E_ERROR | E_USER_ERROR | E_WARNING | E_USER_WARNING | E_NOTICE | E_USER_NOTICE | E_PARSE);
			break;

		case 'S': // Strict
			error_reporting(E_STRICT | E_PARSE);
			break;

		case 'DP': // Deprecated
			error_reporting(E_ERROR | E_USER_ERROR | E_DEPRECATED | E_USER_DEPRECATED | E_PARSE | E_ALL);
			break;
		case 'A': // All
			error_reporting(E_ALL);
			break;
	}
}

/**
 * cleans up a backtrace array and converts it to a string
 *
 * @internal this is such an ugly piece of code due to a reference to the error context
 * being in the backtrace and the error context can not be edited - see php.net/set_error_handler
 * @param array $bt php backtrace
 * @return string the formatted backtrace, empty if the user is not an admin
 */
function phpgw_parse_backtrace($bt)
{
	if (!is_array($bt)) {
		return '';
	}

	// we don't need the call to the error handler
	unset($bt[0]);
	$bt = array_reverse($bt);

	$trace = '&nbsp;';
	$i = 0;
	foreach ($bt as $entry) {
		$line = "#{$i}\t";

		if (isset($entry['type']) && isset($entry['class'])) {
			$line .= "{$entry['class']}{$entry['type']}{$entry['function']}";
		} else {
			$line .= $entry['function'];
		}

		$line .= '(';

		if (isset($entry['args']) && is_array($entry['args']) && count($entry['args'])) {
			$args_count = count($entry['args']);
			foreach ($entry['args'] as $anum => $arg) {
				if (is_array($arg)) {
					$line .= 'serialized_value = ' . json_encode($arg, JSON_PRETTY_PRINT);
					continue;
				}

				// Drop passwords from backtrace
				if (
					$arg == $GLOBALS['phpgw_info']['server']['header_admin_password']
					|| (isset($GLOBALS['phpgw_info']['server']['db_pass']) && $arg == $GLOBALS['phpgw_info']['server']['db_pass'])
					|| (isset($GLOBALS['phpgw_info']['user']['passwd']) && $arg == $GLOBALS['phpgw_info']['user']['passwd'])
				) {
					$line .= '***REMOVED_FOR_SECURITY***';
				} else if (is_object($arg)) {
					continue;
				} else {
					$line .= $arg;
				}

				if (($anum + 1) != $args_count) {
					$line .= ', ';
				}
			}
		}

		$file = 'unknown';
		if (isset($entry['file'])) {
			if (!isset($GLOBALS['phpgw_info']['user']['apps']['admin'])) {
				$file = '/path/to/portico/' . substr($entry['file'], strlen(PHPGW_SERVER_ROOT));
			} else {
				$file = $entry['file'];
			}
		}

		if (isset($entry['line'])) {
			$file .= ":{$entry['line']}";
		} else {
			$file .= ':?';
		}

		$line .= ") [$file]";
		$trace .= "$line\n";
		++$i;
	}

	return print_r($trace, true);
}

/**
 * phpGroupWare Information level "error"
 */
define('PHPGW_E_INFO', -512);

/**
 * phpGroupWare debug level "error"
 */
define('PHPGW_E_DEBUG', -1024);

/**
 * phpGroupWare generic error handler
 *
 * @link http://php.net/set_error_handler
 *
 */
function phpgw_handle_error($error_level, $error_msg, $error_file, $error_line)
{

	if (!(error_reporting() & $error_level)) // 0 == @function() so we ignore it, as the dev requested
	{
		return true;
	}

	/*old - pre php 8*/
	//		if ( error_reporting() == 0 ) // 0 == @function() so we ignore it, as the dev requested
	//		{
	//			return true;
	//		}
	/*
_debug_array($error_level);
_debug_array($error_msg);
_debug_array($error_file);
_debug_array($error_line);
//_debug_array($bt = debug_backtrace());die();
*/
	if (isset($GLOBALS['phpgw_info']['server']['log_levels']['global_level'])) {
		switch ($GLOBALS['phpgw_info']['server']['log_levels']['global_level']) {
			case 'F': // Fatal
			case 'E': // Error
				$error_reporting = E_ERROR | E_USER_ERROR | E_PARSE;
				break;

			case 'W': // Warn
			case 'I': // Info
				$error_reporting = E_ERROR | E_USER_ERROR | E_WARNING | E_USER_WARNING | E_PARSE;
				break;

			case 'N': // Notice
			case 'D': // Debug
				$error_reporting = E_ERROR | E_USER_ERROR | E_WARNING | E_USER_WARNING | E_NOTICE | E_USER_NOTICE | E_PARSE;
				break;

			case 'S': // Strict
				$error_reporting = E_STRICT | E_PARSE;
				break;

			case 'DP': // Deprecated
				$error_reporting = E_ERROR | E_USER_ERROR | E_DEPRECATED | E_USER_DEPRECATED;
				break;
			case 'A': // All
				$error_reporting = E_ALL;
				break;
		}

		if (!(!!($error_reporting & $error_level))) {
			return true;
		}
	}

	if (
		!isset($GLOBALS['phpgw']->log)
		|| !is_object($GLOBALS['phpgw']->log)
	) {
		$GLOBALS['phpgw']->log = createObject('phpgwapi.log');
	}
	$log = &$GLOBALS['phpgw']->log;

	if (!isset($GLOBALS['phpgw_info']['user']['apps']['admin'])) {
		$error_file = str_replace(PHPGW_SERVER_ROOT, '/path/to/portico', $error_file);
	}

	$bt = debug_backtrace();

	$log_args = array(
		'file'	=> $error_file,
		'line'	=> $error_line,
		'text'	=> "$error_msg\n" . phpgw_parse_backtrace($bt)
	);
	$message = '';
	switch ($error_level) {
		case E_USER_ERROR:
		case E_ERROR:
			$log_args['severity'] = 'F'; //all "ERRORS" should be fatal
			$log->fatal($log_args);
			if (ini_get('display_errors')) {
				echo '<p class="msg">' . lang('ERROR: %1 in %2 at line %3', $error_msg, $error_file, $error_line) . "</p>\n";
				die('<pre>' . phpgw_parse_backtrace($bt) . "</pre>\n");
			} else {
				die('Error');
			}
		case E_WARNING:
		case E_USER_WARNING:
			$log_args['severity'] = 'W';
			$log->warn($log_args);
			$message .= '<p class="msg">' . lang('Warning: %1 in %2 at line %3', $error_msg, $error_file, $error_line) . "</p>\n";
			$message .= '<pre>' . phpgw_parse_backtrace($bt) . "</pre>\n";
			break;

		case PHPGW_E_INFO:
			$log_args['severity'] = 'I';
			$log->info($log_args);
			break;

		case PHPGW_E_DEBUG:
			$log_args['severity'] = 'D';
			$log->info($log_args);
			break;

		case E_NOTICE:
		case E_USER_NOTICE:
			$log_args['severity'] = 'N';
			$log->notice($log_args);
			if (isset($GLOBALS['phpgw_info']['server']['log_levels']['global_level']) && $GLOBALS['phpgw_info']['server']['log_levels']['global_level'] == 'N') {
				$message .=  '<p>' . lang('Notice: %1 in %2 at line %3', $error_msg, $error_file, $error_line) . "</p>\n";
				$message .=  '<pre>' . phpgw_parse_backtrace($bt) . "</pre>\n";
			}
			break;
		case E_STRICT:
			$log_args['severity'] = 'S';
			$log->strict($log_args);
			if (isset($GLOBALS['phpgw_info']['server']['log_levels']['global_level']) && $GLOBALS['phpgw_info']['server']['log_levels']['global_level'] == 'S') {

				//  		Will find the messages in the log - no need to print to screen
				//			echo '<p>' . lang('Strict: %1 in %2 at line %3', $error_msg, $error_file, $error_line) . "</p>\n";
				//			echo '<pre>' . phpgw_parse_backtrace($bt) . "</pre>\n";
			}
			break;

		case E_DEPRECATED:
		case E_USER_DEPRECATED:
			$log_args['severity'] = 'DP';
			$log->deprecated($log_args);
			$message .=  '<p class="msg">' . lang('deprecated: %1 in %2 at line %3', $error_msg, $error_file, $error_line) . "</p>\n";
			$message .=  '<pre>' . phpgw_parse_backtrace($bt) . "</pre>\n";
			break;
	}

	if (ini_get('display_errors')) {
		echo $message;
	}
}
set_error_handler('phpgw_handle_error');

