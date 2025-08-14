<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class SyrConnect extends IPSModule
{
    use SyrConnect\StubsCommonLib;
    use SyrConnectLocalLib;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonConstruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('host', '');
        $this->RegisterPropertyInteger('port', 5333);

        $this->RegisterPropertyInteger('device_type', self::$DEVICE_TYPE_NONE);

        $this->RegisterPropertyInteger('update_interval', 60);

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->RegisterTimer('UpdateStatus', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateStatus", "");');

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function MessageSink($timestamp, $senderID, $message, $data)
    {
        parent::MessageSink($timestamp, $senderID, $message, $data);

        if ($message == IPS_KERNELMESSAGE && $data[0] == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    private function CheckModuleConfiguration()
    {
        $r = [];

        $host = $this->ReadPropertyString('host');
        if ($host == '') {
            $this->SendDebug(__FUNCTION__, '"host" is needed', 0);
            $r[] = $this->Translate('Hostname must be specified');
        }

        $device_type = $this->ReadPropertyInteger('device_type');
        if ($device_type == self::$DEVICE_TYPE_NONE) {
            $this->SendDebug(__FUNCTION__, '"device_type" must be set', 0);
            $r[] = $this->Translate('Device type must be specified');
        }

        return $r;
    }

    private function CheckModuleUpdate(array $oldInfo, array $newInfo)
    {
        $r = [];

        return $r;
    }

    private function CompleteModuleUpdate(array $oldInfo, array $newInfo)
    {
        return '';
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainTimer('UpdateStatus', 0);
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainTimer('UpdateStatus', 0);
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainTimer('UpdateStatus', 0);
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $vpos = 1;

        $u = $this->Use4Ident('VLV');
        $this->MaintainVariable('ValveState', $this->Translate('Shut-off valve'), VARIABLETYPE_INTEGER, 'SyrConnect.ValveState', $vpos++, $u);

        $u = $this->Use4Ident('AB');
        $e = $this->Enable4Ident('AB');
        $this->MaintainVariable('ValveAction', $this->Translate('Shut-off valve action'), VARIABLETYPE_BOOLEAN, 'SyrConnect.ValveAction', $vpos++, $u);
        if ($u) {
            $this->MaintainAction('ValveAction', $e);
        }

        $u = $this->Use4Ident('PRF');
        $e = $this->Enable4Ident('PRF');
        $this->MaintainVariable('CurrentProfile', $this->Translate('Current profile'), VARIABLETYPE_INTEGER, 'SyrConnect.CurrentProfile', $vpos++, $u);
        if ($u) {
            $this->MaintainAction('CurrentProfile', $e);
        }

        $u = $this->Use4Ident('DSV');
        $this->MaintainVariable('MicroleakageTestState', $this->Translate('Micro leakage test'), VARIABLETYPE_INTEGER, 'SyrConnect.MicroleakageTestState', $vpos++, $u);

        $u = $this->Use4Ident('AVO');
        $this->MaintainVariable('CurrentWithdrawal', $this->Translate('Current withdrawal'), VARIABLETYPE_FLOAT, 'SyrConnect.Volume', $vpos++, $u);

        $u = $this->Use4Ident('BAR');
        $this->MaintainVariable('InputPressure', $this->Translate('Input pressure'), VARIABLETYPE_FLOAT, 'SyrConnect.Pressure', $vpos++, $u);

        $u = $this->Use4Ident('BUZ');
        $e = $this->Enable4Ident('BUZ');
        $this->MaintainVariable('Buzzer', $this->Translate('Audible alarm'), VARIABLETYPE_BOOLEAN, 'SyrConnect.Buzzer', $vpos++, $u);
        if ($u) {
            $this->MaintainAction('Buzzer', $e);
        }

        $u = $this->Use4Ident('BAT');
        $this->MaintainVariable('BatteryVoltage', $this->Translate('Battery voltage'), VARIABLETYPE_FLOAT, 'SyrConnect.Voltage', $vpos++, $u);

        $u = $this->Use4Ident('CEL');
        $this->MaintainVariable('Temperature', $this->Translate('Temperature'), VARIABLETYPE_FLOAT, 'SyrConnect.Temperature', $vpos++, $u);

        $u = $this->Use4Ident('CND');
        $this->MaintainVariable('Conductivity', $this->Translate('Conductivity'), VARIABLETYPE_INTEGER, 'SyrConnect.Conductivity', $vpos++, $u);

        $u = $this->Use4Ident('FLO');
        $this->MaintainVariable('CurrentFlow', $this->Translate('Current flow'), VARIABLETYPE_INTEGER, 'SyrConnect.Flow', $vpos++, $u);

        $u = $this->Use4Ident('LTV');
        $this->MaintainVariable('LastWithdrawal', $this->Translate('Last withdrawal'), VARIABLETYPE_FLOAT, 'SyrConnect.Volume', $vpos++, $u);

        $u = $this->Use4Ident('VOL');
        $this->MaintainVariable('CumulativeWithdrawal', $this->Translate('Cumulative withdrawal'), VARIABLETYPE_FLOAT, 'SyrConnect.Volume', $vpos++, $u);

        $u = $this->Use4Ident('ALA');
        $this->MaintainVariable('CurrentAlarm', $this->Translate('Current alarm'), VARIABLETYPE_INTEGER, 'SyrConnect.Alarm', $vpos++, $u);

        $u = $this->Use4Ident('WRN');
        $this->MaintainVariable('CurrentWarning', $this->Translate('Current warning'), VARIABLETYPE_INTEGER, 'SyrConnect.Warning', $vpos++, $u);

        $u = $this->Use4Ident('NOT');
        $this->MaintainVariable('CurrentNotification', $this->Translate('Current notification'), VARIABLETYPE_INTEGER, 'SyrConnect.Notification', $vpos++, $u);

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->MaintainTimer('UpdateStatus', 0);
            $this->MaintainStatus(IS_INACTIVE);
            return;
        }

        $this->MaintainStatus(IS_ACTIVE);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Syr Connect');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance',
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'host',
                    'caption' => 'Host'
                ],
                [
                    'type'     => 'Select',
                    'name'     => 'device_type',
                    'caption'  => 'Device type',
                    'options'  => $this->DeviceTypeAsOptions(),
                ],
            ],
            'caption' => 'Basic configuration',
        ];

        $formElements[] = [
            'type'    => 'NumberSpinner',
            'name'    => 'update_interval',
            'suffix'  => 'Seconds',
            'minimum' => 0,
            'caption' => 'Update interval',
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Test access',
            'onClick' => 'IPS_RequestAction($id, "TestAccess", "");',
        ];

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Update status',
            'onClick' => 'IPS_RequestAction($id, "UpdateStatus", "");',
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Expert area',
            'expanded'  => false,
            'items'     => [
                $this->GetInstallVarProfilesFormItem(),
            ],
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Test area',
            'expanded'  => false,
            'items'     => [
                [
                    'type'    => 'TestCenter',
                ],
            ]
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    private function SetUpdateInterval(int $sec = null)
    {
        if ($sec == '') {
            $sec = $this->ReadPropertyInteger('update_interval');
        }
        $this->MaintainTimer('UpdateStatus', $sec * 1000);
    }

    private function TestAccess()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            $msg = $this->GetStatusText() . PHP_EOL;
            $this->PopupMessage($msg);
            return;
        }

        $device_type = $this->ReadPropertyInteger('device_type');
        $msg = $this->DeviceType2String($device_type);

        if ($device_type != self::$DEVICE_TYPE_NONE) {
            $msg = $this->Translate('Model') . ': ' . $msg . PHP_EOL;

            $msg .= PHP_EOL;

            $val = $this->RetrieveData('VER');
            $msg .= $this->Translate('Firmware') . ': ' . $val . PHP_EOL;

            $val = $this->RetrieveData('SRN');
            $msg .= $this->Translate('Serial number') . ': ' . $val . PHP_EOL;

            $msg .= PHP_EOL;

            $msg .= $this->Translate('WLAN') . PHP_EOL;
            $val = $this->RetrieveData('WFC');
            if ($val !== false) {
                $msg .= ' - ' . $this->Translate('SSID') . ': ' . $val . PHP_EOL;

                $val = (int) $this->RetrieveData('WFS');
                $msg .= ' - ' . $this->Translate('Status') . ': ' . $this->WlanState2String($val) . PHP_EOL;

                if ($val == self::$WLAN_STATE_CONNECTED) {
                    $val = (int) $this->RetrieveData('WFR');
                    $msg .= ' - ' . $this->Translate('Signal strengh') . ': ' . $val . '%' . PHP_EOL;

                    $val = $this->RetrieveData('WGW');
                    $msg .= ' - ' . $this->Translate('IP') . ': ' . $val . PHP_EOL;
                }

                $val = $this->RetrieveData('MAC1');
                $msg .= ' - ' . $this->Translate('MAC') . ': ' . $val . PHP_EOL;
            } else {
                $msg .= ' - ' . $this->Translate('not configured') . PHP_EOL;
            }

            $msg .= PHP_EOL;

            $msg .= $this->Translate('LAN') . PHP_EOL;

            $val = $this->RetrieveData('EIP');
            $msg .= ' - ' . $this->Translate('IP') . ': ' . $val . PHP_EOL;
            $val = $this->RetrieveData('MAC2');
            $msg .= ' - ' . $this->Translate('MAC') . ': ' . $val . PHP_EOL;
        }

        $this->PopupMessage($msg);
    }

    private function UpdateStatus()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $update_interval = '';

        if ($this->Use4Ident('VLV')) {
            $val = (int) $this->RetrieveData('VLV');
            $this->SendDebug(__FUNCTION__, '... ValveState (VLV)=' . $val, 0);
            $this->SetValue('ValveState', $val);

            if (in_array($val, [self::$VALVE_STATE_CLOSING, self::$VALVE_STATE_OPENING])) {
                $update_interval = 5;
            }
        }

        if ($this->Use4Ident('AB')) {
            $val = (bool) $this->RetrieveData('AB');
            $b = $val == false;
            $this->SendDebug(__FUNCTION__, '... ValveAction (AB)=' . $this->bool2str($b) . ' (' . $this->bool2str($val) . ')', 0);
            $this->SetValue('ValveAction', $b);
        }

        if ($this->Use4Ident('PRF')) {
            $val = (int) $this->RetrieveData('PRF');
            $this->SendDebug(__FUNCTION__, '... CurrentProfile (PRF)=' . $val, 0);
            $this->SetValue('CurrentProfile', $val);
        }

        if ($this->Use4Ident('DSV')) {
            $val = (int) $this->RetrieveData('DSV');
            $this->SendDebug(__FUNCTION__, '... MicroleakageTestState (DSV)=' . $val, 0);
            $this->SetValue('MicroleakageTestState', $val);
        }

        if ($this->Use4Ident('AVO')) {
            $val = (int) $this->RetrieveData('AVO');
            $f = round($val / 1000, 2);
            $this->SendDebug(__FUNCTION__, '... CurrentWithdrawal (AVO)=' . $f . ' (' . $val . ')', 0);
            $this->SetValue('CurrentWithdrawal', $f);
        }

        if ($this->Use4Ident('BAR')) {
            $val = (int) $this->RetrieveData('BAR');
            $f = round($val / 1000, 1);
            $this->SendDebug(__FUNCTION__, '... InputPressure (BAR)=' . $f . ' (' . $val . ')', 0);
            $this->SetValue('InputPressure', $f);
        }

        if ($this->Use4Ident('BUZ')) {
            $val = (bool) $this->RetrieveData('BUZ');
            $this->SendDebug(__FUNCTION__, '... Buzzer (BUZ)=' . $this->bool2str($val), 0);
            $this->SetValue('Buzzer', $val);
        }

        if ($this->Use4Ident('BAT')) {
            $val = (int) $this->RetrieveData('BAT');
            $f = round($val / 100, 2);
            $this->SendDebug(__FUNCTION__, '... BatteryVoltage (BAT)=' . $f . ' (' . $val . ')', 0);
            $this->SetValue('BatteryVoltage', $f);
        }

        if ($this->Use4Ident('CEL')) {
            $val = (int) $this->RetrieveData('CEL');
            $f = round($val / 10, 1);
            $this->SendDebug(__FUNCTION__, '... Temperature (CEL)=' . $f . ' (' . $val . ')', 0);
            $this->SetValue('Temperature', $f);
        }

        if ($this->Use4Ident('CND')) {
            $val = (int) $this->RetrieveData('CND');
            $this->SendDebug(__FUNCTION__, '... Conductivity (CND)=' . $val, 0);
            $this->SetValue('Conductivity', $val);
        }

        if ($this->Use4Ident('FLO')) {
            $val = (int) $this->RetrieveData('FLO');
            $this->SendDebug(__FUNCTION__, '... CurrentFlow (FLO)=' . $val, 0);
            $this->SetValue('CurrentFlow', $val);
        }

        if ($this->Use4Ident('LTV')) {
            $val = (float) $this->RetrieveData('LTV');
            $this->SendDebug(__FUNCTION__, '... LastWithdrawal (LTV)=' . $val, 0);
            $this->SetValue('LastWithdrawal', $val);
        }

        if ($this->Use4Ident('VOL')) {
            $val = (float) $this->RetrieveData('VOL');
            $this->SendDebug(__FUNCTION__, '... CumulativeWithdrawal (VOL)=' . $val, 0);
            $this->SetValue('CumulativeWithdrawal', $val);
        }

        if ($this->Use4Ident('ALA')) {
            $val = $this->RetrieveData('ALA');
            $i = hexdec('0x' . $val);
            $this->SendDebug(__FUNCTION__, '... CurrentAlarm (ALA)=' . $i . ' (' . $val . ')', 0);
            $this->SetValue('CurrentAlarm', $i);
        }

        if ($this->Use4Ident('WRN')) {
            $val = $this->RetrieveData('WRN');
            $i = hexdec('0x' . $val);
            $this->SendDebug(__FUNCTION__, '... CurrentWarning (WRN)=' . $i . ' (' . $val . ')', 0);
            $this->SetValue('CurrentWarning', $i);
        }

        if ($this->Use4Ident('NOT')) {
            $val = $this->RetrieveData('NOT');
            $i = hexdec('0x' . $val);
            $this->SendDebug(__FUNCTION__, '... CurrentNotification (NOT)=' . $i . ' (' . $val . ')', 0);
            $this->SetValue('CurrentNotification', $i);
        }

        if ($update_interval > 0) {
            $this->SetUpdateInterval($update_interval);
        } else {
            $this->SetUpdateInterval();
        }
    }

    private function LocalRequestAction($ident, $value)
    {
        $device_type = $this->ReadPropertyInteger('device_type');

        $r = true;
        switch ($ident) {
            case 'TestAccess':
                $this->TestAccess();
                break;
            case 'UpdateStatus':
                $this->UpdateStatus();
                break;
            default:
                $r = false;
                break;
        }
        return $r;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->LocalRequestAction($ident, $value)) {
            return;
        }
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }

        if ($this->GetStatus() == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $this->SendDebug(__FUNCTION__, 'ident=' . $ident . ', value=' . $value, 0);

        $r = false;
        switch ($ident) {
            case 'ValveAction': // Absperrung öffnen/schließen
                if ($this->Enable4Ident('AB')) {
                    $r = $this->SwitchValve((bool) $value);
                    $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                }
                break;
            case 'CurrentProfile': // Profil setzen
                if ($this->Enable4Ident('PRF')) {
                    $r = $this->SetCurrentProfile((int) $value);
                    $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                    if ($r) {
                        $this->SetUpdateInterval(1);
                    }
                }
                break;
            case 'Buzzer': // Absperrung öffnen/schließen
                if ($this->Enable4Ident('BUZ')) {
                    $r = $this->SwitchBuzzer((bool) $value);
                    $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                    if ($r) {
                        $this->SetUpdateInterval(1);
                    }
                }
                break;
            case 'CurrentAlarm': // aktuellen Alarm quittieren
                if ($this->Enable4Ident('ALA')) {
                    $r = $this->ClearCurrentAlarm();
                    $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                    if ($r) {
                        $this->SetUpdateInterval(1);
                    }
                }
                break;
            case 'CurrentWarning': // aktuellen Warnung quittieren
                if ($this->Enable4Ident('WRN')) {
                    $r = $this->ClearCurrentWarning();
                    $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                    if ($r) {
                        $this->SetUpdateInterval(1);
                    }
                }
                break;
            case 'CurrentNotification': // aktuelle Nachricht quittieren
                if ($this->Enable4Ident('NOT')) {
                    $r = $this->ClearCurrentNotіfication();
                    $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                    if ($r) {
                        $this->SetUpdateInterval(1);
                    }
                }
                break;
                /*
                case 'RMO':		// Regenerationsmodus	1-4 ( 1 Standard, 2 ECO, 3 Power, 4 Automatik)
                case 'RPD':		// Regenerationsintervall	1-3	Tag
                case 'RTM':		// Regerationsuhrzeit	0:00-23:59
                case 'WFC':		// WLAN SSID	true
                case 'WFD':		// WLAN SSID und Key Löschen
                case 'WFK':		// WLAN Key
                 */
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
        if ($r) {
            $this->SetValue($ident, $value);
        }
    }

    public function SwitchValve(bool $val)
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return false;
        }

        if ($this->Enable4Ident('AB') == false) {
            return false;
        }

        $r = $this->TransmitData('AB', $this->bool2str($val));
        return $r;
    }

    public function SetCurrentProfile(int $val)
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return false;
        }

        if ($this->Enable4Ident('PRF') == false) {
            return false;
        }

        $r = $this->TransmitData('PRF', $val);
        return $r;
    }

    public function SwitchBuzzer(bool $val)
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return false;
        }

        if ($this->Enable4Ident('BUZ') == false) {
            return false;
        }

        $r = $this->TransmitData('BUZ', $this->bool2str($val));
        return $r;
    }

    public function ClearCurrentAlarm()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return false;
        }

        if ($this->Enable4Ident('ALM') == false) {
            return false;
        }

        $r = $this->TransmitData('ALM', 255);
        return $r;
    }

    public function ClearCurrentWarning()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return false;
        }

        if ($this->Enable4Ident('WRN') == false) {
            return false;
        }

        $r = $this->TransmitData('WRN', 255);
        return $r;
    }

    public function ClearCurrentNotification()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return false;
        }

        if ($this->Enable4Ident('NOT') == false) {
            return false;
        }

        $r = $this->TransmitData('NOT', 255);
        return $r;
    }

    /*
        case 'RMO':		// Regenerationsmodus	1-4 ( 1 Standard, 2 ECO, 3 Power, 4 Automatik)
        case 'RPD':		// Regenerationsintervall	1-3	Tag
        case 'RTM':		// Regerationsuhrzeit	0:00-23:59
        case 'WFC':		// WLAN SSID	true
        case 'WFD':		// WLAN SSID und Key Löschen
        case 'WFK':		// WLAN Key
     */

    private function RetrieveData($func)
    {
        $host = $this->ReadPropertyString('host');
        $port = $this->ReadPropertyInteger('port');
        $device_type = $this->ReadPropertyInteger('device_type');

        switch ($device_type) {
            case self::$DEVICE_TYPE_TRIODFR_LS:
            case self::$DEVICE_TYPE_SAFETECH_PLUS:
                $device_key = 'trio';
                break;
            case self::$DEVICE_TYPE_NEOSOFT_2500:
            case self::$DEVICE_TYPE_NEOSOFT_5000:
                $device_key = 'neosoft';
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'unknown device_type=' . $device_type . ' => skip', 0);
                return false;
        }

        $url = 'http://' . $host . ':' . $port . '/' . $device_key . '/get/' . strtolower($func);
        $body = $this->do_HttpRequest($url);
        if ($body == false) {
            return false;
        }

        $jbody = @json_decode($body, true);
        if ($jbody == false) {
            $this->SendDebug(__FUNCTION__, 'json_last_error_msg=' . json_last_error_msg() . ', jbody=' . print_r($jbody, true), 0);
            return false;
        }

        $key = 'get' . $func;
        if (isset($jbody[$key]) == false) {
            $this->SendDebug(__FUNCTION__, 'missing value, jbody=' . print_r($jbody, true), 0);
            return false;
        }

        $ret = $jbody[$key];

        $this->SendDebug(__FUNCTION__, 'func=' . $func . ', value=' . $ret, 0);
        return $ret;
    }

    private function TransmitData($func, $val)
    {
        $host = $this->ReadPropertyString('host');
        $port = $this->ReadPropertyInteger('port');
        $device_type = $this->ReadPropertyInteger('device_type');

        switch ($device_type) {
            case self::$DEVICE_TYPE_TRIODFR_LS:
            case self::$DEVICE_TYPE_SAFETECH_PLUS:
                $device_key = 'trio';
                break;
            case self::$DEVICE_TYPE_NEOSOFT_2500:
            case self::$DEVICE_TYPE_NEOSOFT_5000:
                $device_key = 'neosoft';
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'unknown device_type=' . $device_type . ' => skip', 0);
                return false;
        }

        $url = 'http://' . $host . ':' . $port . '/' . $device_key . '/set/' . strtolower($func) . '/' . $val;
        $body = $this->do_HttpRequest($url);
        if ($body == false) {
            return false;
        }

        $jbody = @json_decode($body, true);
        if ($jbody == false) {
            $this->SendDebug(__FUNCTION__, 'json_last_error_msg=' . json_last_error_msg() . ', jbody=' . print_r($jbody, true), 0);
            return false;
        }

        $key = 'set' . $func . $val;
        if (isset($jbody[$key]) == false) {
            $this->SendDebug(__FUNCTION__, 'missing value, jbody=' . print_r($jbody, true), 0);
            return false;
        }

        $ret = $jbody[$key];

        $this->SendDebug(__FUNCTION__, 'func=' . $func . ', val=' . $val . ' => ' . $ret, 0);

        return $ret == 'OK';
    }

    private function do_HttpRequest($url)
    {
        $this->SendDebug(__FUNCTION__, 'http-get: url=' . $url, 0);
        $time_start = microtime(true);

        $time_start = microtime(true);

        $curl_opts = [
            CURLOPT_URL            => $url,
            CURLOPT_HEADER         => true,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => true,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $curl_opts);
        $response = curl_exec($ch);
        $cerrno = curl_errno($ch);
        $cerror = $cerrno ? curl_error($ch) : '';
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_info = curl_getinfo($ch);
        curl_close($ch);

        $duration = round(microtime(true) - $time_start, 2);
        $this->SendDebug(__FUNCTION__, ' => errno=' . $cerrno . ', httpcode=' . $httpcode . ', duration=' . $duration . 's', 0);
        $this->SendDebug(__FUNCTION__, ' => response=' . $response, 0);

        $statuscode = 0;
        $err = '';
        $statuscode = 0;
        $err = '';

        if ($cerrno) {
            $statuscode = self::$IS_SERVERERROR;
            $err = 'got curl-errno ' . $cerrno . ' (' . $cerror . ')';
        } else {
            $header_size = $curl_info['header_size'];
            $head = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            // $this->SendDebug(__FUNCTION__, ' => head=' . $head, 0);
            // $this->SendDebug(__FUNCTION__, ' => body=' . $body, 0);

            if ($httpcode != 200) {
                if ($httpcode >= 500 && $httpcode <= 599) {
                    $statuscode = self::$IS_SERVERERROR;
                    $err = 'got http-code ' . $httpcode . ' (server error)';
                } else {
                    $statuscode = self::$IS_HTTPERROR;
                    $err = 'got http-code ' . $httpcode;
                }
            }
        }

        if ($statuscode) {
            $this->SendDebug(__FUNCTION__, '    statuscode=' . $statuscode . ', err=' . $err, 0);
            $this->MaintainStatus($statuscode);
            return false;
        }

        $this->MaintainStatus(IS_ACTIVE);
        return $body;
    }

    private function build_url($url, $params)
    {
        $n = 0;
        if (is_array($params)) {
            foreach ($params as $param => $value) {
                $url .= ($n++ ? '&' : '?') . $param . '=' . rawurlencode(strval($value));
            }
        }
        return $url;
    }

    private function build_header($headerfields)
    {
        $header = [];
        foreach ($headerfields as $key => $value) {
            $header[] = $key . ': ' . $value;
        }
        return $header;
    }

    private function Use4Ident($func)
    {
        $device_type = $this->ReadPropertyInteger('device_type');

        $r = false;

        if ($device_type == self::$DEVICE_TYPE_TRIODFR_LS) {
            switch ($func) {
                case 'AB':		// Absperrung öffnen/schließen	true/false
                case 'VLV':		// Status der Absperrung
                case 'PRF':		// Aktuell ausgewähltes Profil
                case 'DSV':		// Status der Mikroleckage
                case 'AVO':		// Volumen aktuelle Entnahme in ml
                case 'BAR':		// Eingangsdruck in mbar	0-16000
                case 'BUZ':		// Buzzer On/Off bei Alarm	true/false
                case 'BAT':		// Batteriespannung in 1/100 V	0-1000
                case 'BAR':		// Eingangsdruck in mbar	0-16000
                case 'CEL':		// Temperatur in °C	0-1000
                case 'CND':		// Leitwert in µS/cm	0-5000
                case 'FLO':		// Aktueller Durchfluss in l/h	0-5000
                case 'LTV':		// Letztes gezapftes Volumen in Litern
                case 'SRN':		// Seriennummer des Gerätes
                case 'VER':		// Firmware Version des Gerätes
                case 'VOL':		// Kumulatives Volumen in Litern
                case 'NPS':		// Keine Turbinenimpulse seit.. in s
                    $r = true;
                    break;
                default:
                    break;
            }
        }
        if ($device_type == self::$DEVICE_TYPE_SAFETECH_PLUS) {
            switch ($func) {
                case 'AB':		// Absperrung öffnen/schließen	true/false
                case 'VLV':		// Status der Absperrung
                case 'PRF':		// Aktuell ausgewähltes Profil
                case 'DSV':		// Status der Mikroleckage
                case 'AVO':		// Volumen aktuelle Entnahme in ml
                case 'BAR':		// Eingangsdruck in mbar	0-16000
                case 'BUZ':		// Buzzer On/Off bei Alarm	true/false
                case 'BAT':		// Batteriespannung in 1/100 V	0-1000
                case 'BAR':		// Eingangsdruck in mbar	0-16000
                case 'CEL':		// Temperatur in °C	0-1000
                case 'CND':		// Leitwert in µS/cm	0-5000
                case 'FLO':		// Aktueller Durchfluss in l/h	0-5000
                case 'LTV':		// Letztes gezapftes Volumen in Litern
                case 'NPS':		// Keine Turbinenimpulse seit.. in s
                case 'SRN':		// Seriennummer des Gerätes
                case 'VER':		// Firmware Version des Gerätes
                case 'VOL':		// Kumulatives Volumen in Litern
                    $r = true;
                    break;
                default:
                    break;
            }
        }

        if ($device_type == self::$DEVICE_TYPE_NEOSOFT_2500) {
            switch ($func) {
                case 'AVO':		// Volumen aktuelle Entnahme in ml
                case 'BUZ':		// Buzzer On/Off bei Alarm	true/false
                case 'FLO':		// Aktueller Durchfluss in l/h	0-5000
                case 'LTV':		// Letztes gezapftes Volumen in Litern
                case 'SRN':		// Seriennummer des Gerätes
                case 'VER':		// Firmware Version des Gerätes
                case 'VOL':		// Kumulatives Volumen in Litern
                case 'VPS1':	// Keine Turbinenimpulse Steuerkopf 1 seit.. in s
                case 'IWH':		// Eingangshärte	1-85	°dH
                case 'OWH':		// Ausgangshärte	1-85	°dH
                case 'SS1':		// Salzvorrat	0-40	Wochen
                case 'SV1':		// Salzmenge	0-40	Kilogramm
                case 'RE1':		// Reserve Kapazität Flasche 1	0-9999	Liter
                case 'RE2':		// Reserve Kapazität Flasche 2	0-9999	Liter
                case 'RG1':		// Regeneration Status 0-2 (0 - keine Regeneration, 1 - Flasche 1 regeneriert, 2 - Flasche 2 regeneriert)
                case 'RTI':		// Zeit bis Regeneration zu Ende	0-5940	Sekunden
                case 'SRH':		// ng	Nächste Halbjährliche Wartung		dd.mm.yyyy
                case 'SRV':		// ng	Nächste Jährliche Wartung		dd.mm.yyyy
                case 'RMO':		// Regenerationsmodus	1-4 ( 1 Standard, 2 ECO, 3 Power, 4 Automatik)
                case 'RPD':		// Regenerationsintervall	1-3	Tag
                case 'RTM':		// Regerationsuhrzeit	0:00-23:59
                    $r = true;
                    break;
                default:
                    break;
            }
        }

        if ($device_type == self::$DEVICE_TYPE_NEOSOFT_5000) {
            switch ($func) {
                case 'AVO':		// Volumen aktuelle Entnahme in ml
                case 'BUZ':		// Buzzer On/Off bei Alarm	true/false
                case 'FLO':		// Aktueller Durchfluss in l/h	0-5000
                case 'LTV':		// Letztes gezapftes Volumen in Litern
                case 'SRN':		// Seriennummer des Gerätes
                case 'VER':		// Firmware Version des Gerätes
                case 'VOL':		// Kumulatives Volumen in Litern
                case 'VPS1':	// Keine Turbinenimpulse Steuerkopf 1 seit.. in s
                case 'VPS2':	// Keine Turbinenimpulse Steuerkopf 2 seit.. in s
                case 'IWH':		// Eingangshärte	1-85	°dH
                case 'OWH':		// Ausgangshärte	1-85	°dH
                case 'SS1':		// Salzvorrat	0-40	Wochen
                case 'SV1':		// Salzmenge	0-40	Kilogramm
                case 'RE1':		// Reserve Kapazität Flasche 1	0-9999	Liter
                case 'RE2':		// Reserve Kapazität Flasche 2	0-9999	Liter
                case 'RG1':		// Regeneration Status 0-2 (0 - keine Regeneration, 1 - Flasche 1 regeneriert, 2 - Flasche 2 regeneriert)
                case 'RTI':		// Zeit bis Regeneration zu Ende	0-5940	Sekunden
                case 'SRH':		// ng	Nächste Halbjährliche Wartung		dd.mm.yyyy
                case 'SRV':		// ng	Nächste Jährliche Wartung		dd.mm.yyyy
                case 'RMO':		// Regenerationsmodus	1-4 ( 1 Standard, 2 ECO, 3 Power, 4 Automatik)
                case 'RPD':		// Regenerationsintervall	1-3	Tag
                case 'RTM':		// Regerationsuhrzeit	0:00-23:59
                    $r = true;
                    break;
                default:
                    break;
            }
        }

        switch ($func) {
            case 'WFC':		// WLAN SSID	true
            case 'WFD':		// WLAN SSID und Key Löschen
            case 'WFK':		// WLAN Key
            case 'WFL':		// Stellt eine Lister der Verfügbaren Netzwerke zur Verfügung
            case 'WFR':		// WLAN Signalstärke in %	1-100
            case 'WFS':		// WLAN Verbindungsstatus	0-2
            case 'WGW':		// WLAN IP
            case 'WIP':		// WLAN Gateway
            case 'EGW':		// Ethernet IP
            case 'EIP':		// Ethernet Gateway
            case 'MAC1':	// MAC-Adresse WLAN Schnittstelle
            case 'MAC2':	// MAC-Adresse LAN Schnittstelle
            case 'ALA':		// Abrufen und Quittierung des aktuellen Alarms
            case 'ALM':		// Abrufen der letzten 8 Alarme
            case 'WRN':		// Abrufen und Quittierung der aktuellen Warnung
            case 'ALW':		// Abrufen der letzten 8 Warnungen
            case 'NOT':		// Abrufen und Quittieren der aktuellen Benachrichtigung
            case 'ALN':		// Abrufen der letzten 8 Benachrichtigungen
                $r = true;
                break;
            default:
                break;
        }

        // $this->SendDebug(__FUNCTION__, 'func=' . $func . ' => ' . $this->bool2str($r), 0);
        return $r;
    }

    private function Enable4Ident($func)
    {
        $device_type = $this->ReadPropertyInteger('device_type');

        $r = false;

        if ($this->Use4Ident($func) == false) {
            return $r;
        }

        if ($device_type == self::$DEVICE_TYPE_TRIODFR_LS) {
            switch ($func) {
                case 'AB':		// Absperrung öffnen/schließen
                case 'PRF':		// Aktuell ausgewähltes Profil
                    $r = true;
                    break;
                default:
                    break;
            }
        }

        if ($device_type == self::$DEVICE_TYPE_SAFETECH_PLUS) {
            switch ($func) {
                case 'AB':		// Absperrung öffnen/schließen
                case 'PRF':		// Profil setzen
                    $r = true;
                    break;
                default:
                    break;
            }
        }

        if ($device_type == self::$DEVICE_TYPE_NEOSOFT_2500) {
            switch ($func) {
                case 'RMO':		// Regenerationsmodus	1-4 ( 1 Standard, 2 ECO, 3 Power, 4 Automatik)
                case 'RPD':		// Regenerationsintervall	1-3	Tag
                case 'RTM':		// Regerationsuhrzeit	0:00-23:59
                    $r = true;
                    break;
                default:
                    break;
            }
        }

        if ($device_type == self::$DEVICE_TYPE_NEOSOFT_5000) {
            switch ($func) {
                case 'RMO':		// Regenerationsmodus	1-4 ( 1 Standard, 2 ECO, 3 Power, 4 Automatik)
                    $r = true;
                    break;
                default:
                    break;
            }
        }

        switch ($func) {
            case 'BUZ':		// Buzzer On/Off bei Alarm	true/false
            case 'WFC':		// WLAN SSID	true
            case 'WFD':		// WLAN SSID und Key Löschen
            case 'WFK':		// WLAN Key
            case 'ALA':		// Quittieren des anliegenden Alarms	255
            case 'WRN':		// Quittieren der aktuellen Warnung	255
            case 'NOT':		// Quittieren der anliegenden Benachrichtigung	255
                $r = true;
                break;
            default:
                break;
        }

        // $this->SendDebug(__FUNCTION__, 'func=' . $func . ' => ' . $this->bool2str($r), 0);
        return $r;
    }
}
