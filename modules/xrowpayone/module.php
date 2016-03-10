<?php

$Module = array( 'name' => 'xrowpayone' );

$ViewList = array();

$ViewList['errorurl'] = array( 'functions' => array( 'errorurl' ),
                            'script' => 'errorurl.php',
                            'unordered_params' => array( 'orderID' => 'orderID', 'siteaccess' => 'siteaccess' ),
                            'params' => array());

$ViewList['successurl'] = array( 'functions' => array( 'successurl' ),
                            'script' => 'successurl.php',
                            'unordered_params' => array( 'orderID' => 'orderID', 'siteaccess' => 'siteaccess' ),
                            'params' => array());

$FunctionList = array();
$FunctionList['errorurl'] = array();
$FunctionList['successurl'] = array();

?>