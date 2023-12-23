<?php

/**
 * @project       Alarmprotokoll/Alarmprotokoll/helper/
 * @file          AP_Messages.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait AP_Messages
{
    /**
     * Updates the message.
     *
     * @param string $Message
     * @param int $Type
     * 0 =  Event message
     * 1 =  State message
     * 2 =  Alarm message
     *
     * @return void
     * @throws Exception
     */
    public function UpdateMessages(string $Message, int $Type): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Nachricht: ' . $Message, 0);
        $this->SendDebug(__FUNCTION__, 'Typ: ' . $Type, 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        //Enter semaphore
        if (!$this->LockSemaphore()) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, Semaphore erreicht!', 0);
            $this->UnlockSemaphore();
            return;
        }
        //Write to archive variable first
        $archiveRetentionTime = $this->ReadPropertyInteger('ArchiveRetentionTime');
        if ($archiveRetentionTime > 0) {
            $this->SetValue('MessageArchive', $Message);
        }
        switch ($Type) {
            case 0: //Event
                $eventMessagesRetentionTime = $this->ReadPropertyInteger('EventMessagesRetentionTime');
                if ($eventMessagesRetentionTime > 0) {
                    $this->UpdateEventMessages($Message);
                }
                break;

            case 1: //State
                $amountStateMessages = $this->ReadPropertyInteger('AmountStateMessages');
                if ($amountStateMessages > 0) {
                    $this->UpdateEventMessages($Message);
                    $this->UpdateStateMessages($Message);
                }
                break;

            case 2: //Alarm
                $alarmMessagesRetentionTime = $this->ReadPropertyInteger('AlarmMessagesRetentionTime');
                if ($alarmMessagesRetentionTime > 0) {
                    $this->UpdateEventMessages($Message);
                    $this->UpdateAlarmMessages($Message);
                }
                break;

        }
        //Leave semaphore
        $this->UnlockSemaphore();
    }

    /**
     * Deletes all messages.
     *
     * @return void
     */
    public function DeleteAllMessages(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SetValue('AlarmMessages', 'Keine Alarmmeldungen vorhanden!');
        $this->SetValue('StateMessages', 'Keine Zustandsmeldungen vorhanden!');
        $this->SetValue('EventMessages', 'Keine Ereignismeldungen vorhanden!');
    }

    /**
     * Deletes the event messages.
     *
     * @return void
     */
    public function DeleteEventMessages(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SetValue('EventMessages', 'Keine Ereignismeldungen vorhanden!');
    }

    /**
     * Deletes the state messages.
     *
     * @return void
     */
    public function DeleteStateMessages(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SetValue('StateMessages', 'Keine Zustandsmeldungen vorhanden!');
    }

    /**
     * Deletes the alarm messages.
     *
     * @return void
     */
    public function DeleteAlarmMessages(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SetValue('AlarmMessages', 'Keine Alarmmeldungen vorhanden!');
    }

    /**
     * Cleans up the messages from archive.
     *
     * @return void
     * @throws Exception
     */
    public function CleanUpMessages(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        //Event messages
        if ($this->ReadPropertyInteger('EventMessagesRetentionTime') > 0) {
            $content = array_merge(array_filter(explode("\n", $this->GetValue('EventMessages'))));
            foreach ($content as $key => $message) {
                $year = (int) substr($message, 6, 4);
                $month = (int) substr($message, 3, 2);
                $day = (int) substr($message, 0, 2);
                $timestamp = mktime(0, 0, 0, $month, $day, $year);
                $dateNow = date('d.m.Y');
                $yearNow = (int) substr($dateNow, 6, 4);
                $monthNow = (int) substr($dateNow, 3, 2);
                $dayNow = (int) substr($dateNow, 0, 2);
                $timeNow = mktime(0, 0, 0, $monthNow, $dayNow, $yearNow);
                $difference = ($timestamp - $timeNow) / 86400;
                $days = abs($difference);
                if ($days >= $this->ReadPropertyInteger('EventMessagesRetentionTime')) {
                    unset($content[$key]);
                }
            }
            if (empty($content)) {
                $this->SetValue('EventMessages', 'Keine Ereignismeldungen vorhanden!');
            } else {
                $this->SetValue('EventMessages', implode("\n", $content));
            }
        }

        //Alarm messages
        if ($this->ReadPropertyInteger('AlarmMessagesRetentionTime') > 0) {
            $content = array_merge(array_filter(explode("\n", $this->GetValue('AlarmMessages'))));
            foreach ($content as $key => $message) {
                $year = (int) substr($message, 6, 4);
                $month = (int) substr($message, 3, 2);
                $day = (int) substr($message, 0, 2);
                $timestamp = mktime(0, 0, 0, $month, $day, $year);
                $dateNow = date('d.m.Y');
                $yearNow = (int) substr($dateNow, 6, 4);
                $monthNow = (int) substr($dateNow, 3, 2);
                $dayNow = (int) substr($dateNow, 0, 2);
                $timeNow = mktime(0, 0, 0, $monthNow, $dayNow, $yearNow);
                $difference = ($timestamp - $timeNow) / 86400;
                $days = abs($difference);
                if ($days >= $this->ReadPropertyInteger('AlarmMessagesRetentionTime')) {
                    unset($content[$key]);
                }
            }
            if (empty($content)) {
                $this->SetValue('AlarmMessages', 'Keine Alarmmeldungen vorhanden!');
            } else {
                $this->SetValue('AlarmMessages', implode("\n", $content));
            }
        }

        //Archive
        $retentionTime = $this->ReadPropertyInteger('ArchiveRetentionTime');
        if ($retentionTime > 0) {
            $archive = $this->ReadPropertyInteger('Archive');
            $instanceStatus = @IPS_GetInstance($this->InstanceID)['InstanceStatus'];
            if ($archive > 1 && @IPS_ObjectExists($archive) && $instanceStatus == 102) {
                //Set start time to 2000-01-01 12:00 am
                $startTime = 946684800;
                //Calculate end time
                $endTime = strtotime('-' . $retentionTime . ' days');
                @AC_DeleteVariableData($archive, $this->GetIDForIdent('MessageArchive'), $startTime, $endTime);
            }
        }
        $this->SetCleanUpMessagesTimer();
    }

    #################### Private

    /**
     * Renames the message designations.
     *
     * @return void
     * @throws Exception
     */
    private function RenameMessages(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        //Rename alarm messages
        $alarmMessagesRetentionTime = $this->ReadPropertyInteger('AlarmMessagesRetentionTime');
        switch ($alarmMessagesRetentionTime) {
            case 0:
                $name = 'Alarmmeldungen (deaktiviert)';
                $this->SetValue('AlarmMessages', '');
                break;

            case 1:
                $name = 'Alarmmeldungen (' . $alarmMessagesRetentionTime . ' Tag)';
                break;

            case $alarmMessagesRetentionTime > 1:
                $name = 'Alarmmeldungen (' . $alarmMessagesRetentionTime . ' Tage)';
                break;

            default:
                $name = 'Alarmmeldungen';
        }
        IPS_SetName($this->GetIDForIdent('AlarmMessages'), $name);

        //Rename state messages
        $amountStateMessages = $this->ReadPropertyInteger('AmountStateMessages');
        switch ($amountStateMessages) {
            case 0:
                $name = 'Zustandsmeldungen (deaktiviert)';
                $this->SetValue('StateMessages', '');
                break;

            case 1:
                $name = 'Zustandsmeldung (letzte)';
                break;

            case $amountStateMessages > 1:
                $name = 'Zustandsmeldungen (letzten ' . $amountStateMessages . ')';
                break;

            default:
                $name = 'Zustandsmeldung(en)';
        }
        IPS_SetName($this->GetIDForIdent('StateMessages'), $name);

        //Rename event messages
        $eventMessagesRetentionTime = $this->ReadPropertyInteger('EventMessagesRetentionTime');
        switch ($eventMessagesRetentionTime) {
            case 0:
                $name = 'Ereignismeldungen (deaktiviert)';
                $this->SetValue('EventMessages', '');
                break;

            case 1:
                $name = 'Ereignismeldungen (' . $eventMessagesRetentionTime . ' Tag)';
                break;

            case $eventMessagesRetentionTime > 1:
                $name = 'Ereignismeldungen (' . $eventMessagesRetentionTime . ' Tage)';
                break;

            default:
                $name = 'Ereignismeldungen';
        }
        IPS_SetName($this->GetIDForIdent('EventMessages'), $name);

        //Rename message archive
        $archiveRetentionTime = $this->ReadPropertyInteger('ArchiveRetentionTime');
        switch ($archiveRetentionTime) {
            case 0:
                $name = 'Archivdaten (deaktiviert)';
                break;

            case 1:
                $name = 'Archivdaten (' . $archiveRetentionTime . ' Tag)';
                break;

            case $archiveRetentionTime > 1:
                $name = 'Archivdaten (' . $archiveRetentionTime . ' Tage)';
                break;

            default:
                $name = 'Archivdaten';
        }
        IPS_SetName($this->GetIDForIdent('MessageArchive'), $name);
    }

    /**
     * Updates the event messages.
     *
     * @param string $Message
     * @return void
     * @throws Exception
     */
    private function UpdateEventMessages(string $Message): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, $Message, 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        if ($this->ReadPropertyInteger('EventMessagesRetentionTime') > 0) {
            $content = array_merge(array_filter(explode("\n", $this->GetValue('EventMessages'))));
            foreach ($content as $key => $message) {
                //Delete empty message hint
                if (strpos($message, 'Keine Ereignismeldungen vorhanden!') !== false) {
                    unset($content[$key]);
                }
            }
            //Add new message at beginning
            array_unshift($content, $Message);
            $newContent = implode("\n", $content);
            $this->SetValue('EventMessages', $newContent);
        }
    }

    /**
     * Updates the state messages.
     *
     * @param string $Message
     * @return void
     * @throws Exception
     */
    private function UpdateStateMessages(string $Message): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, $Message, 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        //Check amount of messages to display
        $amountStateMessages = $this->ReadPropertyInteger('AmountStateMessages');
        if ($amountStateMessages > 0) {
            if ($amountStateMessages == 1) {
                $this->SetValue('AlarmMessages', $Message);
            } else {
                $content = array_merge(array_filter(explode("\n", $this->GetValue('StateMessages'))));
                foreach ($content as $key => $message) {
                    //Delete empty message hint
                    if (strpos($message, 'Keine Zustandsmeldungen vorhanden!') !== false) {
                        unset($content[$key]);
                    }
                }
                $entries = $amountStateMessages - 1;
                array_splice($content, $entries);
                array_unshift($content, $Message);
                $newContent = implode("\n", $content);
                $this->SetValue('StateMessages', $newContent);
            }
        }
    }

    /**
     * Updates the alarm messages.
     *
     * @param string $Message
     * @return void
     * @throws Exception
     */
    private function UpdateAlarmMessages(string $Message): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, $Message, 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $retentionTime = $this->ReadPropertyInteger('AlarmMessagesRetentionTime');
        if ($retentionTime > 0) {
            $content = array_merge(array_filter(explode("\n", $this->GetValue('AlarmMessages'))));
            foreach ($content as $key => $message) {
                //Delete empty message hint
                if (strpos($message, 'Keine Alarmmeldungen vorhanden!') !== false) {
                    unset($content[$key]);
                }
            }
            //Add new message at beginning
            array_unshift($content, $Message);
            $newContent = implode("\n", $content);
            $this->SetValue('AlarmMessages', $newContent);
        }
    }

    /**
     * Set the timer for cleanup the messages.
     *
     * @return void
     * @throws Exception
     */
    private function SetCleanUpMessagesTimer(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        //Set timer to next day
        $timestamp = mktime(0, 05, 0, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
        $timerInterval = ($timestamp - time()) * 1000;
        $this->SetTimerInterval('CleanUpMessages', $timerInterval);
    }

    /**
     * Attempts to set a semaphore and repeats this up to 100 times if unsuccessful.
     *
     * @return bool
     */
    private function LockSemaphore(): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        for ($i = 0; $i < 100; $i++) {
            if (IPS_SemaphoreEnter(__CLASS__ . '.' . $this->InstanceID . 'UpdateMessages', 1)) {
                $this->SendDebug(__FUNCTION__, 'Semaphore locked', 0);
                return true;
            } else {
                IPS_Sleep(mt_rand(1, 5));
            }
        }
        return false;
    }

    /**
     * Deletes a semaphore.
     */
    private function UnlockSemaphore(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        @IPS_SemaphoreLeave(__CLASS__ . '.' . $this->InstanceID . 'UpdateMessages');
        $this->SendDebug(__FUNCTION__, 'Semaphore unlocked', 0);
    }
}