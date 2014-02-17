<?php

$require = function($path) {
  if (file_exists($dir = __DIR__."/../../../vendor")
    || file_exists($dir = __DIR__."/vendor")
  )
  {
      return require "$dir/$path";
  }
};

$require('autoload.php');
