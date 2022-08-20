<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Api\Traits;

/**
 * UiComponent Api controller
*/
trait UiComponent 
{
    /**
     * Load html component
     *
     * @param string $name
     * @param array $params
     * @param string $language
     * @param string|null $type
     * @param array $options
     * @return mixed 
     */
    public function load(string $name, array $params = [], string $language, ?string $type = null, array $options = [])
    {   
        $name = \urldecode($name);
        $component = $this->get('page')->renderHtmlComponent($name,$params,$language,$type);
     
        if ($component->hasError() == true) {
            $errorCode = $component->getError();   
            if ($errorCode != 'NOT_VALID_COMPONENT') {
                $this->setResultField('redirect',$component->getOption('redirect')); 
            }
            $error = $this->get('errors')->getError($errorCode,['full_component_name' => $name]);  
            return $this->withError($error)->getResponse();          
        }
      
        return $this->setResult([
            'name'                => $component->getFullName(),
            'component_id'        => $component->id,
            'type'                => $component->getComponentType(),
            'html'                => $component->getHtmlCode(),   
            'css'                 => [], 
            'js'                  => $this->get('page')->getComponentsFiles()['js'],                 
            'components'          => $this->get('page')->getIncludedComponents(),
            'component_instances' => $this->get('page')->getComponentInstances()                   
        ])->getResponse();        
    }

    /**
     * Get header params
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return array
    */
    protected function getHeaderParams($request): array
    {       
        $params = $request->getHeader('Params');
        $headerParams = $params[0] ?? null;
        
        return (empty($headerParams) == false) ? \json_decode($headerParams,true) : [];         
    }
}
