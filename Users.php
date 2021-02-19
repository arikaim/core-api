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

use Arikaim\Core\Controllers\ApiController;
use Arikaim\Core\Db\Model;
use Arikaim\Core\Http\Cookie;

/**
 * Users controller login, logout 
*/
class Users extends ApiController  
{   
    /**
     * Init controller
     *
     * @return void
     */
    public function init()
    {
        $this->loadMessages('system:admin.messages.user');
    }

    /**
     * Control panel login
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function adminLoginController($request, $response, $data) 
    {
        $this->onDataValid(function($data) {  
            $credentials = [
                'user_name' => $data->get('user_name'),
                'password'  => $data->get('password')
            ];
            $this->get('access')->withProvider('session')->logout();
            $result = $this->get('access')->authenticate($credentials);
    
            if ($result === false) {           
                $this->error('errors.login');  
                $this->logError('Not valid Contro Panel login details',['user_name' => $credentials['user_name']]);
                return; 
            }  
            // check for control panel permission
            if ($this->get('access')->hasControlPanelAccess() == false) {
                $this->error('errors.login');   
                return;
            }
            // update login date time
            $userId = $this->get('access')->getId();  
            Model::Users()->findById($userId)->updateLoginDate();
            $this->setResponse($result,'login','errors.login'); 

        });
        $data
            ->addRule('text:min=2','user_name')   
            ->addRule('text:min=2','password') 
            ->validate();       
    }

    /**
     * Logout
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function logoutController($request, $response, $data) 
    {    
        // remove token
        Cookie::delete('user');
        Cookie::delete('token');   

        $this->get('access')->withProvider('session')->logout();  

        $this->setResponse(true,'logout','errors.logout');      
    }   

    /**
     * Control Panel change user details
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function changeDetailsController($request, $response, $data)
    {
        // access from contorl panel only 
        $this->requireControlPanelPermission();
         
        $this->onDataValid(function($data) { 
            $userName = $data->get('user_name');
            $email = $data->get('email',null);
            $logedUser = $this->get('access')->getUser();
            $user = Model::Users();

            // check if user name is changed           
            if ($logedUser['user_name'] != $userName) {
                // check if user name exists              
                if ($user->verifyUserName($userName,$logedUser['id']) == false) {                   
                    return $this->error('errors.username');                                                                                   
                }
            }
            if (empty($email) == false) {
                if ($user->verifyEmail($email,$logedUser['id']) == false) {                   
                    return $this->error('errors.email');                                                                                   
                }
            }
            $info = [
                'user_name' => $userName,
                'email'     => $email
            ];
          
            $result = $user->findById($logedUser['id'])->update($info);
    
            $this->setResponse($result,'update','errors.update'); 
        });
        $data 
            ->addRule('text:min=2|required','user_name') 
            ->addRule('email','email')           
            ->validate();      
    }

     /**
     * Control Panel change user password
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function changePasswordController($request, $response, $data)
    {
        // access from contorl panel only 
        $this->requireControlPanelPermission();
         
        $this->onDataValid(function($data) { 
            $password = $data->get('password',null);
            $newPassword = $data->get('new_password');
            $repeatPassword = $data->get('repeat_password');

            $logedUser = $this->get('access')->getUser();
            $user = Model::Users()->findById($logedUser['id']);

            if (\is_object($user) == false) {
                $this->error('Not valid user id.');
                return false;
            }
            // check for change password 
            if ($user->verifyPassword($password) == false) {                  
                return $this->error('errors.invalid');                  
            } 
            if ($newPassword != $repeatPassword) {
                // passwords not mach            
                return $this->error('errors.password');                                   
            }
              
            $result = $user->changePassword($logedUser['id'],$newPassword);
           
            $this->setResponse($result,'update','errors.update'); 
        });
        $data   
            ->addRule('text:min=2|required','password')
            ->addRule('text:min=5|required','new_password')
            ->addRule('text:min=5|required','repeat_password')
            ->validate();      
    }
}
