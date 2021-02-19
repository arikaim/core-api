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

use Arikaim\Core\Controllers\ControlPanelApiController;
use Arikaim\Core\Db\Model;
use Arikaim\Core\Packages\Composer;

/**
 * Packages controller
*/
class Packages extends ControlPanelApiController
{
    /**
     * Init controller
     *
     * @return void
     */
    public function init()
    {
        $this->loadMessages('system:admin.messages');
    }

    /**
     * Uninstall package
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function unInstallController($request, $response, $data)
    {
        $this->onDataValid(function($data) { 
            $this->get('cache')->clear();

            $type = $data->get('type',null);
            $name = $data->get('name',null);

            $packageManager = $this->get('packages')->create($type);
            $result = $packageManager->unInstallPackage($name);

            if (\is_array($result) == true) {
                $this->addErrors($result);
                return;
            }

            $this->setResponse($result,function() use($name,$type) {                  
                $this
                    ->message($type . '.uninstall')
                    ->field('type',$type)   
                    ->field('name',$name);                  
            },'errors.' . $type . '.uninstall');
        });
        $data->validate();
    }

    /**
     * Update or Install composer packages
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function updateComposerPackagesController($request, $response, $data)
    {
        $this->onDataValid(function($data) { 
            $this->get('cache')->clear();
          
            $type = $data->get('type',null);
            $name = $data->get('name',null);

            $packageManager = $this->get('packages')->create($type);
            $package = $packageManager->createPackage($name);
        
            if (\is_object($package) == false) {
                $this->error('errors.package.name');
                return;
            }
            $require = $package->getRequire();
            $composerPackages = $require->get('composer',[]);
            
            foreach ($composerPackages as $compsoerPackage) {
                if (Composer::isInstalled($compsoerPackage) === false) {
                    Composer::requirePackage($compsoerPackage);     
                } else {
                    Composer::updatePackage($compsoerPackage);     
                }                         
            }    
            $result = Composer::isInstalled($composerPackages);
        
            $this->setResponse($result,function() use($name,$type) {                  
                $this
                    ->message('composer.update')
                    ->field('type',$type)   
                    ->field('name',$name);                  
            },'errors.composer.update');
        });
        $data->validate();
    }

    /**
     * Install package
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function installController($request, $response, $data)
    {
        $this->onDataValid(function($data) { 
            $this->get('cache')->clear();
          
            $type = $data->get('type',null);
            $name = $data->get('name',null);
            $runPostInstall = $data->get('run_post_install',true);

            $packageManager = $this->get('packages')->create($type);
            $result = $packageManager->installPackage($name);

            if (\is_array($result) == true) {
                $this->addErrors($result);
                return;
            }
            
            if ($runPostInstall == true) {
                // post install actions
                $packageManager->postInstallPackage($name);
            }
           
            $this->setResponse($result,function() use($name,$type) {                  
                $this
                    ->message($type . '.install')
                    ->field('type',$type)   
                    ->field('name',$name);                  
            },'errors.' . $type . '.install');
        });
        $data->validate();
    }

    /**
     * Update (reinstall) package
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function updateController($request, $response, $data)
    {
        $this->onDataValid(function($data) {  
            $this->get('cache')->clear();
        
            $type = $data->get('type',null);
            $name = $data->get('name',null);
            $runPostInstall = $data->get('run_post_install',true);

            $packageManager = $this->get('packages')->create($type);            
            $package = $packageManager->createPackage($name);
            if (\is_object($package) == false) {
                $this->error('errors.package.name');
                return;
            }

            $properties = $package->getProperties();
            $primary = $properties->get('primary',false);

            $package->unInstall();    
            
            $this->get('cache')->clear();
           
            $result = $package->install($primary);

            if ($primary == true) { 
                $package->setPrimary();
            }
            
            if (\is_array($result) == true) {
                $this->addErrors($result);
                return;
            }
        
            if ($runPostInstall == true) {
                // run post install actions
                $package->postInstall();
            }
           
            $this->setResponse($result,function() use($name,$type) {
                $this
                    ->message($type . '.update')
                    ->field('type',$type)   
                    ->field('name',$name);         
            },'errors.' . $type  . '.update');
        });
        $data->validate();
    }

    /**
     * Enable/Disable package
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function setStatusController($request, $response, $data)
    { 
        $this->onDataValid(function($data) {   
            $this->get('cache')->clear();

            $type = $data->get('type',null);
            $name = $data->get('name',null);
            $status = $data->get('status',1);

            $packageManager = $this->get('packages')->create($type);            
          
            $result = ($status == 1) ? $packageManager->enablePackage($name) : $packageManager->disablePackage($name);
            $stausLabel = ($status == 1) ? 'enable' : 'disable';

            $this->setResponse($result,function() use($name,$type,$status,$stausLabel) {               
                $this
                    ->message($type . '.' . $stausLabel)
                    ->field('type',$type)   
                    ->field('status',$status)
                    ->field('name',$name);         
            },'errors.' . $type  . '.' . $stausLabel);
        });
        $data->validate();
    }

    /**
     * Save module config
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function saveConfigController($request, $response, $data)
    {
        $this->onDataValid(function($data) {    
            $this->get('cache')->clear();

            $module = Model::Modules()->FindByColumn('name',$data['name']);
            $module->config = $data->toArray();
            $result = $module->save();
            
            $this->setResponse($result,'module.config','errors.module.config');
        });
        $data->validate();       
    }

    /**
     * Set primary package
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function setPrimaryController($request, $response, $data)
    {      
        $this->onDataValid(function($data) { 
            $this->get('cache')->clear();

            $name = $data['name'];
            $type = $data->get('type','template');

            $packageManager = $this->get('packages')->create($type);            
          
            $package = $packageManager->createPackage($name);
            $result = (\is_object($package) == true) ? $package->setPrimary() : false;
            
            $this->setResponse($result,function() use($name,$type) {         
                $this
                    ->message($type . '.primary')
                    ->field('name',$name);         
            },'errors.' . $type . '.primary'); 
        });
        $data->validate();            
    }

    /**
     * Set ui library params
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function setLibraryParamsController($request, $response, $data)
    {        
        $this->onDataValid(function($data) { 
            $name = $data['name'];
            $libraryParams = $data->get('params',[]);
            $result = [];
        
            foreach ($libraryParams as $item) {
                $result[$item['name']] = $item['value'];
            }

            $params = $this->get('options')->get('library.params',[]);
            $params[$name] = $result;
            $result = $this->get('options')->set('library.params',$params);
            
            $this->get('cache')->clear();
            
            $this->setResponse($result,function() use($name) {                        
                $this
                    ->message('library.params')
                    ->field('name',$name);         
            },'errors.library.params'); 
        });
        $data->validate();            
    }

     /**
     * Set ui library status
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function setLibraryStatusController($request, $response, $data)
    {        
        $this->onDataValid(function($data) { 
            $name =  $data->get('name');
            $status = (bool)$data->get('status',false);

            $packageManager = $this->get('packages')->create('library');
            $library = $packageManager->createPackage($name);
            $library->setStatus($status);

            $result = $library->savePackageProperties();
            $this->get('cache')->clear();

            $this->setResponse($result,function() use($name,$status) {                        
                $this
                    ->message('library.status')
                    ->field('status',$status)
                    ->field('name',$name);         
            },'errors.library.params'); 
        });
        $data->validate();            
    }
}
