<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class SyrConnect extends IPSModule
{
    use SyrConnect\StubsCommonLib;
    use SyrConnectLocalLib;

    public static $SYRCONNECT_TYPE_NONE = 0;
    public static $SYRCONNECT_TYPE_TRIODFR_LS = 1;
    public static $SYRCONNECT_TYPE_SAFETECH_PLUS = 2;
    public static $SYRCONNECT_TYPE_NEOSOFT_2500 = 3;
    public static $SYRCONNECT_TYPE_NEOSOFT_5000 = 4;

    public static $SYRCONNECT_WLANSTATUS_DISCONNECTED = 0;
    public static $SYRCONNECT_WLANSTATUS_CONNECTING = 1;
    public static $SYRCONNECT_WLANSTATUS_CONNECTED = 2;

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

        $this->RegisterPropertyInteger('device_type', self::$SYRCONNECT_TYPE_NONE);

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
        if ($device_type == self::$SYRCONNECT_TYPE_NONE) {
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
                    'options'  => [
                        [
                            'caption' => $this->Translate('none'),
                            'value'   => self::$SYRCONNECT_TYPE_NONE,
                        ],
                        [
                            'caption' => 'SyrTech+',
                            'value'   => self::$SYRCONNECT_TYPE_SAFETECH_PLUS,
                        ],
                    ],
                    // 'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateUseFields", "");',
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

        $msg = '';

        $device_type = $this->ReadPropertyInteger('device_type');
        switch ($device_type) {
            case self::$SYRCONNECT_TYPE_TRIODFR_LS:
                $msg = $this->Translate('Model') . ': TrioDFR LS' . PHP_EOL;
                break;
            case self::$SYRCONNECT_TYPE_SAFETECH_PLUS:
                $msg = $this->Translate('Model') . ': SafeTech +' . PHP_EOL;
                break;
            case self::$SYRCONNECT_TYPE_NEOSOFT_2500:
                $msg = $this->Translate('Model') . ': NeoSoft 2500' . PHP_EOL;
                break;
            case self::$SYRCONNECT_TYPE_NEOSOFT_5000:
                $msg = $this->Translate('Model') . ': NeoSoft 5000' . PHP_EOL;
                break;
            default:
                $msg = $this->Translate('Unknown model');
                $this->PopupMessage($msg);
                return;
        }

        $msg .= PHP_EOL;

        $val = $this->RetrieveData('VER');
        $msg .= $this->Translate('Firmware') . ': ' . $val . PHP_EOL;

        $val = $this->RetrieveData('SRN');
        $msg .= $this->Translate('Serial number') . ': ' . $val . PHP_EOL;

        $msg .= PHP_EOL;

        $msg .= $this->Translate('WLAN') . PHP_EOL;
        $val = $this->RetrieveData('WFC');
        if ($val !== false) {
            $msg .= ' - ' . $this->Translate('SID') . ': ' . $val . PHP_EOL;

            $val = (int) $this->RetrieveData('WFS');
            $msg .= ' - ' . $this->Translate('Status') . ': ' . $this->DecodeWFS($val) . PHP_EOL;

            if ($val == self::$SYRCONNECT_WLANSTATUS_CONNECTED) {
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

        $this->PopupMessage($msg);
    }

    private function DecodeWFS($val)
    {
        $val2txt = [
            self::$SYRCONNECT_WLANSTATUS_DISCONNECTED => 'nicht verbunden',
            self::$SYRCONNECT_WLANSTATUS_CONNECTING   => 'is being connected',
            self::$SYRCONNECT_WLANSTATUS_CONNECTED    => 'verbunden',
        ];

        if (isset($val2txt[$val])) {
            $txt = $this->Translate($val2txt[$val]);
        } else {
            $msg = 'unknown value "' . $val . '"';
            $this->SendDebug(__FUNCTION__, $msg, 0);
            $txt = $val;
        }
        return $txt;
    }

    private function UpdateStatus()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $this->SendDebug(__FUNCTION__, $this->PrintTimer('UpdateStatus'), 0);
    }

    private function LocalRequestAction($ident, $value)
    {
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
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
        if ($r) {
            $this->SetValue($ident, $value);
        }
    }

    private function RetrieveData($func)
    {
        $host = $this->ReadPropertyString('host');
        $port = $this->ReadPropertyInteger('port');
        $device_type = $this->ReadPropertyInteger('device_type');

        switch ($device_type) {
            case self::$SYRCONNECT_TYPE_TRIODFR_LS:
            case self::$SYRCONNECT_TYPE_SAFETECH_PLUS:
                $device_key = 'trio';
                break;
            case self::$SYRCONNECT_TYPE_NEOSOFT_2500:
            case self::$SYRCONNECT_TYPE_NEOSOFT_5000:
                $device_key = 'neosoft';
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'unknown device_type=' . $device_type . ' => skip', 0);
                return false;
        }

        $url = 'http://' . $host . ':' . $port . '/' . $device_key . '/get/' . strtolower($func);
        $body = $this->do_HttpRequest($url);

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
        switch ($func) {
            default:
                break;
        }

        $this->SendDebug(__FUNCTION__, 'value=' . $ret, 0);
        return $ret;
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
            $this->SendDebug(__FUNCTION__, ' => head=' . $head, 0);
            $this->SendDebug(__FUNCTION__, ' => body=' . $body, 0);

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
}
