<?
    IPSUtils_Include ("Entertainment_InterfaceYamaha.ips.php", "IPSLibrary::app::modules::Entertainment");
    IPSUtils_Include ('IPSLogger.inc.php',      'IPSLibrary::app::core::IPSLogger');

    // -------------------------------------------------------------------------------------------------
    // RemoteControl Button
    // -------------------------------------------------------------------------------------------------
    $vars = get_defined_vars();
    $commands = $vars['_GET'];
    
    foreach($commands as $command => $value) {
        //IPSLogger_Wrn(__file__, "Command: ".$command." = ".$value);
        if($value != 'undefined') {
            //IPSLogger_Wrn(__file__, "Setting: ".$command." = ".$value);
            ${$command} = $value;
        }
    }
    
    if ($_POST['rc_action']=="rc_cmd") {
        Yamaha_ReceiveData_Webfront($rc_name, $_POST['rc_button']);

        if ($_POST['rc_button2'] <> "") {
            Yamaha_ReceiveData_Webfront($rc_name2, $_POST['rc_button2']);
        }
        if ($_POST['rc_button3'] <> "") {
            Yamaha_ReceiveData_Webfront($rc_name3, $_POST['rc_button3']);
        }

    } else if ($rc_action == "rc_program") {
        echo Yamaha_ReceiveData_Program($_POST['rc_program'], $_POST['rc_devicename']);
    } else {
        IPSLogger_Wrn(__file__, "Received Unknown RemoteControl-Action '".$_POST['rc_action']."'");
    }

?>