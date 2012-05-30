<?
    /**@addtogroup ipscomponent
     * @{
     *
     *
     * @file          IPSComponentSwitch_NetIO230B.class.php
     * @author        Dominik Zeiger
     *
     *
     */

   /**
    * @class IPSComponentSwitch_NetIO230B
    *
    * Definiert ein IPSComponentSwitch_NetIO230B Object, das ein IPSComponentSwitch Object f�r Homematic implementiert.
    *
    * @author Dominik Zeiger
    * @version
    * Version 2.50.1, 31.01.2012<br/>
    */

    IPSUtils_Include ('IPSComponentSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');
    IPSUtils_Include ('IPSLogger.inc.php',      'IPSLibrary::app::core::IPSLogger');
    IPSUtils_Include ("Device_AbstractPowerSwitch.inc.php", "IPSLibrary::app::modules::PowerSwitch");
    IPSUtils_Include ('Device_NetIO230.inc.php',      'IPSLibrary::app::modules::PowerSwitch');

    class IPSComponentSwitch_NetIO230B extends IPSComponentSwitch {

        private $instanceId;
    
        /**
         * @public
         *
         * Initialisierung eines IPSComponentSwitch_NetIO230B Objektes
         *
         * @param integer $instanceId InstanceId des NetIO230B Devices
         */
        public function __construct($instanceId) {
            $this->instanceId = IPSUtil_ObjectIDByPath($instanceId);
        }

        /**
         * @public
         *
         * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
         * an das entsprechende Module zu leiten.
         *
         * @param integer $variable ID der ausl�senden Variable
         * @param string $value Wert der Variable
         * @param IPSModuleSwitch $module Module Object an das das aufgetretene Event weitergeleitet werden soll
         */
        public function HandleEvent($variable, $value, IPSModuleSwitch $module){
        }

        /**
         * @public
         *
         * Zustand Setzen 
         *
         * @param boolean $value Wert f�r Schalter
         */
        public function SetState($value) {
            IPSLogger_Trc(__file__, "Switching ".$this->instanceId.": ".$value);
            NetIO_setPortStatus($this->instanceId, $value);
        }

        /**
         * @public
         *
         * Liefert aktuellen Zustand
         *
         * @return boolean aktueller Schaltzustand  
         */
        public function GetState() {
            IPSLogger_Trc(__file__, "Getting statei for ".$this->instanceId);
            return GetValue($this->instanceId);
        }

    }

    /** @}*/
?>