<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * @package     CoreAPI
*/
namespace Arikaim\Core\Api;

use Arikaim\Core\Controllers\ControlPanelApiController;
use Arikaim\Core\App\ArikaimStore;

/**
 * Repository controller
*/
class Repository extends ControlPanelApiController
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
     * Dowload and install repository from repository
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function repositoryDownload($request, $response, $data)
    { 
        // TODO 
        
        $data->validate(true);    

        $type = $data->get('type',null);
        $package = $data->get('package',null);
      
        $packageManager = $this->get('packages')->create($type);
        if ($packageManager == null) {
            $this->error('Not valid package type.');
            return false;
        }       
        $repository = null; // To do
        
        if ($repository == null) {
            $this->error('Not valid package name or repository.');
            return false;
        }
    
        $this
            ->message('repository.download')
            ->field('type',$type)   
            ->field('package',$package);                  
    }
}
