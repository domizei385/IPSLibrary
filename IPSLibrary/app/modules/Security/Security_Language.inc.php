<?
    function getLang($langId) {
		$langId = strtoupper($langId);
	
		$langString["OPENED"] = "ge�ffnet";
		$langString["CLOSED"] = "geschlossen";
		
		$langString["ALARM_DETECTED_MAIL_SUFFIX"] = "\n\nTrace: \n%s\n\nEreignisse: \n%s";
	
		$langString["ALARM_MOTION_DETECTED_HEADER"] = "ALARM - Bewegung erkannt";
		$langString["ALARM_MOTION_DETECTED_BODY"] = "%s: Bewegung durch '%s' am Ort '%s' erkannt.";
		$langString["ALARM_MOTION_START_DETECTED_BODY"] = "%s: Bewegung durch '%s' am Ort '%s' erkannt (START).";
		$langString["ALARM_MOTION_STOP_DETECTED_BODY"] = "%s: Bewegung durch '%s' am Ort '%s' erkannt (ENDE).";
		$langString["ALARM_MOTION_DETECTED_BODY_HISTORY"] = $langString["ALARM_MOTION_DETECTED_BODY"].$langString["ALARM_DETECTED_MAIL_SUFFIX"];
		
		$langString["ALARM_SMOKE_DETECTED_HEADER"] = "ALARM - Rauch erkannt";
		$langString["ALARM_SMOKE_DETECTED_BODY"] = "%s: Rauch durch '%s' am Ort '%s' erkannt.";
		$langString["ALARM_SMOKE_DETECTED_BODY_HISTORY"] = $langString["ALARM_SMOKE_DETECTED_BODY"].$langString["ALARM_DETECTED_MAIL_SUFFIX"];
		
		$langString["ALARM_CLOSURE_DETECTED_HEADER"] = "ALARM - �ffnung erkannt";
		$langString["ALARM_CLOSURE_DETECTED_BODY"] = "%s: �ffnung von '%s' am Ort '%s' erkannt.";
		$langString["ALARM_CLOSURE_OPEN_DETECTED_BODY"] = "%s: �ffnung von '%s' am Ort '%s' erkannt.";
		$langString["ALARM_CLOSURE_CLOSE_DETECTED_BODY"] = "%s: Schlie�ung von '%s' am Ort '%s' erkannt.";
		$langString["ALARM_CLOSURE_DETECTED_BODY_HISTORY"] = $langString["ALARM_CLOSURE_DETECTED_BODY"].$langString["ALARM_DETECTED_MAIL_SUFFIX"];
		
		if(isset($langString[$langId])) {
			return $langString[$langId];
		}
		
		IPSLogger_Wrn(__file__, "No language string for language id '".$langId."' defined.");
		return $langId;
	}
?>