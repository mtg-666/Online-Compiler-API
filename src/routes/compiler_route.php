<?php
require_once '../vendor/autoload.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MyApp\Container\DockerContainer as DockerContainer;

$app->post('/api/v1/submit', function (Request $request, Response $response, array $args) {
  $params = (array)$request->getParsedBody();
  try{
    if(!(isset($params['code']))){
      throw new \Exception('PARAMETER MISSING: code');
    }
    if(!(isset($params['language']))){
      throw new \Exception('PARAMETER MISSING: language');
    }
    if(!isset($params['stdin'])){
      $params['stdin'] = " ";
    }
    
    $code = $params['code'];
    $language = $params['language'];
    $stdin = $params['stdin'];

    $languageObj=null;
    if($language == 'cpp'){
      $languageObj = new \MyApp\Languages\LanguageObj($code,'g++','cpp','./a.out',CPP_TIMEOUT,$stdin);
    }
    else if($language == 'python'){
      $languageObj = new \MyApp\Languages\LanguageObj($code,'python3','py',null,PYTHON_TIMEOUT,$stdin);
    }
    else if($language == 'c'){
      $languageObj = new \MyApp\Languages\LanguageObj($code,'gcc','c','./a.out',C_TIMEOUT,$stdin);
    }
    else if($language == 'java'){
      //for java seperate if statement has been written for as 'java Main'
      $languageObj = new \MyApp\Languages\LanguageObj($code,'javac','java','java',JAVA_TIMEOUT,$stdin);
    }
    else{
      throw new Exception($language . ' NOT FOUND');
    }

    $DockerContainer = new DockerContainer($languageObj->code,$languageObj->languageExtension,$languageObj->compilerName,$languageObj->outputCommand,$languageObj->stdin,$languageObj->timeout);
    $DockerContainer->executeContainer();
    if((($outputStr = file_get_contents(PARENT_FOLDER_PATH.'/tmp/'.$DockerContainer->getfolderName().'/output.txt',true)) !== false) 
    && (($errorStr = file_get_contents(PARENT_FOLDER_PATH.'/tmp/'.$DockerContainer->getfolderName().'/error.txt',true)) !== false)
    && (($runtimeSec = file_get_contents(PARENT_FOLDER_PATH.'/tmp/'.$DockerContainer->getfolderName().'/executionInfo.txt',true)) !== false)){
      $payload = json_encode(
                  array(
                      'api_status_code'=>'200',
                      'api_message'=>'Success',
                      'stderr'=>$errorStr,
                      'stdout'=>$outputStr,
                      'runtime'=>$runtimeSec
                      )
                  );
      $response->getBody()->write($payload);
	
      //after all the work delete the folder
      $deleteFolder = 'rm -r '. PARENT_FOLDER_PATH.'/tmp/'.$DockerContainer->getFolderName();
      exec($deleteFolder);

      //return the response
      return $response
              ->withHeader('Access-Control-Allow-Origin','*')
              ->withHeader('Content-Type', 'application/json');
    }
    else{
      throw new Exception('Internal Server Error');
    }
  }catch(\Exception $e){
    $api_status_code = 200;
    if($e->getMessage() === 'Internal Server Error'){
      $api_status_code = 500;
    }
 
    $payload = json_encode(array('api_status_code'=>$api_status_code,'api_message'=>$e->getMessage(),'stderr'=>'','stdout'=>'','runtime'=>''));
    $response->getBody()->write($payload);
    return $response
    ->withHeader('Content-Type', 'application/json')
    ->withHeader('Access-Control-Allow-Origin','*');
  }
  return $response;
});
