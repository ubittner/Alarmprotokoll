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
    private const MODULE_NAME = 'Alarmprotokoll';
    private const MODULE_PREFIX = 'AP';
    private const MODULE_VERSION = '7.0-2, 03.04.2023';
    private const ARCHIVE_MODULE_GUID = '{43192F0B-135B-4CE7-A0A7-1475603F3060}';
    private const SMTP_MODULE_GUID = '{375EAF21-35EF-4BC4-83B3-C780FD8BD88A}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Info
        $this->RegisterPropertyString('Note', '');

        //Designation
        $this->RegisterPropertyString('Designation', '');

        //Messages
        $this->RegisterPropertyInteger('AlarmMessagesRetentionTime', 2);
        $this->RegisterPropertyInteger('AmountStateMessages', 8);
        $this->RegisterPropertyInteger('EventMessagesRetentionTime', 7);

        //Archive
        $this->RegisterPropertyInteger('Archive', 0);
        $this->RegisterPropertyBoolean('UseArchiving', false);
        $this->RegisterPropertyInteger('ArchiveRetentionTime', 90);

        //Monthly protocol
        $this->RegisterPropertyBoolean('UseMonthlyProtocol', true);
        $this->RegisterPropertyInteger('MonthlyProtocolDay', 1);
        $this->RegisterPropertyString('MonthlyProtocolTime', '{"hour":12,"minute":0,"second":0}');
        $this->RegisterPropertyInteger('MonthlySMTP', 0);
        $this->RegisterPropertyString('MonthlyProtocolSubject', 'Monatsprotokoll');
        $this->RegisterPropertyString('MonthlyRecipientList', '[]');

        //Archive protocol
        $this->RegisterPropertyBoolean('UseArchiveProtocol', true);
        $this->RegisterPropertyInteger('ArchiveSMTP', 0);
        $this->RegisterPropertyString('ArchiveProtocolSubject', 'Archivprotokoll');
        $this->RegisterPropertyString('ArchiveRecipientList', '[]');

        //Visualisation
        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableAlarmMessages', true);
        $this->RegisterPropertyBoolean('EnableStateMessages', true);
        $this->RegisterPropertyBoolean('EnableEventMessages', true);

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
        $propertyNames = [
            ['name' => 'Archive', 'use' => 'UseArchiving'],
            ['name' => 'MonthlySMTP', 'use' => 'UseMonthlyProtocol'],
            ['name' => 'ArchiveSMTP', 'use' => 'UseArchiveProtocol']
        ];
        foreach ($propertyNames as $propertyName) {
            $id = $this->ReadPropertyInteger($propertyName['name']);
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                if ($this->ReadPropertyBoolean($propertyName['use'])) {
                    $this->RegisterReference($id);
                }
            }
        }

        $this->RenameMessages();
        $this->SetArchiveLogging($this->ReadPropertyBoolean('UseArchiving'));

        //Timer
        $this->SetCleanUpMessagesTimer();
        $this->SetTimerInterval('SendMonthlyProtocol', $this->GetInterval('MonthlyProtocolTime'));

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('AlarmMessages'), !$this->ReadPropertyBoolean('EnableAlarmMessages'));
        IPS_SetHidden($this->GetIDForIdent('StateMessages'), !$this->ReadPropertyBoolean('EnableStateMessages'));
        IPS_SetHidden($this->GetIDForIdent('EventMessages'), !$this->ReadPropertyBoolean('EnableEventMessages'));
    }

    public function Destroy()
    {
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
            echo 'Instanz mit der ID ' . $id . ' wurde erfolgreich erstellt!';
        } else {
            echo 'Instanz konnte nicht erstellt werden!';
        }
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