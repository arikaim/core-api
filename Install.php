<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Api;

use Arikaim\Core\Controllers\ApiController;
use Arikaim\Core\App\Install as SystemInstall;
use Arikaim\Core\App\PostInstallActions;
use Arikaim\Core\Controllers\Traits\TaskProgress;

/**
 * Install controller
*/
class Install extends ApiController
{
    use TaskProgress;

    /**
     * Install Arikaim 
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function installController($request, $response, $data) 
    {            
        $this->onDataValid(function($data) {    
            $disabled = $this->get('config')->getByPath('settings/disableInstallPage',false);
            if ($disabled == true) {
                $this->error('Install page is disabled.');
                return;
            }
            $install = new SystemInstall();
            $result = $install->prepare();

            // clear cache
            $this->get('cache')->clear();
         
            $result = SystemInstall::setConfigFilesWritable();              
            if ($result === false) {
                $this->error('Config files is not writtable.');
                return;
            }
            
            // save config file               
            $this->get('config')->setValue('db/host',$data->get('host','localhost'));
            $this->get('config')->setValue('db/username',$data->get('username'));
            $this->get('config')->setValue('db/password',$data->get('password'));
            $this->get('config')->setValue('db/database',$data->get('database')); 

            $result = $this->get('config')->save();
            if ($result === false) {
                $this->error('Config file is not writtable.');
                return;
            }
            // clear cache
            $this->get('cache')->clear();

            $result = $this->get('db')->testConnection($this->get('config')->get('db'));
            if ($result == false) {                
                $this->error('Not valid database connection username or password.');
                return; 
            }
           
            // do install        
            $this->initTaskProgress();

            $result = $install->install(
                function($message) {       
                    $this->clearResult();           
                    $this->setResponse(true,$message,'error');
                    $this->sendProgressResponse();       
                },function($error) {    
                    $this->clearResult();                   
                    $this->setResponse(false,'',$error);             
                    $this->sendProgressResponse();       
                }
            );   
            $this->clearResult();    
            $this->taskProgressEnd();
             
            $this->setResponse($result,'Arikaim CMS was installed successfully.','Install Error');   
        });
        $data
            ->addRule('text:min=2','database')
            ->addRule('text:min=2','username')
            ->addRule('text:min=2','password')
            ->validate();      
    }

    /**
     * Install Arikaim modules
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function installModulesController($request, $response, $data) 
    {           
        $this->onDataValid(function($data) {  
            $disabled = $this->get('config')->getByPath('settings/disableInstallPage',false);
            if ($disabled == true) {
                $this->error('Install page is disabled.');
                return;
            }

            // clear cache
            $this->get('cache')->clear();

            // do install
            $install = new SystemInstall();
            $this->initTaskProgress();

            $result = $install->installModules(
                function($name) {   
                    $this->clearResult();   
                    $message = $name . ' module installed successfully.';               
                    $this->setResponse(true,$message,'');
                    return $this->sendProgressResponse();       
                },function($name) {       
                    $this->clearResult();   
                    $error = 'Error module installation ' . $name;                
                    $this->setResponse(false,'',$error);             
                    return $this->sendProgressResponse();       
                }
            );   
            // clear cache
            $this->get('cache')->clear();
            $this->taskProgressEnd();

            $this->setResponse($result,'Modules was installed successfully.','Error install modules.');                          
        });
        $data->validate();      
    }

    /**
     * Install Arikaim extensions
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function installExtensionsController($request, $response, $data) 
    {           
        $this->onDataValid(function($data) {   
            $disabled = $this->get('config')->getByPath('settings/disableInstallPage',false);
            if ($disabled == true) {
                $this->error('Install page is disabled.');
                return;
            }

            // clear cache
            $this->get('cache')->clear();

            // do install
            $install = new SystemInstall();
            $this->initTaskProgress();

            $result = $install->installExtensions(
                function($name) {   
                    $this->clearResult();   
                    $message = $name . ' extension installed successfully.';               
                    $this->setResponse(true,$message,'');
                    return $this->sendProgressResponse();       
                },function($name) {      
                    $this->clearResult();    
                    $error = 'Error extension installation ' . $name;                
                    $this->setResponse(false,'',$error);             
                    return $this->sendProgressResponse();       
                }
            );  
            // clear cache
            $this->get('cache')->clear();
            $this->taskProgressEnd();

            $this->setResponse($result,'Extensions was installed successfully.','Error install extensions.');                          
        });
        $data->validate();      
    }
    
    /**
     * Post install actions
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function postInstallActionsController($request, $response, $data) 
    {           
        $this->onDataValid(function($data) {    
            $disabled = $this->get('config')->getByPath('settings/disableInstallPage',false);
            if ($disabled == true) {
                $this->error('Install page is disabled.');
                return;
            }

            // clear cache
            $this->get('cache')->clear();

            $this->initTaskProgress();

            // do post install actions
            $result = PostInstallActions::run(
                function($package) {   
                    $this->clearResult();   
                    $message = $package . ' package action executed.';               
                    $this->setResponse(true,$message,'');
                    return $this->sendProgressResponse();       
                },function($package) {    
                    $this->clearResult();      
                    $error = 'Error execution action on package ' . $package;                
                    $this->setResponse(false,'',$error);             
                    return $this->sendProgressResponse();       
                }
            );
            // clear cache
            $this->get('cache')->clear();
            
            $this->taskProgressEnd();
            $this->setResponse($result,'Success.','Post install actions error.');                                   
        });
        $data->validate();      
    }

    /**
     * Repair installation Arikaim 
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function repairController($request, $response, $data) 
    {
        $this->requireControlPanelPermission();

        $this->onDataValid(function($data) {  
            // clear cache
            $this->get('cache')->clear();
             
            $install = new SystemInstall();
            $result = $install->install();   

            $result = ($result == false) ? SystemInstall::isInstalled() : true;
            // run post install actions
            PostInstallActions::run();

            // clear cache
            $this->get('cache')->clear();

            $this->setResponse($result,'Arikaim CMS repair installation successfully.','Repair installation error.');                  
        });
        $data->validate();  
    }
}
