<?
    IPSUtils_Include ('IPSLogger.inc.php',              'IPSLibrary::app::core::IPSLogger');
    IPSUtils_Include ("IPSInstaller.inc.php",           "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("Security_Language.inc.php", "IPSLibrary::app::modules::Security");
    
	function Security_getMotionDevices() {
		IPSUtils_Include("Security_Configuration.inc.php", "IPSLibrary::config::modules::Security");
		$devices = getMotionDevices();
		return $devices;
	}
	
	function Security_getSmokeDevices() {
		IPSUtils_Include("Security_Configuration.inc.php", "IPSLibrary::config::modules::Security");
		$devices = getSmokeDevices();
		return $devices;
	}
	
	function Security_getClosureDevices() {
		IPSUtils_Include("Security_Configuration.inc.php", "IPSLibrary::config::modules::Security");
		$devices = getClosureDevices();
		return $devices;
	}
	
	function Security_getConfigById($configs, $variableId) {
		foreach($configs as $config) {
			if($config[c_Variable_ID] == $variableId) {
				return $config;
			}
		}
	}
	
    function Security_getMotionConfigById($variableId) {
		$configs = Security_getMotionDevices();
		return Security_getConfigById($configs, $variableId);
	}
	
	function Security_getSmokeConfigById($variableId) {
		$configs = Security_getSmokeDevices();
		return Security_getConfigById($configs, $variableId);
	}
	
	function Security_logEvent($event) {
		$type = $event["type"];
		$device = $event["device"];
		
		// update device motion log with plain time stamp
		$dataPath = "Program.IPSLibrary.data.modules.Security.".$type.".".$device[c_Variable_ID];
		$idDeviceData = get_ObjectIDByPath($dataPath);
		
		$formattedDate = date("Y-m-d H:i:s", $event["timestamp"]);
		
		$lastEventId = IPS_GetVariableIDByName("Last".$type, $idDeviceData);
		
		$oldValue = substr(GetValueString($lastEventId), 0, 4997);
		SetValueString($lastEventId, $formattedDate."<br>".$oldValue);
		
		// update global motion log with text
		$dataPath = "Program.IPSLibrary.data.modules.Security.".$type."Log";
		$idGlobalLog = get_ObjectIDByPath($dataPath);
		//IPSLogger_Trc(__file__, "Event Log Id ".$idGlobalLog);
		
		$value = substr(GetValueString($idGlobalLog), 0, 4997);
		$newValue = sprintf(getLang("ALARM_".$type."_DETECTED_BODY"), $formattedDate, $device[c_Name], $device[c_Location]);
		
		SetValueString($idGlobalLog, $newValue."<br>".$value);
	}
	
	function Security_getAlarmActiveId() {
		$dataPath = "Program.IPSLibrary.data.modules.Security.ALARM_ACTIVE";
		$varId = get_ObjectIDByPath($dataPath);
		
		if($varId === FALSE) {
			throw new Exception("Variable ALARM_ACTIVE missing");
		}
		
		return $varId;
	}
	
	function Security_enabledDisableAlarm($state) {
		IPSLogger_Inf(__file__, $state ? "Enabling Alarm" : "Disabling Alarm");
	
		$varId = Security_getAlarmActiveId();
		SetValueBoolean($varId, $state);
	}
	
	function Security_isAlarmEnabled() {
		$value = GetValueBoolean(Security_getAlarmActiveId());
		return $value;
	}
	
	function Security_raiseAlarm($event) {
		$type = $event["type"];
		
		IPSLogger_Dbg(__file__, "Sending $type ALARM notification via mail");
		
		$device = $event["device"];
		$formattedDate = date("Y-m-d H:i:s", $event["timestamp"]);
		// device motion log
		$dataPath = "Program.IPSLibrary.data.modules.Security.".$type.".".$device[c_Variable_ID];
		$idDeviceData = get_ObjectIDByPath($dataPath);
		$lastEventId = IPS_GetVariableIDByName("Last".$type, $idDeviceData);
		$logEntries = str_replace("<br>", "\n", GetValueString($lastEventId));
		
		$body = sprintf(getLang("ALARM_".$type."_DETECTED_BODY_HISTORY"), $formattedDate, $device[c_Name], $device[c_Location], $logEntries);
		SMTP_SendMail(SMPT_MailId, getLang("ALARM_".$type."_DETECTED_HEADER"), $body);
	}
    
?>