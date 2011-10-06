<?php

/*
#===================================================================================
# BAM Manager (Bugzilla Automated Metrics Manager): index.php
#
# Copyright 2011, Comarch SA
# Maintainers: 	Krystian Jedrzejowski <krystian.jedrzejowski@comarch.com>,
# 				Kamil Marek <kamil.marek@comarch.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#
# Date: Thu Jul 13 11:56:00 EET 2011
#===================================================================================
*/

function checkPass($user, $pass)
{
  $login = $_POST['login'];
  if(!$fd = @fopen("users/$login.cl50cp1eoq9zj3scotij1a84", "r")) return 1;
  $result = 2;
  while (!feof($fd)){
  $line = trim(fgets($fd));
  $arr = explode(":", $line);
  if(count($arr)<2)
  continue;
 
  if($arr[0] != $user)
  continue;

    if($arr[1] == $pass){
    $result = 0;
    break;
    }

    else
    break;
  }
  fclose($fd);
  return $result;
}

session_start();
  if(isSet($_SESSION['logged in'])){
   header("Location:index.php");
}

  else if(!isSet($_POST["password"]) || !isSet($_POST["login"])){
   $_SESSION['notification'] = "Enter your username and password:";
   include('form.php');
  }
 
  else{
   $val = checkPass($_POST["login"], $_POST["password"]);
   if($val == 0){
   $_SESSION['logged in'] = $_POST['login'];
   header("Location:index.php");
   }
  
   else if($val == 1){
    $_SESSION['notification'] = "Server error. Cannot log in.!";
    include('form.php');
   }
  
   else if($val == 2){
    $_SESSION['notification'] = "Incorrect username or password";
    include('form.php');
   }
  
   else {
    $_SESSION['notification'] = "Server error. Cannot log in.";
    include('form.php');
   }
  }

?>