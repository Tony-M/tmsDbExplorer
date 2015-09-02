<?php


require_once 'lib/tmsDbExplorer.php';

$db = new tmsDbExplorer();


$db->updateDbScheme(); //create structure from DB
//$db->loadDbStructure(); // load structure from cache
//$db->buildModels(array('Dict', 'log'));
$db->buildModels();