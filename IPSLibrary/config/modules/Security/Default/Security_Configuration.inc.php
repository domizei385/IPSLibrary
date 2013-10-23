<?
    IPSUtils_Include ("IPSLogger.inc.php", "IPSLibrary::app::core::IPSLogger");
    IPSUtils_Include ("IPSInstaller.inc.php", "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("Security_Functions.inc.php", "IPSLibrary::app::modules::Security");
    
	define('SMPT_MailId', 56262);
	
    function getMotionDevices() {
        $ret = array();
        
        // HM-IPS Motion ID, Name, Location
        $ret[] = array(
            c_Variable_ID    => 47256,
            c_Name           => "Motion Hallway",
            c_Location       => "Hallway"
        );
        
        return $ret;
    }
	
	function getSmokeDevices() {
        $ret = array();
        
        // Variable ID, Name, Location
        $ret[] = array(
            c_Variable_ID    => 18857,
            c_Name           => "Smoke",
            c_Location       => "Living Room"
        );
		
        $ret[] = array(
            c_Variable_ID    => 57419,
            c_Name           => "Smoke",
            c_Location       => "Kitchen"
        );
        
        return $ret;
    }
	
	function getClosureDevices() {
        $ret = array();
        
        // Variable ID, Name, Location
        $ret[] = array(
            c_Variable_ID    => 29529,
            c_Name           => "Fenster",
            c_Location       => "Bad"
        );
		
		$ret[] = array(
            c_Variable_ID    => 42269,
            c_Name           => "Fenster",
            c_Location       => "Küche"
        );
		
		$ret[] = array(
            c_Variable_ID    => 52728,
            c_Name           => "Fenster",
            c_Location       => "Schlafzimmer"
        );
		
		/*$ret[] = array(
            c_Variable_ID    => 52728,
            c_Name           => "Fenster links",
            c_Location       => "Wohnzimmer"
        );*/
		
		$ret[] = array(
            c_Variable_ID    => 36247,
            c_Name           => "Fenster rechts",
            c_Location       => "Wohnzimmer"
        );
		
		$ret[] = array(
            c_Variable_ID    => 35028,
            c_Name           => "Eingangstür",
            c_Location       => "Flur"
        );
        
        return $ret;
    }
	
	function getAlarmSwitches() {
		$ret = array();
        
        $ret[] = array(
            c_Variable_ID_Lock_Int => 55280,
			c_Variable_ID_Lock_Ext => 27270,
			c_Variable_ID_Unlock   => 33881,
            c_Name           	   => "Alarmkey 1"
        );
		
		$ret[] = array(
            c_Variable_ID_Lock_Int => 45233,
			c_Variable_ID_Lock_Ext => 37187,
			c_Variable_ID_Unlock   => 53127,
            c_Name           	   => "Alarmkey 2"
        );
		
		return $ret;
	}
	
	function getAlarmModes() {
		return array(
			v_ALARM_MODE_NAME => array("OFF", "PERIMETER", "ON"),
			v_ALARM_MODE_COLOR => array(0x800000, 0x808000, 0x008000),
		);
	}
	
	function getAlarmConditions() {
		$conditions = array();
		
		// Condition: notify when alarm is "ON" and the door was opened before motion was detected
		$hallwayMotion = new cVariable(47256);
		$hallwayDoor = new cVariable(35028);
		$conditions[] =  new cDefinition("Door opened and motion detected", 
			new cAnd(
				new cAlarmType("ON"),
				new cValue($hallwayMotion, true),
				new cOrder($hallwayDoor, $hallwayMotion)
			)
		);
		
		// Condition: notify when alarm is set to "PERIMETER" and the door was opened
		$conditions[] =  new cDefinition("Door opened in Perimeter mode", 
			new cAnd(
				new cAlarmType("PERIMETER"),
				new cValue($hallwayDoor, true)
			)
		);
		
		// Condition: raise alarm on every activation of a smoke device
		$smokeValueConditions = array();
		foreach(getSmokeDevices() as $smokeDevice) {
			$smokeVariable = new cVariable($smokeDevice[c_Variable_ID]);
			$smokeValueConditions[] = new cValue($smokeVariable, true);
		}
		$conditions[] = new cDefinition("Smoke detected", new cOr($smokeValueConditions));
		
		return $conditions;
	}

?>