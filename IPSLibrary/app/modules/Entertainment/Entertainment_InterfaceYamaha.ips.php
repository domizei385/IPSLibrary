<?
    IPSUtils_Include ("Entertainment.inc.php",   "IPSLibrary::app::modules::Entertainment");
    IPSUtils_Include ("Yamaha_Constants.inc.php",   "IPSLibrary::app::modules::Entertainment");
    
    define("cmd_DELAY", "DELAY");
    define("cmd_HTTP_TYPE", "CMD_HTTP_TYPE");
    
    define("MAPPING_FUNCTION", "__function__");
    define("YAMAHA_LIST_PAGE_SIZE", 8);
    define("cmd_XML", "COMMAND_XML");
    define("cmd_ZONE", "COMMAND_ZONE");
    define("Message_Command_Parameter_Zone", '(Zone=%s) => %s');
    define("Message_Commands_Execution", 'Executing %d command%s took %.4f seconds.');

class YamahaCommand {
    public function __construct($name, $prefix, $suffix, $parameterMapping, $isInternal) {
        $this->name = $name;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        $this->mapping = $parameterMapping;
        $this->isInternal = $isInternal;
    }
    
    public function requiresMapping() {
        return $this->mapping != null;
    }
    
    public function __get($name) {
        if(isset($this->$name)) {
            return $this->$name;
        } else {
            throw new Exception("Variable or Handler for $name does not exist.",$name);
        }
    }
    
    public function __set($name, $value) {
        $this->$name = $value;
    }
}

class Yamaha_Receiver {
    
    private $urlFormat = "http://%s/YamahaRemoteControl/ctrl";
    
    private $targetUrl;
    private $commandScriptMapping;
    private $zoneMapping;
    private $commands = array();
    
    public function __construct($ip) {
        $this->targetUrl = sprintf($this->urlFormat, $ip);
        $this->setupCommands();
        $this->initInternalMapping();
    }
    
    private function buildCommand($cmd, $parameter) {
        $command = $this->commands[$cmd]->prefix;
        //IPSLogger_Com(__file__, "rawcmd:".print_r($cmd, true));
        //IPSLogger_Com(__file__, "rawMap:".print_r($this->commands[$cmd], true));
        if($this->commands[$cmd]->requiresMapping()) {
            $thisParameterMapping = $this->commands[$cmd]->mapping;
            if(isset($thisParameterMapping[MAPPING_FUNCTION])) {
                $command .= $thisParameterMapping[MAPPING_FUNCTION]($parameter);
            } else if(isset($thisParameterMapping[$parameter])) {
                $command .= $thisParameterMapping[$parameter];
            } else if($thisParameterMapping != "") {
                $command .= $thisParameterMapping;
            } else {
                // no mapping -> directly used supplied parameter
                $command .= $parameter;
            }
        }
        else {
            $command .= $parameter;
        }
        $command .= $this->commands[$cmd]->suffix;
        return $command;
    }
    
    private function buildCommandForZone($cmd, $parameter, $targetZone, $delay = 100) {
        return array(cmd_XML => $this->buildCommand($cmd, $parameter), cmd_ZONE => $targetZone, cmd_DELAY => $delay);
    }
    
