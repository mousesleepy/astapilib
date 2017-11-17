#!/usr/bin/env php
<?php

require_once('agi.php');

$AGI = new AGI();

ob_start();

var_dump($AGI->verbose('pizda', 1));
var_dump($AGI->Answer());
var_dump($AGI->Gosub('C-1',5,1));
var_dump($AGI->GetLastResponse());
//var_dump($AGI->StreamFile('testrec', '0'));
//var_dump($AGI->Exec('Musiconhold','conference,5'));


//var_dump($AGI->DatabaseGet('testf', 'testk'));


$D = ob_get_clean();
file_put_contents('/tmp/out.str', $D, true);
sleep(5);