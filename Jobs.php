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
use Arikaim\Core\Collection\PropertiesFactory;

/**
 * Jobs controller
*/
class Jobs extends ControlPanelApiController
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
     * Delete job
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function deleteJobController($request, $response, $data)
    {
        $this->onDataValid(function($data) {       
            $uuid = $data->get('uuid');

            $job = $this->get('queue')->findById($uuid);
            $result = (\is_object($job) == true) ? $job->delete() : false;

            $this->setResponse($result,function() use($uuid) {                  
                $this
                    ->message('jobs.delete')                   
                    ->field('uuid',$uuid);   
            },'errors.jobs.delete');
        });
        $data->validate();      
    }
    
    /**
     * Enable/Disable job
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function setStatusController($request, $response, $data)
    {
        $this->onDataValid(function($data) {   
            $uuid = $data->get('uuid');  
            $status = $data->get('status',1);

            $result = $this->get('queue')->getStorageDriver()->setJobStatus($uuid,$status);
            $this->setResponse($result,function() use($status,$uuid) {                  
                $this
                    ->message('jobs.status')
                    ->field('status',$status)
                    ->field('uuid',$uuid);   
            },'errors.jobs.status');
        });
        $data->validate();          
    }

    /**
     * Save job config
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function saveConfigController($request, $response, $data)
    {
        $this->onDataValid(function($data) {            
            $jobName = $data->get('name');           
            $data->offsetUnset('name');

            $job = $this->get('queue')->getJob($jobName);
            // change config valus
            $config = PropertiesFactory::createFromArray($job['config']); 
            $config->setPropertyValues($data->toArray());

            $result = $this->get('queue')->saveJobConfig($jobName,$config->toArray());
        
            $this->setResponse($result,'jobs.config','errors.jobs.config');
        });
        $data->validate();       
    }
}
