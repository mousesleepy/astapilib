<?php

require_once(__DIR__.DIRECTORY_SEPARATOR.'baseagi.php');

class AGI extends baseAGI
{
    public function __construct()
    {
	parent::__construct();
    }
    
    //подготовка параметров для передачи
    protected function make_params($inparams)
    {
	$retval = '';
	foreach ($inparams as $pname => $pval)
	{
	    if (is_null($pval))
	    {
		break;
	    }
	    $retval .= ' "'.$pval.'"';
	}
	return $retval;
    }

    //Logs a message to the asterisk verbose log.
    public function verbose ($message, $level)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'verbose';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    return TRUE;
	}
	return FALSE;
    }
    
    //Answer channel
    public function Answer()
    {
	$cmd = 'answer';
	$process_result = $this->ProcessCmd($cmd);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] == 0)
	    {
		return TRUE;
	    }
	}
	return FALSE;	
    }
    
    //Hangup a channel.
    public function Hangup($channelname = NULL)
    {
	$cmd = 'hangup';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] == 1)
	    {
		return TRUE;
	    }
	}
	return FALSE;	
    }
    
    //Does nothing.
    public function Noop()
    {
	$cmd = 'noop';
	$process_result = $this->ProcessCmd($cmd);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] == 1)
	    {
		return TRUE;
	    }
	}
	return FALSE;	
    }
    
    //Returns status of the connected channel.
    /*Return values:
    0 - Channel is down and available.
    1 - Channel is down, but reserved.
    2 - Channel is off hook.
    3 - Digits (or equivalent) have been dialed.
    4 - Line is ringing.
    5 - Remote end is ringing.
    6 - Line is up.
    7 - Line is busy.
    */
    public function ChannelStatus ($channelname = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'channel status';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] == -1)
	    {
		return FALSE;
	    }
	    return $process_result['result']['val'];
	}
	return FALSE;	
    }
    
    //Gets a channel variable.
    public function GetVariable ($variablename)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'get variable';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] == 1)
	    {
		return $process_result['result']['data'];
	    }
	}
	return FALSE;	
    }
    
    //Evaluates a channel expression
    public function GetFullVariable ($variablename, $channelname = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'get full variable';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] == 1)
	    {
		return $process_result['result']['data'];
	    }
	}
	return FALSE;	
    }
    
    //Sets a channel variable.
    public function SetVariable ($variablename , $value)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'Set variable';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    return TRUE;
	}
	return FALSE;	
    }
    
    //Adds/updates database value
    public function DatabasePut ($family, $key, $value)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'database put';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] == 1)
	    {
		return TRUE;
	    }
	}
	return FALSE;	
    }

    //Gets database value
    public function DatabaseGet ($family, $key)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'database get';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] == 1)
	    {
		return $process_result['result']['data'];
	    }
	}
	return FALSE;	
    }

    //Removes database key/value
    public function DatabaseDel ($family, $key)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'database del';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] == 1)
	    {
		return TRUE;
	    }
	}
	return FALSE;	
    }    
    
    //Removes database keytree/value
    public function DatabaseDeltree ($family, $keytree = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'get data';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] == 0) // в документации сказано, что должна быть еденица, на деле, все наоборот, возможно будет исправлено в будущем
	    {
		return TRUE;
	    }
	}
	return FALSE;	
    }    

    //Executes a given Application
    public function Exec ($application, $options = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'exec';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -2) 
	    {
		return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }    

    //Prompts for DTMF on a channel
    public function GetData ($file, $timeout = NULL, $maxdigits = NULL)
    {
	$timeout = $timeout * 1000;
	$params = $this->make_params(get_defined_vars());
	$cmd = 'get data';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }
        
    //Stream file, prompt for DTMF, with timeout.
    public function GetOption ($file, $escape_digits, $timeout = NULL)
    {
	$timeout = $timeout * 1000;
	$params = $this->make_params(get_defined_vars());
	$cmd = 'get option';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		return ['result' => $process_result['result']['val'], 'endpos' => $process_result['endpos']['val']];
	    }
	}
	return FALSE;	
    }

    //Sends audio file on channel.
    public function StreamFile ($filename, $escape_digits, $sample_offset = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'stream file';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		return ['result' => $process_result['result']['val'], 'endpos' => $process_result['endpos']['val']];
	    }
	}
	return FALSE;	
    }

    //Receives one character from channels supporting it.
    //нет возможности проверить
    public function ReceiveChar ($timeout)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'receive char';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }

    //Receives text from channels supporting it.
    //нет возможности проверить
    public function ReceiveText ($timeout)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'receive text';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		return $process_result['result']['data'];
	    }
	}
	return FALSE;	
    }

    //Records to a given file.
    public function RecordFile ($filename, $format, $escape_digits, $timeout = -1, $offset_samples = NULL, $BEEP = NULL, $silence = NULL)
    {
	if ($timeout != -1)
	{
	    $timeout = $timeout * 1000;
	}
	if ($silence !== NULL)
	{
	    $silence = 's='.$silence;
	}
	$params = $this->make_params(get_defined_vars());
	$cmd = 'record file';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		return ['result' => $process_result['result']['val'], 'endpos' => $process_result['endpos']['val']];
	    }
	}
	return FALSE;	
    }    
    
    //Says a given character string.
    public function SayAlpha ($number, $escape_digits = '')
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'say alpha';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		    return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }    

    //Says a given digit string.
    public function SayDigits ($number, $escape_digits = '')
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'say digits';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		    return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }    

    //Says a given number.
    public function SayNumber ($number, $escape_digits = '', $gender = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'say number';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		    return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }    

    //Says a given character string with phonetics.
    public function SayPhonetic ($number, $escape_digits = '')
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'say phonetic';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		    return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }    
    //Says a given date. (Unix time)
    public function SayDate ($date, $escape_digits = '')
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'say date';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		    return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }    

    //Says a given time. (Unix time)
    public function SayTime ($time, $escape_digits = '')
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'say time';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		    return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }    

    //Says a given time as specified by the format given. (почему то не работает, не понятен формат)
    public function SayDatetime ($time, $escape_digits = '', $format = NULL, $timezone = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'say datetime';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		    return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }    

    //Sends images to channels supporting it.
    public function SendImage ($image)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'send image';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		return TRUE;
	    }
	}
	return FALSE;	
    }    

    //Sends text to channels supporting it.
    public function SendText ($text_to_send)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'send text';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		return TRUE;
	    }
	}
	return FALSE;	
    }    
    
    //Autohangup channel in some time.
    public function SetAutohangup ($time)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'set autohangup';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    return TRUE;
	}
	return FALSE;	
    }    

    //Sets callerid for the current channel.
    public function SetCallerid ($number)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'set callerid';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    return TRUE;
	}
	return FALSE;	
    }    
    
    //Sets channel context.
    public function SetContext  ($context)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'set context';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    return TRUE;
	}
	return FALSE;	
    }    

    //Changes channel extension.
    public function SetExtension  ($extension)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'set extension';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    return TRUE;
	}
	return FALSE;	
    }    
    
    //Enable/Disable Music on hold generator
    public function SetMusicOn  ($class = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'set music on';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    return TRUE;
	}
	return FALSE;	
    }    
    
    //Enable/Disable Music on hold generator
    public function SetMusicOff  ($class = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'set music off';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    return TRUE;
	}
	return FALSE;	
    }    

    //Enable/Disable Music on hold generator
    public function SetPriority  ($priority)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'set priority';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    return TRUE;
	}
	return FALSE;	
    }    

    //Sends audio file on channel and allows the listener to control the stream.
    public function ControlStreamFile  ($filename, $escape_digits, $skipms = NULL, $ffchar = NULL, $rewchr = NULL, $pausechr = NULL, $offsetms = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'control stream file';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	   if ($process_result['result']['val'] != -1)
	    {
		return ['result' => $process_result['result']['val'], 'endpos' => $process_result['endpos']['val']];
	    }
	}
	return FALSE;	
    }    

    //Toggles TDD mode (for the deaf). параметры следует уточнить, не понятно что указывать
    public function TddMode ($mode) 
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'tdd mode';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] == 1)
	    {
		return TRUE;
	    }
	}
	return FALSE;	
    }
    
    //Waits for a digit to be pressed.
    public function WaitForDigit ($timeout = -1) 
    {
	if ($timeout != -1)
	{
	    $timeout = $timeout * 1000;
	}
	$params = $this->make_params(get_defined_vars());
	$cmd = 'wait for digit';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }
    
    //Cause the channel to execute the specified dialplan subroutine. Должным образом не проверялось,что должно возвращаться не понятно.
    public function Gosub ($context, $extension, $priority, $optional_argument = NULL) 
    {
	$params = $this->make_params(get_defined_vars());
	$cmd = 'gosub';
	$process_result = $this->ProcessCmd($cmd.$params);
	if ($process_result['code'] == 200)
	{
	    if ($process_result['result']['val'] != -1)
	    {
		return $process_result['result']['val'];
	    }
	}
	return FALSE;	
    }        
}
