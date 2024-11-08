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
            $component = $this->get('view')->renderComponentError($name,$language,'RENDER_COMPONENT_ERROR');
            $component->setError($e->getMessage() . ' line: ' . $e->getLine() . ' file: ' . $e->getFile());
        }
    
        $errorCode = ($component->hasError() == true) ? $component->getError() : $this->get('page')->getError();
        
        if (empty($errorCode) == false) {
            $options = $component->getOptions();
            if ($errorCode == 'ACCESS_DENIED') {
                $this->setResultField('redirect',($options['access']['redirect'] ?? null)); 
                $this->setResultField('reload',($options['access']['reload'] ?? true)); 
            } else {
                $this->setResultField('redirect',null); 
                $this->setResultField('reload',false); 
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
