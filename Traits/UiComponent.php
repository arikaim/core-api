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

use Arikaim\Core\Collection\Arrays;

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
     * @return mixed 
     */
    public function load(string $name, array $params = [], string $language, ?string $type = null)
    {   
        $component = $this->get('page')->renderHtmlComponent($name,$params,$language,$type);
     
        if ($component->hasError() == true) {
            $errorCode = $component->getError();   
            if ($errorCode != 'NOT_VALID_COMPONENT') {
                $this->setResultField('redirect',$component->getOption('redirect')); 
            }
            $error = $this->get('errors')->getError($errorCode,['full_component_name' => $name]);  
            return $this->withError($error)->getResponse();          
        }
      
        $files = $this->get('page')->getComponentsFiles();
        
        $result = [
            'name'       => $component->getFullName(),
            'css'        => Arrays::arrayColumns($files['css'],['url','params','component_name']),
            'js'         => Arrays::arrayColumns($files['js'],['url','params','component_name']),
            'components' => $this->get('page')->getIncludedComponents(),
            'type'       => $component->getComponentType(),
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
    protected function getHeaderParams($request): array
    {       
        $params = $request->getHeader('Params');
        $headerParams = $params[0] ?? null;
        
        return (empty($headerParams) == false) ? \json_decode($headerParams,true) : [];         
    }
}
