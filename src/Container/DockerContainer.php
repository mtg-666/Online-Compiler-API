<?php 
namespace MyApp\Container;

class DockerContainer{
    private $code,$languageExtension,$stdin,$compilerName,$outputCommand;
    private $timeout;//set different timeout for different languages
    private $folderName,$fileName;//folder name for user 
    public function __construct($code,$languageExtension,$compilerName,$outputCommand,$stdin,$timeout){
      $this->code = $code;
      $this->languageExtension = $languageExtension;
      $this->compilerName = $compilerName;
      $this->outputCommand = $outputCommand;
      $this->stdin = $stdin;
      $this->timeout = $timeout;
    }

    public function getFolderName() : string{
      return $this->folderName;
    }

    private function prepareUserFolderAndFile(){
      //making unique user folderName
      $this->folderName = md5($this->code.time().rand(0,10).rand(0,10).rand(0,10)).'_'.time();
      $this->fileName = substr($this->folderName,0,5);
      if(!mkdir(PARENT_FOLDER_PATH."/tmp/$this->folderName/tmp",0777,true)){
        throw new \Exception('Internal Server Error');
      }

      //opening file in folder to write code
      if(!($file = fopen(PARENT_FOLDER_PATH."/tmp/$this->folderName/tmp/$this->fileName.$this->languageExtension",'w'))){ 
	throw new \Exception('Internal Server Error');
      }

      //writing code in file
      if(!fwrite($file,$this->code)){
        throw new \Exception('Internal Server Error');
      }
      fclose($file);
      $file=null;

      //opening file in folder to save input
      if(!($file = fopen(PARENT_FOLDER_PATH."/tmp/$this->folderName/tmp/input.txt",'w'))){ 
        throw new \Exception('Internal Server Error');
      }
      //writing input in file
      if(!fwrite($file,$this->stdin)){
        throw new \Exception('PARAMETER INPUT IS EMPTY');
      }
      fclose($file);
      $file=null;

      //copying the bash script
      copy(PARENT_FOLDER_PATH."/src/Container/runner.sh",PARENT_FOLDER_PATH."/tmp/$this->folderName/runner.sh");
    }

    private function prepareContainer() : string{
      $bashstmt = 'timeout -s SIGKILL 3 docker run --rm -d -it -v '."\"".VOLUME_PATH.'/'.$this->folderName.':/'.$this->folderName."\"".' '.
      DOCKER_IMAGE. ' bash '. $this->folderName . '/runner.sh ' . 
      " $this->timeout $this->folderName $this->compilerName $this->fileName.$this->languageExtension";
      if(!is_null($this->outputCommand)){
        $bashstmt = $bashstmt . " $this->outputCommand";
      }
      return $bashstmt;
    }

    public function executeContainer(){
      //prepare the folders and files and transfer the essiential bash script to folder in compiler/tmp/
      $this->prepareUserFolderAndFile();

      //error if occurs then should be redirect to log file in src/Container/errorlogs.txt
      $bashstmt = $this->prepareContainer() .' 2>>'. PARENT_FOLDER_PATH . '/src/Container/errorlogs.txt';
      
      //get output container id to variable(in case of successful docker container running)
      $containerId = exec($bashstmt);
      
      //if container was obtained, we should store it's id in txt file in src/Container/dockerContainer.txt
      if(!empty($containerId)){
        //IMPORTANT LINE WHICH SAVED ME
        //actually the exec in php was returning to script while container ran in the background because of which
        //script ran asynchronously and return the response before container could process the request 
        //This is such a big failure so to overcome that simple docker wait command blocks the terminal until 
        //the docker container is destroyed which helps us to make response synchronously after docker processing
        exec('docker wait '.$containerId);
      }
      else{
        throw new \Exception('Internal Server Error');
      }
    }
}
