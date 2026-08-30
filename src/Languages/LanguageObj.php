<?php
namespace MyApp\Languages;

/**
 * To add new language support make sure that you fill all the below class values in compiler_route.php
 * For $timeout you can go into config/default_timeout.php to add constant timeout for that 
 * language
 * If any special compilation flags or output command flags are needed then you can add it in runner.sh
 * E.g for java i have used -d flag so that javac has its own if() condition bash file 
 */

final class LanguageObj{
  public $code,$compilerName, $languageExtension, $outputCommand, $timeout, $stdin;
  function __construct($code,$compilerName,$languageExtension,$outputCommand,$timeout,$stdin){
    $this->code=$code;
    $this->compilerName=$compilerName;
    $this->languageExtension=$languageExtension;
    $this->outputCommand=$outputCommand;
    $this->timeout=$timeout;
    $this->stdin=$stdin;
  }
}