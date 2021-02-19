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

use Arikaim\Core\Db\Model;
use Arikaim\Core\Controllers\ControlPanelApiController;

/**
 * Access Tokens controller
*/
class AccessTokens extends ControlPanelApiController
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
     * Delete token
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function deleteController($request, $response, $data) 
    {                
        $this->onDataValid(function($data) {          
            $uuid = $data->get('uuid');
            $result = Model::AccessTokens()->removeToken($uuid);

            $this->setResponse($result,function() use($uuid) {
                $this
                    ->message('access_tokens.delete')
                    ->field('uuid',$uuid);
            },'errors.access_tokens.delete');
                     
        });
        $data
            ->addRule('exists:model=AccessTokens|field=uuid|required','uuid')
            ->validate();        
    }

    /**
     * Delete expired tokens
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function deleteExpiredController($request, $response, $data) 
    {                   
        $this->onDataValid(function($data) {               
            $uuid = $data->get('uuid');
            $user = Model::Users()->findById($uuid);
            $result = Model::AccessTokens()->deleteExpired($user->id,null);

            $this->setResponse($result,function() use($uuid) {
                $this
                    ->message('access_tokens.expired')
                    ->field('user',$uuid);
            },'errors.access_tokens.expired');
        });
        $data
            ->addRule('exists:model=Users|field=uuid|required','uuid')
            ->validate();         
    }
}