    private function setupCommands() {
        $commands = array();
        $commands[] = new YamahaCommand("MUTE", "<Volume><Mute>", "</Mute></Volume>", array("ON" => "On", "OFF" => "Off"), false);
        $commands[] = new YamahaCommand("PWR", "<Power_Control><Power>", "</Power></Power_Control>", array("ON" => "On", "OFF" => "Standby"), false);
        $commands[] = new YamahaCommand("VOL", "<Volume><Lvl>", "</Lvl></Volume>",
            array(MAPPING_FUNCTION => function($val) {
                $rVal = (int) $val * 10;
                return "<Val>$rVal</Val><Exp>1</Exp><Unit>dB</Unit>";
            }), false);
        $commands[] = new YamahaCommand("INP", "<Input><Input_Sel>", "</Input_Sel></Input>", null, false);
        $commands[] = new YamahaCommand("CHAN", "<List_Control><Direct_Sel>", "</Direct_Sel></List_Control>",
            array(MAPPING_FUNCTION => function($val) {
                $iVal = intval($val);
                $item = $val % YAMAHA_LIST_PAGE_SIZE;
                return "Line_$item";
            }), false);
        $commands[] = new YamahaCommand("xNETRADIO_SELECT_LINE", "<List_Control><Direct_Sel>", "</Direct_Sel></List_Control>",
            array(MAPPING_FUNCTION => function($val) {
                $iVal = intval($val);
                $item = $val % YAMAHA_LIST_PAGE_SIZE;
                return "Line_$item";
            }), false);
        $commands[] = new YamahaCommand("xNETRADIO_JUMP_LINE", "<List_Control><Jump_Line>", "</Jump_Line></List_Control>",
            array(MAPPING_FUNCTION => function($val) {
                $iVal = intval($val);
                $page = $iVal;
                return $page;
            }), false);
        $commands[] = new YamahaCommand("xNETRADIO_JUMP_BACK", "<List_Control><Cursor>", "</Cursor></List_Control>", "back", false);
        $commands[] = new YamahaCommand("xNETRADIO_LIST_INFO", "<List_Info>", "</List_Info>",
            array(MAPPING_FUNCTION => function($val) {
                return "GetParam";
            }), false);
            
        foreach($commands as $command) {
            $this->commands[$command->name] = $command;
        }
    }
    
    private function getListStatus($zone) {
        $xCmd = $this->buildCommandForZone("xNETRADIO_LIST_INFO", null, $zone);
        $xCmd[cmd_HTTP_TYPE] = "GET";
        $reply = $this->executeCommands(array($xCmd));
        $content = $reply[0]["content"];
        //IPSLogger_Trc(__file__, "Content: ".print_r($content, true));
        $xml = new SimpleXMLElement($content);
        $listInfo = $xml->$zone->List_Info;
        return $listInfo;
    }
    
    private function initInternalMapping() {
        $this->commandScriptMapping = array(
            'CHAN'	=> function($cmd, $parameter, $zone) {
                $cmdArray = array();
                
                // get current list information
                $listInfo = $this->getListStatus($zone);
                IPSLogger_Trc(__file__, "Current Menu: ".((int) $listInfo->Menu_Layer)." - ".$listInfo->Menu_Name);
                
                // make sure we have "Bookmarks" selected
                if(((int) $listInfo->Menu_Layer) > 1 && $listInfo->Menu_Name != "Bookmarks") {
                    //IPSLogger_Trc(__file__, "Go To root folder");
                    // go to root folder
                    for($i = ((int) $listInfo->Menu_Layer); $i > 1; $i--) {
                        $cmdArray[] = $this->buildCommandForZone("xNETRADIO_JUMP_BACK", 1, $zone, 800);
                    }
                }
                
                if(((int) $listInfo->Menu_Layer) == 1) {
                    // go to bookmarks folder
                    //IPSLogger_Trc(__file__, "Go To bookmarks folder");
                    $cmdArray[] = $this->buildCommandForZone("xNETRADIO_SELECT_LINE", 1, $zone, 800);
                }
                
                // get the current cursor position and position it on the correct page
                $currentLine = $listInfo->Cursor_Position->Current_Line;
                $maxLine = $listInfo->Cursor_Position->Max_Line;
                
                // jump to the correct page in the current folder
                $currentPage = (int) ($currentLine / YAMAHA_LIST_PAGE_SIZE);
                $newPage = (int) ($parameter / YAMAHA_LIST_PAGE_SIZE);
                
                //IPSLogger_Trc(__file__, "MaxLine: ".$listInfo->Cursor_Position->Max_Line);
                //IPSLogger_Trc(__file__, "Parameter: ".$parameter);
                //IPSLogger_Trc(__file__, "Cline/Nline: ".$currentLine."/".$parameter);
                //IPSLogger_Trc(__file__, "Cpage/Npage: ".$currentPage."/".$newPage);
                if($currentPage != $newPage) {
                    //IPSLogger_Trc(__file__, "Changing page");
                    $cmdArray[] = $this->buildCommandForZone('xNETRADIO_JUMP_LINE', $parameter, $zone, 800);
                }
                
                // select the proper entry in the current folder and on the current page
                $cmdArray[] = $this->buildCommandForZone("xNETRADIO_SELECT_LINE", $parameter, $zone);
                return $cmdArray;
            },
        );
        
        $this->zoneMapping = array(
            "SYSTEM"	=> "System",
            "MAIN"		=> "Main_Zone",
            "ZONE2"		=> "Zone_2",
            "ZONE3"		=> "Zone_3",
            "ZONE4"		=> "Zone_4",
        );
    }
    
