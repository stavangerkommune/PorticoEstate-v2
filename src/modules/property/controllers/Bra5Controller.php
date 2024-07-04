<?php

namespace App\modules\property\controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\modules\phpgwapi\services\Settings;
use App\Database\Db;
use App\modules\phpgwapi\security\Sessions;
use Sanitizer;

require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';

/**
 * @OA\Info(title="Portico API", version="0.1")
 */
class Bra5Controller
{
	private $db;
	private $acl;
	private $userSettings;
	private $secKey;
	private $where_parameter;
	private $errors = [];
	private $classname;
	private $baseclassname;
	private $phpgwapi_common;

	public function __construct(ContainerInterface $container)
	{

		//property/inc/soap_client/bra5/soap.php?domain=default&location_id=303&section=BraArkivFDV&bygningsnr=139276655

		Settings::getInstance()->update('flags', ['session_name' => 'soapclientsession']);

		$sessions = Sessions::getInstance();

		$this->phpgwapi_common = new \phpgwapi_common();

		$this->db =	Db::getInstance();
		$this->userSettings = Settings::getInstance()->get('user');
		//		Settings::getInstance()->update('server', ['usecookies' => false]);

		$location_id = Sanitizer::get_var('location_id', 'int');
		$section	 = Sanitizer::get_var('section', 'string');

		$c = CreateObject('admin.soconfig', $location_id);
		$lang_denied = '';

		if (!Sanitizer::get_var(session_name(), 'string', 'COOKIE') || !$sessions->verify())
		{

			$login	 = $c->config_data[$section]['anonymous_user'];
			$logindomain = Sanitizer::get_var('domain', 'string', 'GET');
			if (strstr($login, '#') === false && $logindomain)
			{
				$login .= "#{$logindomain}";
			}

			$passwd		= $c->config_data[$section]['anonymous_pass'];

			$sessionid = $sessions->create($login, $passwd);
			if (!$sessionid)
			{
				$lang_denied = lang('Anonymous access not correctly configured');
				if ($sessions->reason)
				{
					$lang_denied = $sessions->reason;
				}
			}
		}

		if ($lang_denied)
		{
			_debug_array($lang_denied);
			$this->phpgwapi_common->phpgw_exit();
		}

		$location_url	 = $c->config_data[$section]['location_url'];
		$braarkiv_user	 = $c->config_data[$section]['braarkiv_user'];
		$braarkiv_pass	 = $c->config_data[$section]['braarkiv_pass'];
		$this->classname		 = $c->config_data[$section]['arkd'];
		$this->where_parameter = $c->config_data[$section]['where_parameter'];
		$this->baseclassname	 = !empty($c->config_data[$section]['baseclassname']) ? $c->config_data[$section]['baseclassname'] : 'Eiendomsarkiver';

		ini_set('display_errors', true);
		error_reporting(-1);
		/**
		 * Load autoload
		 */
		require_once PHPGW_API_INC . '/soap_client/bra5/Bra5Autoload.php';
		$wdsl	 = "{$location_url}?WSDL";
		$options = array();
		$context = stream_context_create([
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
			],
		]);
		$options[\Bra5WsdlClass::WSDL_STREAM_CONTEXT] = $context;
		$options[\Bra5WsdlClass::WSDL_URL]			 = $wdsl;
		$options[\Bra5WsdlClass::WSDL_ENCODING]		 = 'UTF-8';
		$options[\Bra5WsdlClass::WSDL_TRACE]			 = false;
		$options[\Bra5WsdlClass::WSDL_SOAP_VERSION]	 = SOAP_1_2;

