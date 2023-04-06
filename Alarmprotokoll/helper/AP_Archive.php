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
    #################### Private

    /**
     * Sets the archive logging.
     *
     * @return bool
     * @throws Exception
     */
    private function SetArchiveLogging(): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $result = false;
        $archiveID = $this->ReadPropertyInteger('Archive');
        $variableID = $this->GetIDForIdent('MessageArchive');
        if ($archiveID > 1 && @IPS_ObjectExists($archiveID)) { //0 = main category, 1 = none
            if ($variableID > 1 && @IPS_ObjectExists($variableID)) {
                $this->SendDebug(__FUNCTION__, 'Daten werden archiviert!', 0);
                $result = AC_SetLoggingStatus($archiveID, $variableID, true);
                if (IPS_HasChanges($archiveID)) {
                    @IPS_ApplyChanges($archiveID);
                }
            }
        }
        if ($archiveID == 0) {
            $archives = IPS_GetInstanceListByModuleID(self::ARCHIVE_MODULE_GUID);
            if ($variableID > 1 && @IPS_ObjectExists($variableID)) {
                foreach ($archives as $archive) {
                    $variables = @AC_GetAggregationVariables($archive, false);
                    foreach ($variables as $variable) {
                        if ($variable['VariableID'] == $variableID) {
                            $this->SendDebug(__FUNCTION__, 'Daten werden nicht archiviert!', 0);
                            $result = AC_SetLoggingStatus($archive, $variableID, false);
                            if (IPS_HasChanges($archive)) {
                                @IPS_ApplyChanges($archive);
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
}