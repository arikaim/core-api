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
use Arikaim\Core\Queue\Cron;

/**
 * Cron api controller
*/
class CronApi extends ControlPanelApiController
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
     * Install cron scheduler entry
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function installCronController($request, $response, $data)
    {         
        $cron = new Cron();
        $result = ($cron->isRunning() == false) ? $cron->run() : true;
        
        $this->setResponse($result,'cron.install','errors.cron.install');              
    }

    /**
     * Run cron command
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function runController($request, $response, $data)
    {         
        $output = Cron::runCronCommand();

        $this->setResponse(true,function() use($output) {                  
            $this
                ->message('cron.run')                   
                ->field('output',$output);   
        },'errors.cron.run');                   
    }

    /**
     * Uninstall cron scheduler entry
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function unInstallCronController($request, $response, $data)
    {         
        $cron = new Cron();
        $result = $cron->stop();

        $this->setResponse($result,'cron.uninstall','errors.cron.uninstall');               
    }
}
