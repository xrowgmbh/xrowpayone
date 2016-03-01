<?php

$Module = array( 'name' => 'xrowpayone' );

$ViewList = array();

$ViewList['errorurl'] = array( 'functions' => array( 'errorurl' ),
                            'script' => 'errorurl.php',
                            'params' => array());

$ViewList['successurl'] = array( 'functions' => array( 'successurl' ),
                            'script' => 'successurl.php',
                            'params' => array());

$FunctionList = array();
$FunctionList['errorurl'] = array();
$FunctionList['successurl'] = array();

?>
