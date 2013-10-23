<?
	
	IPSUtils_Include ('IPSLogger.inc.php',              'IPSLibrary::app::core::IPSLogger');
	IPSUtils_Include ("Security_Functions.inc.php", "IPSLibrary::app::modules::Security");

	if($IPS_SENDER == 'Variable') {
		$switchType = Security_getSwitchTypeFromVariable($IPS_VARIABLE);
		
		if($switchType === c_Variable_ID_Unlock) {
			$IPS_VALUE = 0;
		} else if($switchType === c_Variable_ID_Lock_Ext) {
			$IPS_VALUE = 1;
		} else if($switchType === c_Variable_ID_Lock_Int) {
			$IPS_VALUE = 2;
		} else {
			IPSLogger_Wrn(__file__, "Unknown switch type ".$switchType);
			return;
		}
	}

	Security_setAlarmMode($IPS_VALUE);
?>