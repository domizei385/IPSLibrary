<?
    /**@defgroup security_installation Security Installation
     * @ingroup security
     * @{
     *
     * Installations File für den Security
     *
     * @section requirements_security Installations Voraussetzungen Security
     * - IPS Kernel >= 2.50
     * - IPSModuleManager >= 2.50.1
     *
     * @section visu_security Visualisierungen für Security
     * - WebFront 10Zoll
     * - Mobile
     *
     * @page install_security Installations Schritte
     * Folgende Schritte sind zur Installation der EDIP Ansteuerung nötig:
     * - Laden des Modules (siehe IPSModuleManager)
     * - Konfiguration (Details siehe Konfiguration, Installation ist auch ohne spezielle Konfiguration möglich)
     * - Installation (siehe IPSModuleManager)
     *
     * @file          Security_Installation.ips.php
     * @author        Dominik Zeiger
     * @version
     *  Version 0.0.001, 31.01.2012<br/>
     *
     */

    if (!isset($moduleManager)) {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

        echo 'ModuleManager Variable not set --> Create "default" ModuleManager'.PHP_EOL;
        $moduleManager = new IPSModuleManager('Security');
    }

    $moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
    $moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.2');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

    IPSUtils_Include ("IPSInstaller.inc.php",           "IPSLibrary::install::IPSInstaller");
    IPSUtils_Include ("Security_Configuration.inc.php", "IPSLibrary::config::modules::Security");
    
    $WFC10_Enabled          = $moduleManager->GetConfigValue('Enabled', 'WFC10');
    $WFC10_ConfigId         = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
    $WFC10_Path             = $moduleManager->GetConfigValue('Path', 'WFC10');
    $WFC10_TabPaneItem      = $moduleManager->GetConfigValue('TabPaneItem', 'WFC10');
    $WFC10_TabPaneParent    = $moduleManager->GetConfigValue('TabPaneParent', 'WFC10');
    $WFC10_TabPaneName      = $moduleManager->GetConfigValue('TabPaneName', 'WFC10');
    $WFC10_TabPaneOrder     = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10');
    $WFC10_TabPaneIcon      = $moduleManager->GetConfigValue('TabPaneIcon', 'WFC10');
    $WFC10_TabPaneExclusive = $moduleManager->GetConfigValueBoolDef('TabPaneExclusive', 'WFC10', false);
    
    $WFC10_TabItem1         = $moduleManager->GetConfigValue('TabItem1', 'WFC10');
    $WFC10_TabName1         = $moduleManager->GetConfigValue('TabName1', 'WFC10');
    $WFC10_TabIcon1         = $moduleManager->GetConfigValue('TabIcon1', 'WFC10');
    $WFC10_TabOrder1        = $moduleManager->GetConfigValueInt('TabOrder1', 'WFC10');

    $Mobile_Enabled         = $moduleManager->GetConfigValue('Enabled', 'Mobile');
    $Mobile_Path            = $moduleManager->GetConfigValue('Path', 'Mobile');
    $Mobile_PathOrder       = $moduleManager->GetConfigValueInt('PathOrder', 'Mobile');
    $Mobile_PathIcon        = $moduleManager->GetConfigValue('PathIcon', 'Mobile');
    $Mobile_Name1           = $moduleManager->GetConfigValue('Name1', 'Mobile');
    $Mobile_Order1          = $moduleManager->GetConfigValueInt('Order1', 'Mobile');
    $Mobile_Icon1           = $moduleManager->GetConfigValue('Icon1', 'Mobile');

    // ----------------------------------------------------------------------------------------------------------------------------
    // Program Installation
    // ----------------------------------------------------------------------------------------------------------------------------
    $CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
    $CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdConfig   = $moduleManager->GetModuleCategoryID('config');
    
    // Get Scripts Ids
    $ID_ScriptSecurity = IPS_GetScriptIDByName('Security', $CategoryIdApp);
    $ID_ScriptSecurityMotionHandler = IPS_GetScriptIDByName('Security_MotionHandler', $CategoryIdApp);
	$ID_ScriptSecuritySmokeHandler = IPS_GetScriptIDByName('Security_SmokeHandler', $CategoryIdApp);
	$ID_ScriptSecurityClosureHandler = IPS_GetScriptIDByName('Security_ClosureHandler', $CategoryIdApp);
    $ID_ScriptSecurityEnableDisableAlarm = IPS_GetScriptIDByName('Security_EnableDisableAlarm', $CategoryIdApp);
    
    // TODO: enable logging
	createAlarmModeVariable($CategoryIdData, $ID_ScriptSecurityEnableDisableAlarm);
	CreateVariable(cat_MOTION."Log", 3 /* String */, $CategoryIdData, 0, "~HTMLBox", false, false);
	CreateVariable(cat_SMOKE."Log", 3 /* String */, $CategoryIdData, 0, "~HTMLBox", false, false);
	CreateVariable(cat_CLOSURE."Log", 3 /* String */, $CategoryIdData, 0, "~HTMLBox", false, false);
	CreateVariable(cat_SWITCHES."Log", 3 /* String */, $CategoryIdData, 0, "~HTMLBox", false, false);
	
	createCategoryAndDevices($CategoryIdData, cat_MOTION, getMotionDevices(), $ID_ScriptSecurityMotionHandler);
	createCategoryAndDevices($CategoryIdData, cat_SMOKE, getSmokeDevices(), $ID_ScriptSecuritySmokeHandler);
	createCategoryAndDevices($CategoryIdData, cat_CLOSURE, getClosureDevices(), $ID_ScriptSecurityClosureHandler);
	
	// configure alarm switches
	$alarmSwitches = getAlarmSwitches();
	$typeCategoryId = CreateCategory(cat_SWITCHES, $CategoryIdData, 50);
	foreach($alarmSwitches as $id => $deviceConfig) {
		$unlockId = $deviceConfig[c_Variable_ID_Unlock];
		$lockIntId = $deviceConfig[c_Variable_ID_Lock_Int];
		$lockExtId = $deviceConfig[c_Variable_ID_Lock_Ext];
		
		echo "Creating device ".$deviceConfig[c_Name]." in $typeCategoryId for ($unlockId, $lockIntId, $lockExtId) \n";
		$CategoryIdDevice = CreateCategory($deviceConfig[c_Name], $typeCategoryId, 50);
		CreateVariable("Last".c_Variable_ID_Unlock, 3 /*String*/, $CategoryIdDevice, 10, "~HTMLBox");
		CreateVariable("Last".c_Variable_ID_Lock_Int, 3 /*String*/, $CategoryIdDevice, 20, "~HTMLBox");
		CreateVariable("Last".c_Variable_ID_Lock_Ext, 3 /*String*/, $CategoryIdDevice, 30, "~HTMLBox");
		
		$eventId = CreateEvent($deviceConfig[c_Name].'('.$unlockId.") - On ".c_Variable_ID_Unlock, $unlockId, $ID_ScriptSecurityEnableDisableAlarm, 0);
		$eventId = CreateEvent($deviceConfig[c_Name].'('.$lockIntId.") - On ".c_Variable_ID_Lock_Int, $lockIntId, $ID_ScriptSecurityEnableDisableAlarm, 0);
		$eventId = CreateEvent($deviceConfig[c_Name].'('.$lockExtId.") - On ".c_Variable_ID_Lock_Ext, $lockExtId, $ID_ScriptSecurityEnableDisableAlarm, 0);
	}
	
	function createCategoryAndDevices($parentCategory, $type, $devices, $handlerScriptId) {
		$typeCategoryId = CreateCategory($type, $parentCategory, 50);
		foreach($devices as $deviceNumber => &$deviceConfig) {
			$deviceId = $deviceConfig[c_Variable_ID];
		
			echo "Creating device ".$deviceConfig[c_Name]." (Location: ".$deviceConfig[c_Location].") in $typeCategoryId for $deviceId \n";
			$CategoryIdDevice = CreateCategory($deviceId, $typeCategoryId, 50);
			CreateVariable("Last".$type, 3 /*String*/, $CategoryIdDevice, 10, "~HTMLBox");
			
			$eventId = CreateEvent($deviceId." - On ".$type, $deviceId, $handlerScriptId);
		}
	}
	
	function createProfile($Name, $suffix, $typ, $digits = 0) {
		@IPS_DeleteVariableProfile($Name);
		IPS_CreateVariableProfile($Name, $typ);
		IPS_SetVariableProfileText($Name, "", $suffix);
		IPS_SetVariableProfileValues($Name, 0, 0, 0);

		if($digits > 0) {
			IPS_SetVariableProfileDigits($Name, $digits);
		}
	}
	
	function createAlarmModeVariable($parentCategory, $scriptId) {
		$alarmModes = getAlarmModes();
		CreateProfile_Associations("Security_AlarmModes", $alarmModes[v_ALARM_MODE_NAME], "", $alarmModes[v_ALARM_MODE_COLOR]);
		
		CreateVariable(v_ALARM_MODE, 1 /*Integer*/, $parentCategory, 0, "Security_AlarmModes", $scriptId, 0);
	}
	
    return;
    
    // ----------------------------------------------------------------------------------------------------------------------------
    // Webfront Installation
    // ----------------------------------------------------------------------------------------------------------------------------
    if ($WFC10_Enabled) {
        $ID_CategoryWebFront        = CreateCategoryPath($WFC10_Path);
        EmptyCategory($ID_CategoryWebFront);
        $ID_CategoryOutput          = CreateCategory('Security', $ID_CategoryWebFront, 10);
        $ID_CategoryLeft            = CreateCategory('Left',     $ID_CategoryOutput, 10);
        $ID_CategoryRight           = CreateCategory('Right',    $ID_CategoryOutput, 20);

        $UniqueId = date('Hi');
        $baseName = $WFC10_TabPaneItem.'_'.$WFC10_TabPaneName;
        DeleteWFCItems($WFC10_ConfigId, $baseName);
        DeleteWFCItems($WFC10_ConfigId, $baseName.'_OvSP');
        
        CreateWFCItemTabPane   ($WFC10_ConfigId, $baseName,                          $WFC10_TabPaneItem,         $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);
        CreateWFCItemSplitPane ($WFC10_ConfigId, $baseName.'_OvSP',                  $baseName, 0, $WFC10_TabName1, $WFC10_TabIcon1, 1 /*Vertical*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0 /*Percent*/, 'true');
        CreateWFCItemCategory  ($WFC10_ConfigId, $baseName.'_OvCatLeft'.$UniqueId,   $baseName.'_OvSP', $WFC10_TabOrder1, $WFC10_TabName1, $WFC10_TabIcon1, $ID_CategoryLeft /*BaseId*/, 'false' /*BarBottomVisible*/);
        CreateWFCItemCategory  ($WFC10_ConfigId, $baseName.'_OvCatRight'.$UniqueId, $baseName.'_OvSP', $WFC10_TabOrder1, $WFC10_TabName1, $WFC10_TabIcon1, $ID_CategoryRight /*BaseId*/, 'false' /*BarBottomVisible*/);
        
        $count = count($devices);
        if($count == 1) {
            foreach($devices as $device) {
                CreateLink($device[DEVICE_IP]." - Receive", $device["RECEIVE_ID"], $ID_CategoryLeft, 10);
                CreateLink($device[DEVICE_IP]." - Send", $device["SEND_ID"], $ID_CategoryLeft, 10);
            }
            // Dect Status
            $dectChildren = IPS_GetChildrenIDs($device["DECT_ID"]);
            $i = 0;
            foreach($dectChildren as $dectChild) {
                $dectName = GetValueString(IPS_GetObjectIDByName("Name", $dectChild));
                CreateLink($device[DEVICE_IP]." - ".$dectName, $dectChild, $ID_CategoryRight, $i++);
            }
        } else {
            // TODO: create categories?
            foreach($devices as $device) {
                CreateLink($device[DEVICE_IP]." - Receive", $device["RECEIVE_ID"], $ID_CategoryLeft, 10);
                CreateLink($device[DEVICE_IP]." - Send", $device["SEND_ID"], $ID_CategoryLeft, 10);
            }
            //CreateLink($device[DEVICE_IP]." - DECT Status", $device["DECT_ID"], $ID_CategoryRight, 50);
        }

        ReloadAllWebFronts();
    }

    // ----------------------------------------------------------------------------------------------------------------------------
    // iPhone Installation
    // ----------------------------------------------------------------------------------------------------------------------------
    if ($Mobile_Enabled) {
        $ID_CategoryiPhone    = CreateCategoryPath($Mobile_Path, $Mobile_PathOrder, $Mobile_PathIcon);
        
        foreach($devices as $device) {
            CreateLink("Security@".$device[DEVICE_IP], $device["STATE_ID"],    $ID_CategoryiPhone, 10);
        }
    }
	
    /** @}*/
?>