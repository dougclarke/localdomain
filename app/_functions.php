<?php

function got_podman(){
  $output = null;
  if(exec('podman version 2> /dev/null', $output)) return $output[0];
  else return false;
}

function _pw($l=32){
  $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!-.[]?*()';
  $pw = '';
  $len = mb_strlen($chars, '8bit') - 1;
  foreach(range(1, $l) as $i){
    $pw .= $chars[random_int(0, $len)];
  }
  return $pw;
}