    public function execute($Parameters) {
        $hasArray = is_array($Parameters[1]);
        if($hasArray) {
            $parameterSet = $Parameters[1];
            if(isset($Parameters[2])) {
                $zone = $this->zoneMapping[$Parameters[2]];
            }
        } else {
            $parameterSet = array($Parameters[1] => $Parameters[2]);
            if(isset($Parameters[3])) {
                $zone = $this->zoneMapping[$Parameters[3]];
            }
        }
        
        $timeStart = microtime(true);
        $commandArray = array();
        foreach($parameterSet as $cmd => $parameter) {
            // TODO: should not map CHAN to NET_RADIO
            if(!isset($zone)) {
                if($cmd == 'CHAN') {
                    $zone = 'NET_RADIO';
                } else {
                    $zone = $this->zoneMapping["MAIN"];
                }
            }
            
            if(isset($this->commandScriptMapping[$cmd])) {
                $func = $this->commandScriptMapping[$cmd];
                //IPSLogger_Com(__file__, "Mapping:".print_r($this->commandScriptMapping, true));
                $cmds = $func($cmd, $parameter, $zone);
                foreach($cmds as $newCmd) {
                    array_push($commandArray, $newCmd);
                }
            } else {
                $commandArray[] = $this->buildCommandForZone($cmd, $parameter, $zone);
            }
        }
        $this->executeCommands($commandArray);
        
        $timeEnd = microtime(true);
        $elapsedSeconds = $timeEnd - $timeStart;
        $totalCommands = count($commandArray);
        
        //IPSLogger_Trc(__file__, sprintf(Message_Commands_Execution, $totalCommands, $totalCommands <> 1 ? 's' : '', $elapsedSeconds));
    }
    
    private function executeCommands($commandArray) {
        IPSLogger_Trc(__file__, "executeCommands> ".print_r($commandArray , true));
        
        $results = array();
        foreach($commandArray as $commandItem) {
            //IPSLogger_Com(__file__, sprintf(Message_Command_Parameter_Zone, $commandItem[cmd_ZONE], $commandItem[cmd_XML]));
            
            //IPSLogger_Com(__file__, "CommandItem: ".print_r($commandItem, true));
            if(array_key_exists(cmd_XML, $commandItem) && array_key_exists(cmd_ZONE, $commandItem)) {
                $result = $this->sendData($commandItem[cmd_XML], $commandItem[cmd_ZONE], isset($commandItem[cmd_HTTP_TYPE]) ? $commandItem[cmd_HTTP_TYPE] : "PUT");
                $results[] = $result;
            }
            
            // handle delay after command
            if(array_key_exists(cmd_DELAY, $commandItem)) {
                //IPSLogger_Trc(__file__, "Sleeping for ".$commandItem[cmd_DELAY] * 1000);
                usleep($commandItem[cmd_DELAY] * 1000);
            }
            
            // TODO: evaluate the return status
            IPSLogger_Trc(__file__, "Result: ".print_r($result["content"], true));
        }
        return $results;
    }
    
    public function putData($command, $zone = "Main_Zone") {
        return $this->sendData($command, $zone, "PUT");
    }
    
    public function getData($command, $zone = "Main_Zone") {
        return $this->sendData($command, $zone, "GET");
    }
    
