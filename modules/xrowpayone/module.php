<?php

$Module = array( 'name' => 'xrowpayone' );

$ViewList = array();

$ViewList['errorurl'] = array( 'functions' => array( 'errorurl' ),
                            'script' => 'errorurl.php',
                            'unordered_params' => array( 'orderID' => 'orderID' ),
                            'params' => array());

$ViewList['successurl'] = array( 'functions' => array( 'successurl' ),
                            'script' => 'successurl.php',
                            'unordered_params' => array( 'orderID' => 'orderID' ),
                            'params' => array());

$ViewList['statuscheck'] = array( 'functions' => array( 'statuscheck' ),
                            'script' => 'statuscheck.php',
                            'unordered_params' => array(),
                            'params' => array());

$FunctionList = array();
$FunctionList['errorurl'] = array();
$FunctionList['successurl'] = array();
$FunctionList['statuscheck'] = array();

?>
