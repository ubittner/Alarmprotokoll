<?php

/**
 * @project       Alarmprotokoll/Alarmprotokoll
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpRedundantMethodOverrideInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/AP_autoload.php';

class Alarmprotokoll extends IPSModule
{
    //Helper
    use AP_Archive;
    use AP_Config;
    use AP_Messages;
    use AP_Protocol;

    //Constants
    private const LIBRARY_GUID = '{60C35BE7-ED7C-AD82-EFCA-8B2AD23579F6}';
    private const MODULE_GUID = '{66BDB59B-E80F-E837-6640-005C32D5FC24}';
    private const MODULE_PREFIX = 'AP';
    private const ARCHIVE_MODULE_GUID = '{43192F0B-135B-4CE7-A0A7-1475603F3060}';
    private const SMTP_MODULE_GUID = '{375EAF21-35EF-4BC4-83B3-C780FD8BD88A}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Info
        $this->RegisterPropertyString('Note', '');

        //Archive
        $this->RegisterPropertyInteger('Archive', 0);
        $this->RegisterPropertyInteger('ArchiveRetentionTime', 90);

        //Monthly protocol
        $this->RegisterPropertyBoolean('UseMonthlyProtocol', true);
        $this->RegisterPropertyInteger('MonthlyProtocolDay', 1);
        $this->RegisterPropertyString('MonthlyProtocolTime', '{"hour":12,"minute":0,"second":0}');
        $this->RegisterPropertyString('TextFileTitle', 'Alarmanlage');
        $this->RegisterPropertyString('TextFileDescription', 'Protokoll (Standortbezeichnung)');
        $this->RegisterPropertyInteger('MonthlySMTP', 0);
        $this->RegisterPropertyString('MonthlyProtocolSubject', 'Protokoll Alarmanlage (Standortbezeichnung)');
        $this->RegisterPropertyString('MonthlyProtocolText', 'Das Protokoll finden Sie im Anhang dieser E-Mail.');
        $this->RegisterPropertyString('MonthlyRecipientList', '[]');

        //Visualisation
        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableAlarmMessages', true);
        $this->RegisterPropertyInteger('AlarmMessagesRetentionTime', 2);
        $this->RegisterPropertyBoolean('EnableStateMessages', true);
        $this->RegisterPropertyInteger('AmountStateMessages', 8);
        $this->RegisterPropertyBoolean('EnableEventMessages', true);
        $this->RegisterPropertyInteger('EventMessagesRetentionTime', 7);

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        //Alarm messages
        $id = @$this->GetIDForIdent('AlarmMessages');
        $this->RegisterVariableString('AlarmMessages', 'Alarmmeldung', '~TextBox', 20);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('AlarmMessages'), 'Warning');
        }

        //State messages
        $id = @$this->GetIDForIdent('StateMessages');
        $this->RegisterVariableString('StateMessages', 'Zustandsmeldungen', '~TextBox', 30);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('StateMessages'), 'Power');
        }

        //Event messages
        $id = @$this->GetIDForIdent('EventMessages');
        $this->RegisterVariableString('EventMessages', 'Ereignismeldungen', '~TextBox', 40);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('EventMessages'), 'Information');
        }

        //Message archive
        $id = @$this->GetIDForIdent('MessageArchive');
        $this->RegisterVariableString('MessageArchive', 'Archivdaten', '~TextBox', 50);
        if (!$id) {
            IPS_SetHidden($this->GetIDForIdent('MessageArchive'), true);
        }

        ########## Timers

        $this->RegisterTimer('CleanUpMessages', 0, self::MODULE_PREFIX . '_CleanUpMessages(' . $this->InstanceID . ');');
        $this->RegisterTimer('SendMonthlyProtocol', 0, self::MODULE_PREFIX . '_SendMonthlyProtocol(' . $this->InstanceID . ', true, 1);');
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all update messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
            }
        }

        //Register references
        $archiveID = $this->ReadPropertyInteger('Archive');
        if ($archiveID > 1 && @IPS_ObjectExists($archiveID)) {
            $this->RegisterReference($archiveID);
        }

        $smtpID = $this->ReadPropertyInteger('MonthlySMTP');
        if ($smtpID > 1 && @IPS_ObjectExists($smtpID)) {
            if ($this->ReadPropertyBoolean('UseMonthlyProtocol')) {
                $this->RegisterReference($smtpID);
            }
        }

        $this->RenameMessages();
        $this->SetArchiveLogging();

        //Timer
        $this->SetCleanUpMessagesTimer();
        $this->SetTimerInterval('SendMonthlyProtocol', $this->GetInterval('MonthlyProtocolTime'));

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('AlarmMessages'), !$this->ReadPropertyBoolean('EnableAlarmMessages'));
        IPS_SetHidden($this->GetIDForIdent('StateMessages'), !$this->ReadPropertyBoolean('EnableStateMessages'));
        IPS_SetHidden($this->GetIDForIdent('EventMessages'), !$this->ReadPropertyBoolean('EnableEventMessages'));

        //Generate text file
        $startTime = strtotime('first day of previous month midnight');
        $endTime = strtotime('first day of this month midnight') - 1;
        $this->GenerateTextFileData($startTime, $endTime);
    }

    public function Destroy()
    {
        //Delete text file
        $this->DeleteTextFile($this->InstanceID);

        //Never delete this line!
        parent::Destroy();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        if ($Message == IPS_KERNELSTARTED) {
            $this->KernelReady();
        }
    }

    public function CreateSMTPInstance(): void
    {
        $id = @IPS_CreateInstance(self::SMTP_MODULE_GUID);
        if (is_int($id)) {
            IPS_SetName($id, 'E-Mail, Send (SMTP)');
            $infoText = 'Instanz mit der ID ' . $id . ' wurde erfolgreich erstellt!';
        } else {
            $infoText = 'Instanz konnte nicht erstellt werden!';
        }
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
    }

    public function UIShowMessage(string $Message): void
    {
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $Message);
    }

    #################### Request Action

    public function RequestAction($Ident, $Value)
    {
        if ($Ident == 'Active') {
            $this->SetValue($Ident, $Value);
        }
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function CheckMaintenance(): bool
    {
        $result = false;
        if (!$this->GetValue('Active')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Instanz ist inaktiv!', 0);
            $result = true;
        }
        return $result;
    }

    private function GetInterval(string $TimerName): int
    {
        $timer = json_decode($this->ReadPropertyString($TimerName));
        $now = time();
        $hour = $timer->hour;
        $minute = $timer->minute;
        $second = $timer->second;
        $definedTime = $hour . ':' . $minute . ':' . $second;
        if (time() >= strtotime($definedTime)) {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
        } else {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
        }
        return ($timestamp - $now) * 1000;
    }
}