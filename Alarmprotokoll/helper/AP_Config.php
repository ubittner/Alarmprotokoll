<?php

/**
 * @project       Alarmprotokoll/Alarmprotokoll
 * @file          AP_Config.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnused */

declare(strict_types=1);

trait AP_Config
{
    /**
     * Reloads the configuration form.
     *
     * @return void
     */
    public function ReloadConfig(): void
    {
        $this->ReloadForm();
    }

    /**
     * Modifies a configuration button.
     *
     * @param string $Field
     * @param string $Caption
     * @param int $ObjectID
     * @return void
     */
    public function ModifyButton(string $Field, string $Caption, int $ObjectID): void
    {
        $state = false;
        if ($ObjectID > 1 && @IPS_ObjectExists($ObjectID)) { //0 = main category, 1 = none
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', $Caption);
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $ObjectID);
    }

    /**
     * Gets the configuration form.
     *
     * @return false|string
     * @throws Exception
     */
    public function GetConfigurationForm()
    {
        $form = [];

        ########## Elements

        //Info
        $form['elements'][0] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Info',
            'items'   => [
                [
                    'type'    => 'Label',
                    'name'    => 'ModuleID',
                    'caption' => "ID:\t\t\t" . $this->InstanceID
                ],
                [
                    'type'    => 'Label',
                    'name'    => 'ModuleDesignation',
                    'caption' => "Modul:\t\t" . self::MODULE_NAME
                ],
                [
                    'type'    => 'Label',
                    'name'    => 'ModulePrefix',
                    'caption' => "Präfix:\t\t" . self::MODULE_PREFIX
                ],
                [
                    'type'    => 'Label',
                    'name'    => 'ModuleVersion',
                    'caption' => "Version:\t\t" . self::MODULE_VERSION
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Note',
                    'caption' => 'Notiz',
                    'width'   => '600px'
                ]
            ]
        ];

