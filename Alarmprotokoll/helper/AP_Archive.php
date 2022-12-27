<?php

/**
 * @project       Alarmprotokoll/Alarmprotokoll
 * @file          AP_Archive.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

declare(strict_types=1);

trait AP_Archive
{
    /**
     * Sets the archive logging.
     *
     * @param bool $State
     * false =  don't log
     * true =   log
     *
     * @return void
     * @throws Exception
     */
    public function SetArchiveLogging(bool $State): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $id = $this->ReadPropertyInteger('Archive');
        if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
            @AC_SetLoggingStatus($id, $this->GetIDForIdent('MessageArchive'), $State);
            @IPS_ApplyChanges($id);
            $text = 'Es werden keine Daten mehr archiviert!';
            if ($State) {
                $text = 'Die Daten werden archiviert!';
            }
            $this->SendDebug(__FUNCTION__, $text, 0);
        } else {
            $this->SendDebug(__FUNCTION__, 'Es ist kein Archiv ausgewählt!', 0);
        }
    }
}