		$bra5ServiceLogin = new \Bra5ServiceLogin($options);
		if ($bra5ServiceLogin->Login(new \Bra5StructLogin($braarkiv_user, $braarkiv_pass)))
		{
			$this->secKey = $bra5ServiceLogin->getResult()->getLoginResult()->LoginResult;
		}
		else
		{
			print_r($bra5ServiceLogin->getLastError());
		}
	}

	public function process(Request $request, Response $response): Response
	{

		$fileid		 = Sanitizer::get_var('fileid', 'int');
		if ($fileid)
		{
			$this->get_file($fileid);
		}
		else
		{
			$html = $this->index();
			$response->getBody()->write($html);
			return $response->withHeader('Content-Type', 'text/html')->withStatus(200);
		}
	}

	public function get_file($fileid)
	{
		$Bra5ServiceGet = new \Bra5ServiceGet();

		$Bra5ServiceGet->getFileName(new \Bra5StructGetFileName($this->secKey, $fileid));
		$filename = $Bra5ServiceGet->getResult()->getFileNameResult->getFileNameResult;
		$browser = CreateObject('phpgwapi.browser');

		$get_chunked = true;
		if ($get_chunked)
		{
			$Bra5ServiceFile = new \Bra5ServiceFile();

			$_fileid = $Bra5ServiceFile->fileTransferRequestChunkedInit(new \Bra5StructFileTransferRequestChunkedInit($this->secKey, $fileid))->fileTransferRequestChunkedInitResult->fileTransferRequestChunkedInitResult;

			// Offset er posisjon i fila
			$offset			 = 0;
			$base64string	 = "";
			$fp				 = fopen("php://temp", 'w');

			// kjører løkke til tekstverdien vi får i retur er null
			while (($base64string = $Bra5ServiceFile->fileTransferRequestChunk(new \Bra5StructFileTransferRequestChunk($this->secKey, $_fileid, $offset))->fileTransferRequestChunkResult->fileTransferRequestChunkResult) != null)
			{
				$decoded_string	 = base64_decode($base64string);
				fputs($fp, $decoded_string);
				// Oppdaterer offset til filens foreløpige lengde
				$offset			 += strlen($decoded_string);
			}
			// Avslutter nedlasting
			$Bra5ServiceFile->fileTransferRequestChunkedEnd(new \Bra5StructFileTransferRequestChunkedEnd($this->secKey, $_fileid));

			$browser->content_header($filename);

			// Read what we have written.
			rewind($fp);
			echo stream_get_contents($fp);
			$this->phpgwapi_common->phpgw_exit();
		}
		else
		{

			$Bra5ServiceGet->getFileAsByteArray(new \Bra5StructGetFileAsByteArray($this->secKey, $fileid));
			$file_result	 = $Bra5ServiceGet->getResult()->getFileAsByteArrayResult;
			$file			 = base64_decode($file_result->getFileAsByteArrayResult);
			$browser->content_header($filename);

			echo $file;

			$this->phpgwapi_common->phpgw_exit();
		}

		_debug_array($Bra5ServiceGet->getLastError());
		$this->phpgwapi_common->phpgw_exit();

	}

	public function index()
	{

		if ($this->where_parameter)
		{
			$_where = "{$this->where_parameter} = " . Sanitizer::get_var($this->where_parameter);
		}
		else
		{
			$bygningsnr	 = (int)Sanitizer::get_var('bygningsnr', 'int');
			$_where		 = "Byggnr = {$bygningsnr}";
		}

		if (!$_where)
		{
			$this->errors[] = "Mangler innparameter for avgrensing av listesøk";
		}

		$bra5ServiceSearch	 = new \Bra5ServiceSearch();

		if ($bra5ServiceSearch->searchAndGetDocuments(new \Bra5StructSearchAndGetDocuments($this->secKey, $this->baseclassname, $this->classname, $_where, $_maxhits			 = -1)))
		{
			//		_debug_array($bra5ServiceSearch->getResult());die();
			$_result = $bra5ServiceSearch->getResult()->getsearchAndGetDocumentsResult()->getExtendedDocument()->getsearchAndGetDocumentsResult()->ExtendedDocument;
		}
		$css = file_get_contents(PHPGW_SERVER_ROOT . "/phpgwapi/templates/pure/css/pure-min.css");
		$css .= file_get_contents(PHPGW_SERVER_ROOT . "/phpgwapi/templates/pure/css/pure-extension.css");

		$header = <<<HTML
<!DOCTYPE HTML>
<html>
	<head>
		<meta charset="utf-8">
		<style TYPE="text/css">
			{$css}
		</style>
	</head>
		<body>
			<div class="pure-form pure-form-aligned">
HTML;

		$footer	 = <<<HTML
			</div>
	</body>
</html>
HTML;

		if (!$_result)
		{
			return "<H2> Ingen treff </H2>";
		}


		$skip_field	 = array(
			'ASTA',
			'ASTA_Signatur',
			'Adresse',
			'Eiendomsnummer',
			'GNR/BNR',
			'Sakstype',
			'Saksnr',
			'Tiltakstype',
			'Tiltaksart',
			'Gradering',
			'Skjerming',
			'BrukerID',
			'Team'
		);
		$content	 = <<<HTML
	<table class="pure-table pure-table-bordered pure-table-striped">
		<thead>
HTML;

		$content .= '<th>';
		$content .= 'Last ned';
		$content . '</th>';

		$location_id = Sanitizer::get_var('location_id', 'int');
		$section	 = Sanitizer::get_var('section', 'string');

		$base_url = \phpgw::link(
			'/property/inc/soap_client/bra5/soap.php',
			array(
				'domain'		 => $_GET['domain'],
				'location_id'	 => $location_id,
				'section'		 => $section
			)
		);
		foreach ($_result[0]->getAttributes()->Attribute as $attribute)
		{
			if (in_array($attribute->Name, $skip_field))
			{
				continue;
			}
			$content .= '<th>';
			$content .= $attribute->Name;
			$content . '</th>';
		}

		$content .= '</thead>';

		$case_array = array();
		foreach ($_result as $document)
		{

			$_html	 = '<tr>';
			$_html	 .= '<td>';
			$_html	 .= "<a href ='{$base_url}&fileid={$document->ID}' title = '{$document->Name}' target = '_blank'>{$document->ID}</a>";
			$_html	 .= '</td>';

			foreach ($document->getAttributes()->Attribute as $attribute)
			{
				if (in_array($attribute->Name, $skip_field))
				{
					continue;
				}

				if ($attribute->Name == 'Dokumentdato')
				{
					$_key = strtotime($attribute->Value->anyType[0]);
				}

				$_html .= '<td>';

				if (is_array($attribute->Value->anyType))
				{
					foreach ($attribute->Value->anyType as $key => $value)
					{
						if ($attribute->Name == 'Dato' && $value != null)
						{
							$_html .= date('d.m.Y', strtotime($value));
						}
						else
						{
							if ($key > 0)
							{
								$_html .= ', ';
							}

							if (is_object($value) && $value->enc_stype == 'Matrikkel')
							{
								$_html .= $value->enc_value->getGnr();
								$_html .= ' / ' . $value->enc_value->getBnr();
							}
							else
							{
								$_html .= $value;
							}
						}
					}
				}
				else
				{
					$_html .= $attribute->Value->anyType;
				}
				$_html .= '</td>';
			}

			$_html .= '</tr>';

			$case_array[$_key][] = $_html;
		}
		$bra5ServiceLogout	 = new \Bra5ServiceLogout();
		$bra5ServiceLogout->Logout(new \Bra5StructLogout($this->secKey));

		krsort($case_array);
		//_debug_array($case_array);
		foreach ($case_array as $case)
		{
			$content .= implode('', $case);
		}

		$content .= <<<HTML
	</table>
HTML;

		return $header . $content . $footer;

	}
}