        //Designation
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Bezeichnung',
            'items'   => [
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Designation',
                    'caption' => 'Bezeichnung (z.B. Standortbezeichnung)',
                    'width'   => '600px'
                ]
            ]
        ];

        //Functions
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Funktionen',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableActive',
                    'caption' => 'Aktiv (Schalter im WebFront)'
                ]
            ]
        ];

        //Messages
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Meldungen',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableAlarmMessages',
                    'caption' => 'Alarmmeldungen'
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'AlarmMessagesRetentionTime',
                    'caption' => 'Anzeigedauer',
                    'suffix'  => 'Tage'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableStateMessages',
                    'caption' => 'Zustandsmeldungen'
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'AmountStateMessages',
                    'caption' => 'Anzeige',
                    'suffix'  => 'Meldungen'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableEventMessages',
                    'caption' => 'Ereignismeldungen'
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'EventMessagesRetentionTime',
                    'caption' => 'Anzeigedauer',
                    'suffix'  => 'Tage'
                ],
            ]
        ];

        //Archive
        $id = $this->ReadPropertyInteger('Archive');
        $enabled = false;
        if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
            $enabled = true;
            $variables = @AC_GetAggregationVariables($id, false);
            $state = false;
            if (!empty($variables)) {
                foreach ($variables as $variable) {
                    $variableID = $variable['VariableID'];
                    if ($variableID == $this->GetIDForIdent('MessageArchive')) {
                        $state = @AC_GetLoggingStatus($id, $variableID);
                    }
                }
            }
            $text = 'Es werden keine Daten archiviert!';
            if ($state) {
                $text = 'Die Daten werden archiviert!';
            }
        } else {
            $text = 'Es ist kein Archiv ausgewählt!';
        }

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Archivierung',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseArchiving',
                    'caption' => 'Archivierung'
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectModule',
                            'name'     => 'Archive',
                            'caption'  => 'Archiv',
                            'moduleID' => self::ARCHIVE_MODULE_GUID,
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "ArchiveConfigurationButton", "ID " . $Archive . " Instanzkonfiguration", $Archive);'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'caption'  => 'ID ' . $id . ' verwalten',
                            'name'     => 'ArchiveConfigurationButton',
                            'visible'  => $enabled,
                            'objectID' => $id
                        ]
                    ]
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'ArchiveRetentionTime',
                    'caption' => 'Datenspeicherung',
                    'minimum' => 7,
                    'suffix'  => 'Tage'
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Status:',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'    => 'Label',
                    'caption' => $text
                ]
            ]
        ];

        //Protocols
        $monthlyMailer = $this->ReadPropertyInteger('MonthlyMailer');
        $monthlyMailerVisibility = false;
        if ($monthlyMailer > 1 && @IPS_ObjectExists($monthlyMailer)) { //0 = main category, 1 = none
            $monthlyMailerVisibility = true;
        }

        $archiveMailer = $this->ReadPropertyInteger('ArchiveMailer');
        $archiveMailerVisibility = false;
        if ($archiveMailer > 1 && @IPS_ObjectExists($archiveMailer)) { //0 = main category, 1 = none
            $archiveMailerVisibility = true;
        }

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Protokolle',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseMonthlyProtocol',
                    'caption' => 'Monatsprotokoll'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'MonthlyProtocolSubject',
                    'caption' => 'Betreff'
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectModule',
                            'name'     => 'MonthlyMailer',
                            'caption'  => 'Mailer (E-Mail)',
                            'moduleID' => self::MAILER_MODULE_GUID,
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "MonthlyMailerConfigurationButton", "ID " . $MonthlyMailer . " Instanzkonfiguration", $MonthlyMailer);'
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateMailerInstance($id);'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'caption'  => 'ID ' . $monthlyMailer . ' Instanzkonfiguration',
                            'name'     => 'MonthlyMailerConfigurationButton',
                            'visible'  => $monthlyMailerVisibility,
                            'objectID' => $monthlyMailer
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseArchiveProtocol',
                    'caption' => 'Archivprotokoll'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'ArchiveProtocolSubject',
                    'caption' => 'Betreff'
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectModule',
                            'name'     => 'ArchiveMailer',
                            'caption'  => 'Mailer (E-Mail)',
                            'moduleID' => self::MAILER_MODULE_GUID,
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "ArchiveMailerConfigurationButton", "ID " . $ArchiveMailer . " Instanzkonfiguration", $ArchiveMailer);'
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateMailerInstance($id);'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'caption'  => 'ID ' . $archiveMailer . ' Instanzkonfiguration',
                            'name'     => 'ArchiveMailerConfigurationButton',
                            'visible'  => $archiveMailerVisibility,
                            'objectID' => $archiveMailer
                        ]
                    ]
                ]
            ]
        ];

        ########## Actions

        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Konfiguration',
            'items'   => [
                [
                    'type'    => 'Button',
                    'caption' => 'Neu laden',
                    'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                ]
            ]
        ];

        //Test center
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Schaltfunktionen',
            'items'   => [
                [
                    'type' => 'TestCenter',
                ]
            ]
        ];

        //Messages
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Meldungen',
            'items'   => [
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Alle Meldungen löschen',
                    'popup'   => [
                        'caption' => 'Wirklich alle Meldungen der Anzeige löschen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Löschen',
                                'onClick' => self::MODULE_PREFIX . '_DeleteAllMessages($id); echo "Alle Meldungen wurden gelöscht!";'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Alarmmeldungen löschen',
                    'popup'   => [
                        'caption' => 'Wirklich alle Alarmmeldungen der Anzeige löschen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Löschen',
                                'onClick' => self::MODULE_PREFIX . '_DeleteAlarmMessages($id); echo "Alle Alarmmeldungen wurden gelöscht!";'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Zustandsmeldungen löschen',
                    'popup'   => [
                        'caption' => 'Wirklich alle Zustandsmeldungen der Anzeige löschen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Löschen',
                                'onClick' => self::MODULE_PREFIX . '_DeleteStateMessages($id); echo "Alle Zustandsmeldungen wurden gelöscht!";'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Ereignismeldungen löschen',
                    'popup'   => [
                        'caption' => 'Wirklich alle Ereignismeldungen der Anzeige löschen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Löschen',
                                'onClick' => self::MODULE_PREFIX . '_DeleteEventMessages($id); echo "Alle Ereignismeldungen wurden gelöscht!";'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Daten bereinigen',
                    'popup'   => [
                        'caption' => 'Wirklich alle Daten bereinigen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Bereinigen',
                                'onClick' => self::MODULE_PREFIX . '_CleanUpMessages($id); echo "Alle Daten wurden bereinigt!";'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        //Protocols
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Protokolle',
            'items'   => [
                [
                    'type'    => 'Button',
                    'caption' => 'Protokoll Vormonat versenden',
                    'onClick' => self::MODULE_PREFIX . '_SendMonthlyProtocol($id, false, 1); echo "Protokollversand wurde ausgelöst!";'
                ],
                [
                    'type'    => 'Button',
                    'caption' => 'Protokoll Aktueller Monat versenden',
                    'onClick' => self::MODULE_PREFIX . '_SendMonthlyProtocol($id, false, 0); echo "Protokollversand wurde ausgelöst!";'
                ],
                [
                    'type'    => 'Button',
                    'caption' => 'Archivprotokoll versenden',
                    'onClick' => self::MODULE_PREFIX . '_SendArchiveProtocol($id); echo "Archivprotokollversand wurde ausgelöst!";'
                ]
            ]
        ];

        //Registered references
        $registeredReferences = [];
        $references = $this->GetReferenceList();
        foreach ($references as $reference) {
            $name = 'Objekt #' . $reference . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($reference)) {
                $name = IPS_GetName($reference);
                $rowColor = '#C0FFC0'; //light green
            }
            $registeredReferences[] = [
                'ObjectID' => $reference,
                'Name'     => $name,
                'rowColor' => $rowColor];
        }

        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Registrierte Referenzen',
            'items'   => [
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredReferences',
                    'rowCount' => 10,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ]
                    ],
                    'values' => $registeredReferences
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredReferencesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        //Registered messages
        $registeredMessages = [];
        $messages = $this->GetMessageList();
        foreach ($messages as $id => $messageID) {
            $name = 'Objekt #' . $id . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($id)) {
                $name = IPS_GetName($id);
                $rowColor = '#C0FFC0'; //light green
            }
            switch ($messageID) {
                case [10001]:
                    $messageDescription = 'IPS_KERNELSTARTED';
                    break;

                case [10603]:
                    $messageDescription = 'VM_UPDATE';
                    break;

                default:
                    $messageDescription = 'keine Bezeichnung';
            }
            $registeredMessages[] = [
                'ObjectID'           => $id,
                'Name'               => $name,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription,
                'rowColor'           => $rowColor];
        }

        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Registrierte Nachrichten',
            'items'   => [
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredMessages',
                    'rowCount' => 10,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Nachrichten ID',
                            'name'    => 'MessageID',
                            'width'   => '150px'
                        ],
                        [
                            'caption' => 'Nachrichten Bezeichnung',
                            'name'    => 'MessageDescription',
                            'width'   => '250px'
                        ]
                    ],
                    'values' => $registeredMessages
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredMessagesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        ########## Status

        $form['status'][] = [
            'code'    => 101,
            'icon'    => 'active',
            'caption' => self::MODULE_NAME . ' wird erstellt',
        ];
        $form['status'][] = [
            'code'    => 102,
            'icon'    => 'active',
            'caption' => self::MODULE_NAME . ' ist aktiv',
        ];
        $form['status'][] = [
            'code'    => 103,
            'icon'    => 'active',
            'caption' => self::MODULE_NAME . ' wird gelöscht',
        ];
        $form['status'][] = [
            'code'    => 104,
            'icon'    => 'inactive',
            'caption' => self::MODULE_NAME . ' ist inaktiv',
        ];
        $form['status'][] = [
            'code'    => 200,
            'icon'    => 'inactive',
            'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug!',
        ];

        return json_encode($form);
    }
}