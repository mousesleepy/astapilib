#!/usr/bin/env php
<?php
declare(ticks=10);
require_once('ami.php');
function TH($A,$B)
{
    var_dump($A,$B);
}
$AMI = new AMI(array('autorefresh' => TRUE,'logverbose' => 6));
$is_connected = $AMI->connect("127.0.0.1", "monast", "blabla");
$AMI->enable_events(TRUE);


//$AMI->add_event_handler('userevent', 'TH');
//$list = $AMI->SIPnotify('SIP/a14',Array('Event' => 'message-summary', 'Content-type' => 'application/simple-message-summary', 'Content' => "Messages-Waiting: yes Message-Account: sip:asterisk@127.0.0.1 Voice-Message: 1/1 (1/1)"));
//$AMI->SIPnotify('SIP/a14', 123);
//var_dump($AMI->DAHDIDialOffhook(40, 555));


$list = $AMI->DataGet('sip');

var_dump($list);
//sleep(5);
//var_dump($AMI->StopMixMonitor('SIP/a14-00020b37'));
//$AMI->Bridge('SIP/a14-000045bf', 'SIP/s03p38-000045bd');

while (TRUE)
{
    usleep(1000000);
   echo "*";

   //var_dump($AMI->getDeviceState("DAHDI/i2/89644321203")); 
   //echo memory_get_usage(TRUE),PHP_EOL; 

}

unset($AMI);