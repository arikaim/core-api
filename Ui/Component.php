<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Api\Ui;

use Arikaim\Core\Controllers\ApiController;
use Arikaim\Core\Collection\Arrays;

/**
 * Component Api controller
*/
class Component extends ApiController
{
    /**
     * Get html component properties
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function componentProperties($request, $response, $data)
    {
        $language = $this->getPageLanguage($data); 
        $component = $this->get('page')->createHtmlComponent($data['name'],[],$language);

        if (\is_object($component) == false) {          
            $error = $this->get('errors')->getError('TEMPLATE_COMPONENT_NOT_FOUND',['full_component_name' => $data['name']]);
            return $this->withError($error)->getResponse();  
        }
        
        $component = $component->renderComponent();

        if ($component->hasError() == true) {
            $error = $component->getError();            
            $error = $this->get('errors')->getError($error['code'],['full_component_name' => $data['name']]);                   
            return $this->withError($error)->getResponse();  
        }
        // deny requets 
        if ($component->getOption('access/deny-request') == true) {
            $error = $this->get('errors')->getError('ACCESS_DENIED');
            return $this->withError($error)->getResponse();           
        }
        
        return $this->setResult($component->getProperties())->getResponse();        
    }

    /**
     * Get html component details
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function componentDetails($request, $response, $data)
    {
        // control panel only
        $this->requireControlPanelPermission();

        $component = $this->get('page')->createHtmlComponent($data['name']);
        if (\is_object($component) == false) {
            return $this->withError('TEMPLATE_COMPONENT_NOT_FOUND')->getResponse();  
        }

        $component = $component->renderComponent();

        if ($component->hasError() == true) {
            $error = $component->getError();
            return $this->withError($this->get('errors')->getError($error['code'],$error['params']))->getResponse();            
        }
        $details = [
            'properties' => $component->getProperties(),
            'options'    => $component->getOptions(),
            'files'      => $component->getFiles()
        ];
        
        return $this->setResult($details)->getResponse();       
    }

   /**
     * Load html component
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function loadComponent($request, $response, $data)
    {       
        $params = $this->getParams($request);
     
        // get header params
        $headerParams = $this->getHeaderParams($request);
        $params = \array_merge($params,$headerParams);
        $params = \array_merge($params,$data->toArray());
      
        $language = $this->getPageLanguage($params);
        
        return $this->load($data['name'],$params,$language);
    }

    /**
     * Load html component
     *
     * @param string $name
     * @param array $params
     * @param string $language
     * @return JSON 
     */
    public function load(string $name, array $params = [], ?string $language)
    {   
        $params['current_path'] = $this->get('options')->get('current.path','');
        $component = $this->get('page')->createHtmlComponent($name,$params,$language,true);
        
        if (\is_object($component) == false) {          
            $error = $this->get('errors')->getError('TEMPLATE_COMPONENT_NOT_FOUND',['full_component_name' => $name]);  

            return $this->withError($error)->getResponse();  
        }
     
        $component = $component->renderComponentDescriptor($component->getComponentData(),$params);
    
        if ($component->hasError() == true) {
            $this->setResultField('redirect',$component->getOption('access/redirect')); 
            $error = $component->getError();         
            $error = $error['message'] ?? $this->get('errors')->getError($error['code'] ?? null,['full_component_name' => $name]);  
            return $this->withError($error)->getResponse();          
        }
      
        if ($component->getOption('access/deny-request') == true) { 
            $this->setResultField('redirect',$component->getOption('access/redirect')); 
            $error = $this->$this->get('errors')->getError('ACCESS_DENIED',['name' => $component->getFullName()]);
            return $this->withError($error)->getResponse();           
        }
       
        $files = $this->get('page')->getComponentsFiles();
       
        $result = [
            'name'       => $component->getFullName(),
            'css'        => Arrays::arrayColumns($files['css'],['url','params','component_name']),
            'js'         => Arrays::arrayColumns($files['js'],['url','params','component_name']),
            'components' => $this->get('view')->getIncludedComponents(),
            'html'       => $component->getHtmlCode()           
        ];
  
        return $this->setResult($result)->getResponse();        
    }

    /**
     * Get header params
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return array
     */
    private function getHeaderParams($request)
    {       
        $params = $request->getHeader('Params');
        $headerParams = $params[0] ?? null;
        
        return (empty($headerParams) == false) ? \json_decode($headerParams,true) : [];         
    }
}
