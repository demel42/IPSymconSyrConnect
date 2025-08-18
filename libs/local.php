<?php

declare(strict_types=1);

trait SyrConnectLocalLib
{
    public static $IS_SERVERERROR = IS_EBASE + 12;
    public static $IS_HTTPERROR = IS_EBASE + 13;

    private function GetFormStatus()
    {
        $formStatus = $this->GetCommonFormStatus();

        $formStatus[] = ['code' => self::$IS_SERVERERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (server error)'];
        $formStatus[] = ['code' => self::$IS_HTTPERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (http error)'];

        return $formStatus;
    }

    public static $STATUS_INVALID = 0;
    public static $STATUS_VALID = 1;
    public static $STATUS_RETRYABLE = 2;

    private function CheckStatus()
    {
        switch ($this->GetStatus()) {
            case IS_ACTIVE:
                $class = self::$STATUS_VALID;
                break;
            case self::$IS_SERVERERROR:
            case self::$IS_HTTPERROR:
                $class = self::$STATUS_RETRYABLE;
                break;
            default:
                $class = self::$STATUS_INVALID;
                break;
        }

        return $class;
    }

    public static $DEVICE_TYPE_NONE = 0;
    public static $DEVICE_TYPE_TRIODFR_LS = 1;
    public static $DEVICE_TYPE_SAFETECH_PLUS = 2;
    public static $DEVICE_TYPE_NEOSOFT_2500 = 3;
    public static $DEVICE_TYPE_NEOSOFT_5000 = 4;

    public static $WLAN_STATE_DISCONNECTED = 0;
    public static $WLAN_STATE_CONNECTING = 1;
    public static $WLAN_STATE_CONNECTED = 2;

    public static $VALVE_STATE_CLOSED = 10;
    public static $VALVE_STATE_CLOSING = 11;
    public static $VALVE_STATE_OPEN = 20;
    public static $VALVE_STATE_OPENING = 21;

    public static $MICROLEAKAGE_TEST_INACTIVE = 0;
    public static $MICROLEAKAGE_TEST_ACTIVE = 1;
    public static $MICROLEAKAGE_TEST_ABORTED = 2;
    public static $MICROLEAKAGE_TEST_SKIPPED = 3;

    private function InstallVarProfiles(bool $reInstall = false)
    {
        if ($reInstall) {
            $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);
        }

        $associations = [
            ['Wert' => false, 'Name' => $this->Translate('open'), 'Farbe' => -1],
            ['Wert' => true, 'Name' => $this->Translate('close'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('SyrConnect.ValveAction', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [
            ['Wert' => false, 'Name' => $this->Translate('off'), 'Farbe' => -1],
            ['Wert' => true, 'Name' => $this->Translate('on'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('SyrConnect.Buzzer', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [
            ['Wert' => self::$VALVE_STATE_CLOSED, 'Name' => $this->Translate('closed'), 'Farbe' => -1],
            ['Wert' => self::$VALVE_STATE_CLOSING, 'Name' => $this->Translate('closing'), 'Farbe' => -1],
            ['Wert' => self::$VALVE_STATE_OPEN, 'Name' => $this->Translate('opened'), 'Farbe' => -1],
            ['Wert' => self::$VALVE_STATE_OPENING, 'Name' => $this->Translate('opening'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('SyrConnect.ValveState', VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [
            ['Wert' => 1, 'Name' => $this->Translate('Present'), 'Farbe' => -1],
            ['Wert' => 2, 'Name' => $this->Translate('Absent'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('SyrConnect.ActiveProfile', VARIABLETYPE_INTEGER, '', 1, 8, 0, 0, '', $associations, $reInstall);

        $associations = [
            ['Wert' => self::$MICROLEAKAGE_TEST_INACTIVE, 'Name' => $this->Translate('not active'), 'Farbe' => -1],
            ['Wert' => self::$MICROLEAKAGE_TEST_ACTIVE, 'Name' => $this->Translate('active'), 'Farbe' => -1],
            ['Wert' => self::$MICROLEAKAGE_TEST_ABORTED, 'Name' => $this->Translate('aborted'), 'Farbe' => -1],
            ['Wert' => self::$MICROLEAKAGE_TEST_SKIPPED, 'Name' => $this->Translate('skipped'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('SyrConnect.MicroleakageTestState', VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $this->CreateVarProfile('SyrConnect.Volume', VARIABLETYPE_FLOAT, ' l', 0, 0, 0, 1, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('SyrConnect.Pressure', VARIABLETYPE_FLOAT, ' bar', 0, 0, 0, 1, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('SyrConnect.Voltage', VARIABLETYPE_FLOAT, ' V', 0, 0, 0, 2, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('SyrConnect.Temperature', VARIABLETYPE_FLOAT, ' ºC', 0, 0, 0, 1, 'Gauge', [], $reInstall);

        $this->CreateVarProfile('SyrConnect.Flow', VARIABLETYPE_INTEGER, ' l/h', 0, 0, 0, 0, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('SyrConnect.Conductivity', VARIABLETYPE_INTEGER, ' µS/cm', 0, 0, 0, 0, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('SyrConnect.Seconds', VARIABLETYPE_INTEGER, ' s', 0, 0, 0, 0, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('SyrConnect.Hardness', VARIABLETYPE_INTEGER, ' °dH', 0, 0, 0, 0, 'Gauge', [], $reInstall);

        $associations = [
            ['Wert' => 0xA1, 'Name' => $this->Translate('Limit switch'), 'Farbe' => -1],
            ['Wert' => 0xA2, 'Name' => $this->Translate('Motor current'), 'Farbe' => -1],
            ['Wert' => 0xA3, 'Name' => $this->Translate('Volume leakage'), 'Farbe' => -1],
            ['Wert' => 0xA4, 'Name' => $this->Translate('Time leakage'), 'Farbe' => -1],
            ['Wert' => 0xA5, 'Name' => $this->Translate('Flow leakage'), 'Farbe' => -1],
            ['Wert' => 0xA6, 'Name' => $this->Translate('Suspected micro leakage'), 'Farbe' => -1],
            ['Wert' => 0xA7, 'Name' => $this->Translate('Floor sensor leakage'), 'Farbe' => -1],
            ['Wert' => 0xA8, 'Name' => $this->Translate('Flow sensor malfunction'), 'Farbe' => -1],
            ['Wert' => 0xA9, 'Name' => $this->Translate('Pressure sensor malfunction'), 'Farbe' => -1],
            ['Wert' => 0xAA, 'Name' => $this->Translate('Temperature sensor malfunction'), 'Farbe' => -1],
            ['Wert' => 0xAB, 'Name' => $this->Translate('Conductivity sensor malfunction'), 'Farbe' => -1],
            ['Wert' => 0xAC, 'Name' => $this->Translate('Conductivity sensor malfunction'), 'Farbe' => -1],
            ['Wert' => 0xAD, 'Name' => $this->Translate('Increased water hardness'), 'Farbe' => -1],
            ['Wert' => 0x0D, 'Name' => $this->Translate('Salt supply empty'), 'Farbe' => -1],
            ['Wert' => 0x0E, 'Name' => $this->Translate('Valve position'), 'Farbe' => -1],
            ['Wert' => 0xFF, 'Name' => '-', 'Farbe' => -1],
        ];
        $this->CreateVarProfile('SyrConnect.Alarm', VARIABLETYPE_INTEGER, '', 1, 8, 0, 0, '', $associations, $reInstall);

        $associations = [
            ['Wert' => 0x01, 'Name' => $this->Translate('Power interruption'), 'Farbe' => -1],
            ['Wert' => 0x07, 'Name' => $this->Translate('Leakage warning'), 'Farbe' => -1],
            ['Wert' => 0x08, 'Name' => $this->Translate('Batteries'), 'Farbe' => -1],
            ['Wert' => 0x02, 'Name' => $this->Translate('Salt supply'), 'Farbe' => -1],
            ['Wert' => 0x09, 'Name' => $this->Translate('Initial filling'), 'Farbe' => -1],
            ['Wert' => 0x10, 'Name' => $this->Translate('Volume leakage'), 'Farbe' => -1],
            ['Wert' => 0x11, 'Name' => $this->Translate('Time leakage'), 'Farbe' => -1],
            ['Wert' => 0xFF, 'Name' => '-', 'Farbe' => -1],
        ];
        $this->CreateVarProfile('SyrConnect.Warning', VARIABLETYPE_INTEGER, '', 1, 8, 0, 0, '', $associations, $reInstall);

        $associations = [
            ['Wert' => 0x01, 'Name' => $this->Translate('Update available'), 'Farbe' => -1],
            ['Wert' => 0x04, 'Name' => $this->Translate('Update installed'), 'Farbe' => -1],
            ['Wert' => 0x02, 'Name' => $this->Translate('Half-yearly maintenance'), 'Farbe' => -1],
            ['Wert' => 0x03, 'Name' => $this->Translate('Annual maintenance'), 'Farbe' => -1],
            ['Wert' => 0xFF, 'Name' => '-', 'Farbe' => -1],
        ];
        $this->CreateVarProfile('SyrConnect.Notification', VARIABLETYPE_INTEGER, '', 1, 8, 0, 0, '', $associations, $reInstall);
    }

    private function DeviceTypeMapping()
    {
        return [
            self::$DEVICE_TYPE_NONE          => 'none',
            // self::$DEVICE_TYPE_TRIODFR_LS    => 'TrioDFR LS',
            self::$DEVICE_TYPE_SAFETECH_PLUS => 'SafeTech +',
            // self::$DEVICE_TYPE_NEOSOFT_2500  => 'NeoSoft 2500',
            // self::$DEVICE_TYPE_NEOSOFT_5000  => 'NeoSoft 5000',
        ];
    }

    private function DeviceType2String($deviceType)
    {
        $deviceTypeMap = $this->DeviceTypeMapping();
        if (isset($deviceTypeMap[$deviceType])) {
            $s = $this->Translate($deviceTypeMap[$deviceType]);
        } else {
            $s = $this->Translate('Unknown model') . ' ' . $deviceType;
        }
        return $s;
    }

    private function DeviceTypeAsOptions()
    {
        $maps = $this->DeviceTypeMapping();
        $opts = [];
        foreach ($maps as $u => $e) {
            $opts[] = [
                'caption' => $e,
                'value'   => $u,
            ];
        }
        return $opts;
    }

    private function ValveStateMapping()
    {
        return [
            self::$VALVE_STATE_CLOSED  => 'closed',
            self::$VALVE_STATE_CLOSING => 'closing',
            self::$VALVE_STATE_OPEN    => 'open',
            self::$VALVE_STATE_OPENING => 'opening',
        ];
    }

    private function ValveState2String($valveState)
    {
        $valveStateMap = $this->ValveStateMapping();
        if (isset($valveStateMap[$valveState])) {
            $s = $valveStateMap[$valveState];
        } else {
            $s = $this->Translate('Unknown valve state') . ' ' . $valveState;
        }
        return $s;
    }

    private function WlanStateMapping()
    {
        return [
            self::$WLAN_STATE_DISCONNECTED => 'not connected',
            self::$WLAN_STATE_CONNECTING   => 'is being connected',
            self::$WLAN_STATE_CONNECTED    => 'connected',
        ];
    }

    private function WlanState2String($wlanState)
    {
        $wlanStateMap = $this->WlanStateMapping();
        if (isset($wlanStateMap[$wlanState])) {
            $s = $wlanStateMap[$wlanState];
        } else {
            $s = $this->Translate('Unknown valve state') . ' ' . $wlanState;
        }
        return $s;
    }

    private function MicroleakageTestStateStateMapping()
    {
        return [
            self::$MICROLEAKAGE_TEST_INACTIVE => 'not active',
            self::$MICROLEAKAGE_TEST_ACTIVE   => 'active',
            self::$MICROLEAKAGE_TEST_ABORTED  => 'aborted',
            self::$MICROLEAKAGE_TEST_SKIPPED  => 'skipped',
        ];
    }

    private function MicroleakageTestStateState2String($microleakageTestStateState)
    {
        $microleakageTestStateStateMap = $this->MicroleakageTestStateStateMapping();
        if (isset($microleakageTestStateStateMap[$microleakageTestStateState])) {
            $s = $microleakageTestStateStateMap[$microleakageTestStateState];
        } else {
            $s = $this->Translate('Unknown micro leakage state') . ' ' . $microleakageTestStateState;
        }
        return $s;
    }
}
