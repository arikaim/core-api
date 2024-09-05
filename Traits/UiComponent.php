<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * @package     CoreAPI
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
     * @param string|null $language
     * @param string|null $type
     * @param array $options
     * @return mixed 
     */
    public function load(string $name, array $params = [], ?string $language = null, ?string $type = null, array $options = [])
    {   
        $name = \urldecode($name);
        try {
            $component = $this->get('page')->renderHtmlComponent($name,$params,$language,$type);
        } catch (\Exception $e) {
            $component->setError('RENDER_COMPONENT_ERROR');
        }
    
        if ($component->hasError() == true) {
            $errorCode = $component->getError();              
          
            if ($errorCode == 'ACCESS_DENIED') {
                $options = $component->getOptions();
                $this->setResultField('redirect',($options['redirect'] ?? null)); 
                $this->setResultField('reload',($options['reload'] ?? false)); 
            }            
           
            return $this->withError($errorCode)->getResponse();          
        }
      
        return $this->setResult([
            'name'                => $component->getFullName(),
            'component_id'        => $component->id,
            'type'                => $component->getComponentType(),
            'html'                => $component->getHtmlCode(),   
            'css'                 => [], 
            'js'                  => $this->get('page')->getComponentsFiles()['js'],                 
            'components'          => \array_values($this->get('page')->getIncludedComponents()),
            'component_instances' => \array_values($this->get('page')->getComponentInstances())                   
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
