<?php


require_once 'lib/tmsDbExplorer.php';

$db = new tmsDbExplorer();


$db->updateDbScheme();
$db->buildModels();