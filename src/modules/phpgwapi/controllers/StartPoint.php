<?php

namespace App\modules\phpgwapi\controllers;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\modules\phpgwapi\services\Log;
use App\modules\phpgwapi\services\Settings;
use Sanitizer;

class StartPoint
{
    private $log;
    public function __construct()
    {
        $this->init();
        $this->log = new Log();

    }

    public function init()
    {
        $this->loadConfig();
        $this->loadLanguage();
        $this->loadSession();
        $this->loadUser();
        $this->loadHooks();
        $this->loadApp();
    }

    public function loadConfig()
    {
    }

    public function loadLanguage()
    {
    }

    public function loadSession()
    {
    }

    public function loadUser()
    {
    }

    public function loadHooks()
    {
    }

    public function loadApp()
    {
    }

    public function run(Request $request, Response $response)
    {
        if (isset($_GET['menuaction']) || isset($_POST['menuaction']))
        {
            if(isset($_GET['menuaction']))
            {
                list($app,$class,$method) = explode('.',$_GET['menuaction']);
            }
            else
            {
                list($app,$class,$method) = explode('.',$_POST['menuaction']);
            }
            if (! $app || ! $class || ! $method)
            {
                $invalid_data = true;
            }
        }
        else
        {
    
            $app = 'home';
            $invalid_data = true;
        }
    
        $api_requested = false;
        if ($app == 'phpgwapi')
        {
            $app = 'home';
            $api_requested = true;
        }
        
        $flags = [
            'noheader'   => true,
            'currentapp' => $app
		];

		Settings::getInstance()->set('flags', $flags);	
        
        if ($app == 'home' && ! $api_requested)
        {
            $GLOBALS['phpgw']->redirect_link('/home.php');
        }
    
        if ($api_requested)
        {
            $app = 'phpgwapi';
        }
    
        $Object = CreateObject("{$app}.{$class}");
    
        if ( !$invalid_data 
            && is_object($Object)
            && isset($Object->public_functions) 
            && is_array($Object->public_functions) 
            && isset($Object->public_functions[$method])
            && $Object->public_functions[$method] )
    
        {
            if ( Sanitizer::get_var('X-Requested-With', 'string', 'SERVER') == 'XMLHttpRequest'
                 // deprecated
                || Sanitizer::get_var('phpgw_return_as', 'string', 'GET') == 'json' )
            {
                $return_data = $Object->$method();
                $response_str = json_encode($return_data);
                $response->getBody()->write($response_str);
                return $response->withHeader('Content-Type', 'application/json');

//                $flags['nofooter'] = true;
 //               Settings::getInstance()->set('flags', $flags);    
     //           $GLOBALS['phpgw']->common->phpgw_exit();
            }
            else
            {
                if(Sanitizer::get_var('phpgw_return_as', 'string', 'GET') =='noframes')
                {
                    $flags['noframework'] = true;
                    $flags['headonly']=true;
                    Settings::getInstance()->set('flags', $flags);
                }
                $Object->$method();
                return;
            }
            unset($app);
            unset($class);
            unset($method);
            unset($invalid_data);
            unset($api_requested);
        }
        else
        {
            //FIXME make this handle invalid data better
            if (! $app || ! $class || ! $method)
            {
                $this->log->message(array(
                    'text' => 'W-BadmenuactionVariable, menuaction missing or corrupt: %1',
                    'p1'   => $menuaction,
                    'line' => __LINE__,
                    'file' => __FILE__
                ));
            }
    
            if ( ( !isset($Object->public_functions)
                || !is_array($Object->public_functions)
                || !isset($Object->public_functions[$method])
                || !$Object->public_functions[$method] )
                && $method)
            {
                $this->log->message(array(
                    'text' => 'W-BadmenuactionVariable, attempted to access private method: %1 from %2',
                    'p1'   => $method,
                    'p2'=> Sanitizer::get_ip_address(),
                    'line' => __LINE__,
                    'file' => __FILE__
                ));
            }
            $this->log->commit();
    
           // $GLOBALS['phpgw']->redirect_link('/home.php');
        }
     //   $GLOBALS['phpgw']->common->phpgw_footer();
    

        $response_str = json_encode(['message' => 'Welcome to Portico API']);
        $response->getBody()->write($response_str);
        return $response->withHeader('Content-Type', 'application/json');
    }
}