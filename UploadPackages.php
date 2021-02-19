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
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\ZipFile;
use Arikaim\Core\Utils\Path;

use Arikaim\Core\Controllers\Traits\FileUpload;

/**
 * UploadPackage controller
*/
class UploadPackages extends ControlPanelApiController
{
    use FileUpload;

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
     * Get package info
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function packageInfoController($request, $response, $data)
    {
    }

    /**
     * Confirm Upload package
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function confirmUploadController($request, $response, $data)
    {
        $this->onDataValid(function($data)  {    
            
            $packageDir = $data->get('package_directory');
            $sourcePath = Path::STORAGE_TEMP_PATH . $packageDir;    
            
            if (File::exists($sourcePath) == false) {
                $this->error('errors.package.temp');
                return;                
            }
            $packageInfo = File::readJsonFile($sourcePath . DIRECTORY_SEPARATOR . 'arikaim-package.json');
            if ($packageInfo === false) {
                $this->error('errors.package.json');
                return;
            }
            $destinatinPath = $this->get('packages')->getPackagePath($packageInfo['package-type']);
            $destinatinPath = $destinatinPath . $packageInfo['name'];
           
            $result = File::copy($sourcePath,$destinatinPath);

            $this->setResponse($result,function() use($packageDir) {            
                $this
                    ->message('package.upload')                                    
                    ->field('package',$packageDir);                                                 
            },'errors.package.upload');
        });  
        $data->validate();        
    }

    /**
     * Upload package
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function uploadController($request, $response, $data)
    {  
        $this->onDataValid(function($data) use ($request) {            
            $files = $this->uploadFiles($request,Path::STORAGE_TEMP_PATH,false);

            // process uploaded files         
            foreach ($files as $item) {               
                if (empty($item['error']) == false) continue;
                $fileUploaded = $item['name'];
                $destination = pathinfo($fileUploaded,PATHINFO_FILENAME); 
                if (File::getExtension($fileUploaded) == 'zip') {
                    // unzip 
                    $fileName = Path::STORAGE_TEMP_PATH . $fileUploaded;                   
                    ZipFile::extract($fileName,Path::STORAGE_TEMP_PATH . $destination);
                    break;
                }
            }
            $packageDir = pathinfo($fileName,PATHINFO_FILENAME); 

            $result = $this->get('storage')->has('temp/' . $packageDir);
            if ($result == false) {               
                $this->error('errors.package.upload');
                return;
            }

            $packageInfo = File::readJsonFile(Path::STORAGE_TEMP_PATH . $packageDir . DIRECTORY_SEPARATOR . 'arikaim-package.json');
            if ($packageInfo === false) {
                $this->error('errors.package.json');
                return;
            }
            $packagePath = $this->get('packages')->getPackagePath($packageInfo['package-type']);
            $destinationPath = Path::getRelativePath($packagePath) . $packageInfo['name'];

            $currentPackage = false;
            if (File::exists($packagePath . $packageInfo['name']) == true) {
                $packageManager = $this->get('packages')->create($packageInfo['package-type']);
                $package = $packageManager->createPackage($packageInfo['name']);
                $currentPackage = $package->getProperties()->toArray();
            }
            
            $this->setResponse($result,function() use($fileUploaded, $packageInfo, $destinationPath, $currentPackage, $packageDir) {            
                $this
                    ->message('package.upload')                  
                    ->field('file_uploaded',$fileUploaded)
                    ->field('package',$packageInfo)
                    ->field('destination',$destinationPath)
                    ->field('package_directory',$packageDir)
                    ->field('current',$currentPackage);   
                               
            },'errors.package.upload');
        });
        $data->validate();       
    }
}