    private function sendData($command, $zone = "Main_Zone", $type = "PUT") {
        $msg  = "<YAMAHA_AV cmd=\"$type\">";
        $msg .= "<$zone>";
        $msg .= $command;
        $msg .= "</$zone>";
        $msg .= '</YAMAHA_AV>';
        
        IPSLogger_Com(__file__, 'Send Message to Yamaha: '.$msg.' (Command='.$command.')');
        return post_request($this->targetUrl, $msg);
    }
}

//-------------------------------------------------------------------
    function Yamaha_SendData($Parameters) {
        //IPSLogger_Com(__file__, "XX: ".print_r($Parameters, true));
        
        $deviceName = $Parameters[0];
        $Devices = get_CommunicationConfiguration();
        $DeviceProperties = $Devices[$deviceName];
        $ip = $DeviceProperties[c_Property_IPAddress];
        $yamaha = new Yamaha_Receiver($ip);
        $yamaha->execute($Parameters);
    }
    
        // ---------------------------------------------------------------------------------------------------------------------------
    function Yamaha_ReceiveData($RemoteControl, $Button, $MessageType) {
        //WinLIRC_ReceiveData_Translation(&$RemoteControl, &$Button);
        $Parameters = array(c_Comm_Yamaha, $RemoteControl, $Button);
        if (!Entertainment_ReceiveData($Parameters, $MessageType)) {
            IPSLogger_Com(__file__, 'ReceiveData');
            if ($MessageType == c_MessageType_Action) {
                Yamaha_SendData($Parameters);
            }
        }
    }

    // ---------------------------------------------------------------------------------------------------------------------------
    function Yamaha_ReceiveData_Webfront($RemoteControl, $Button) {
        IPSLogger_Com(__file__, "Received Data from WebFront, Control='$RemoteControl', Command='$Button'");
        Yamaha_ReceiveData($RemoteControl, $Button, c_MessageType_Action);
    }

    // ---------------------------------------------------------------------------------------------------------------------------
    function Yamaha_ReceiveData_Program($Program, $DeviceName) {
        IPSLogger_Com(__file__, "Received Program '$Program' from Webfront, Device='$DeviceName'");
        $ControlId = get_ControlIdByDeviceName($DeviceName, c_Control_Program);
        if ($Program == 'next') {
            Entertainment_SetProgramNext($ControlId);
        } else if ($Program == 'prev') {
            Entertainment_SetProgramPrev($ControlId);
        } else {
            Entertainment_SetProgram($ControlId, $Program);
        }
        return GetValue($ControlId);
    }
    
    function post_request($url, $data, $referer = '') {
        //IPSLogger_Trc(__file__, $url." - ".print_r($data, true));
        
        $url = parse_url($url);
        
        if ($url['scheme'] != 'http') { 
            die('Error: Only HTTP request are supported !');
        }
        
        $host = $url['host'];
        $path = $url['path'];
        
        // open a socket connection on port 80 - timeout: 5 sec
        $fp = @fsockopen($host, 80, $errno, $errstr, 5);
        
        if ($fp) {
            fputs($fp, "POST $path HTTP/1.1\r\n");
            fputs($fp, "Host: $host\r\n");
            
            if ($referer != '')
                fputs($fp, "Referer: $referer\r\n");
            
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-length: ". strlen($data) ."\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $data);
            
            $result = ''; 
            while(!feof($fp)) {
                // receive the results of the request
                $result .= fgets($fp, 128);
            }
        }
        else { 
            return array(
                'status' => 'err', 
                'error' => "$errstr ($errno)"
            );
        }
        
        // close the socket connection
        fclose($fp);
        
        // split the result header from the content
        $result = explode("\r\n\r\n", $result, 2);
     
        $header = isset($result[0]) ? $result[0] : '';
        $content = isset($result[1]) ? $result[1] : '';
		
        // return result array
        return array(
            'status' => 'ok',
            'header' => $header,
            'content' => $content
        );
    }


?>