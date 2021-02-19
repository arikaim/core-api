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
use Arikaim\Core\System\Update as SystemUpdate;

/**
 * Update controller
*/
class Update extends ControlPanelApiController
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
     * Update Arikaim
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function updateController($request, $response, $data) 
    {           
        $this->onDataValid(function($data) { 
            $package = $data->get('package',ARIKAIM_PACKAGE_NAME);
            $update = new SystemUpdate($package);
            $update->update();
            $version = $update->getCurrentVersion();
       
            return $this->setResponse(true,function() use($version) {
                $this
                    ->message('core.update')
                    ->field('version',$version);                            
            },'errors.core.update');
        });
        $data->validate();    
                 
    }
    
    /**
     * Get last package version
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function getLastVersionController($request, $response, $data) 
    {           
        $package = $data->get('package',ARIKAIM_PACKAGE_NAME);
  
        $update = new SystemUpdate($package);
        $version = $update->getLastVersion();
       
        $this->setResponse($version,function() use($version) {
            $this->field('version',$version);             
        },'errors.core.update');
    }
}
