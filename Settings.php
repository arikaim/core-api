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

/**
 * Settings controller
*/
class Settings extends ControlPanelApiController
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
     * Disable install page
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function disableInstallPageController($request, $response, $data)
    {
        $installPage = $data->get('install_page',false);
        $this->get('cache')->clear();
        
        $this->get('config')->setBooleanValue('settings/disableInstallPage',$installPage);
        // save and reload config file
        $result = $this->get('config')->save();
        $this->setResponse($result,'settings.save','errors.settings.save');
        $this->get('cache')->clear();        
    }
}
