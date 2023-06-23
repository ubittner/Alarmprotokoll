<?php

/**
 * @project       Alarmprotokoll/Alarmprotokoll
 * @file          AP_Config.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

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
     * Expands or collapses the expansion panels.
     *
     * @param bool $State
     * false =  collapse,
     * true =   expand
     *
     * @return void
     */
    public function ExpandExpansionPanels(bool $State): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->UpdateFormField('Panel' . $i, 'expanded', $State);
        }
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
        if ($ObjectID > 1 && @IPS_ObjectExists($ObjectID)) {
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

        //Configuration buttons
        $form['elements'][0] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration ausklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, true);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration einklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, false);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration neu laden',
                        'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                    ]
                ]
            ];

        //Info
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel1',
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
                    'caption' => "Version:\t\t" . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date'])
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Entwickler:\t" . $library['Author']
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

        //Archive
        $id = $this->ReadPropertyInteger('Archive');
        $enabled = false;
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $enabled = true;
        }

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Archivierung',
            'name'    => 'Panel2',
            'items'   => [
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectModule',
                            'name'     => 'Archive',
                            'caption'  => 'Archiv',
                            'moduleID' => self::ARCHIVE_MODULE_GUID,
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "ArchiveConfigurationButton", "ID " . $Archive . " verwalten", $Archive);'
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
                ]
            ]
        ];

        //Monthly protocol

        //Monthly SMTP
        $monthlySMTP = $this->ReadPropertyInteger('MonthlySMTP');
        $enabled = false;
        if ($monthlySMTP > 1 && @IPS_ObjectExists($monthlySMTP)) {
            $enabled = true;
        }

        //Monthly recipient list
        $monthlyRecipientValues = [];
        $recipients = json_decode($this->ReadPropertyString('MonthlyRecipientList'), true);
        foreach ($recipients as $recipient) {
            $rowColor = '#C0FFC0'; //light green
            if (!$recipient['Use']) {
                $rowColor = '#DFDFDF'; //grey
            }
            $address = $recipient['Address'];
            if (empty($address) || strlen($address) < 6) {
                $rowColor = '#FFC0C0'; //red
            }
            $monthlyRecipientValues[] = ['rowColor' => $rowColor];
        }

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Protokoll',
            'name'    => 'Panel3',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Monatsprotokoll',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseMonthlyProtocol',
                    'caption' => 'Monatsprotokoll'
                ],
                [
                    'type'    => 'Select',
                    'name'    => 'MonthlyProtocolDay',
                    'caption' => 'Versenden am',
                    'options' => [
                        [
                            'caption' => '1.',
                            'value'   => 1
                        ],
                        [
                            'caption' => '2.',
                            'value'   => 2
                        ],
                        [
                            'caption' => '3.',
                            'value'   => 3
                        ],
                        [
                            'caption' => '4.',
                            'value'   => 4
                        ],
                        [
                            'caption' => '5.',
                            'value'   => 5
                        ],
                        [
                            'caption' => '6.',
                            'value'   => 6
                        ],
                        [
                            'caption' => '7.',
                            'value'   => 7
                        ],
                        [
                            'caption' => '8.',
                            'value'   => 8
                        ],
                        [
                            'caption' => '9.',
                            'value'   => 9
                        ],
                        [
                            'caption' => '10.',
                            'value'   => 10
                        ],
                        [
                            'caption' => '11.',
                            'value'   => 11
                        ],
                        [
                            'caption' => '12.',
                            'value'   => 12
                        ],
                        [
                            'caption' => '13.',
                            'value'   => 13
                        ],
                        [
                            'caption' => '14.',
                            'value'   => 14
                        ],
                        [
                            'caption' => '15.',
                            'value'   => 15
                        ],
                        [
                            'caption' => '16.',
                            'value'   => 16
                        ],
                        [
                            'caption' => '17.',
                            'value'   => 17
                        ],
                        [
                            'caption' => '18.',
                            'value'   => 18
                        ],
                        [
                            'caption' => '19.',
                            'value'   => 19
                        ],
                        [
                            'caption' => '20.',
                            'value'   => 20
                        ],
                        [
                            'caption' => '21.',
                            'value'   => 21
                        ],
                        [
                            'caption' => '22.',
                            'value'   => 22
                        ],
                        [
                            'caption' => '23.',
                            'value'   => 23
                        ],
                        [
                            'caption' => '24.',
                            'value'   => 24
                        ],
                        [
                            'caption' => '25.',
                            'value'   => 25
                        ],
                        [
                            'caption' => '26.',
                            'value'   => 26
                        ],
                        [
                            'caption' => '27.',
                            'value'   => 27
                        ],
                        [
                            'caption' => '28.',
                            'value'   => 28
                        ],
                        [
                            'caption' => '29.',
                            'value'   => 29
                        ],
                        [
                            'caption' => '30.',
                            'value'   => 30
                        ],
                        [
                            'caption' => '31.',
                            'value'   => 31
                        ]
                    ]
                ],
                [
                    'type'    => 'SelectTime',
                    'name'    => 'MonthlyProtocolTime',
                    'caption' => 'Versenden um'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Protokollkopfzeile',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'TextFileTitle',
                    'caption' => 'Titel',
                    'width'   => '600px'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'TextFileDescription',
                    'caption' => 'Bezeichnung',
                    'width'   => '600px'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'E-Mail',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectModule',
                            'name'     => 'MonthlySMTP',
                            'caption'  => 'SMTP Instanz',
                            'moduleID' => self::SMTP_MODULE_GUID,
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "MonthlySMTPConfigurationButton", "ID " . $MonthlySMTP . " konfigurieren", $MonthlySMTP);'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'caption'  => 'ID ' . $monthlySMTP . ' konfigurieren',
                            'name'     => 'MonthlySMTPConfigurationButton',
                            'visible'  => $enabled,
                            'objectID' => $monthlySMTP
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateSMTPInstance($id);'
                        ]
                    ]
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'MonthlyProtocolSubject',
                    'caption' => 'Betreff',
                    'width'   => '600px'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'MonthlyProtocolText',
                    'caption' => 'Text',
                    'width'   => '600px'
                ],
                [
                    'type'     => 'List',
                    'name'     => 'MonthlyRecipientList',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Empfänger',
                            'name'    => 'Name',
                            'width'   => '350px',
                            'add'     => '',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'E-Mail Adresse',
                            'name'    => 'Address',
                            'width'   => '400px',
                            'add'     => '@',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ]
                    ],
                    'values' => $monthlyRecipientValues
                ]
            ]
        ];

        //Visualisation

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel4',
            'caption' => 'Visualisierung',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Aktiv',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableActive',
                    'caption' => 'Aktiv'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Alarmmeldungen',
                    'bold'    => true,
                    'italic'  => true
                ],
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
                    'type'    => 'Label',
                    'caption' => 'Zustandsmeldungen',
                    'bold'    => true,
                    'italic'  => true
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
                    'type'    => 'Label',
                    'caption' => 'Ereignismeldungen',
                    'bold'    => true,
                    'italic'  => true
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
                ]
            ]
        ];

        ########## Actions

        //Protocol
        $startTime = strtotime('first day of previous month midnight');
        $endTime = strtotime('first day of this month midnight') - 1;
        $startDate = '{"day":' . date('j', $startTime) . ',"month":' . date('n', $startTime) . ',"year":' . date('Y', $startTime) . '}';
        $endDate = '{"day":' . date('j', $endTime) . ', "month":' . date('n', $endTime) . ', "year":' . date('Y', $endTime) . '}';
        $form['actions'][] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'SelectDate',
                        'name'    => 'StartDate',
                        'caption' => 'Datum von',
                        'value'   => $startDate
                    ],
                    [
                        'type'    => 'SelectDate',
                        'name'    => 'EndDate',
                        'caption' => 'Datum bis',
                        'value'   => $endDate
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => ' '
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Protokoll erstellen',
                        'onClick' => self::MODULE_PREFIX . '_GenerateTextFileCustomData($id, $StartDate, $EndDate);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die Textdatei wurde erfolgreich erstellt!");'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Protokoll versenden',
                        'onClick' => self::MODULE_PREFIX . '_SendProtocol($id); ' . self::MODULE_PREFIX . '_UIShowMessage($id, "Das Protokoll wurde versendet!");'
                    ]
                ]
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        //Test center
        $form['actions'][] =
            [
                'type' => 'TestCenter'
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
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

        //Developer area
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Entwicklerbereich',
            'items'   => [
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'PopupButton',
                            'caption' => 'Alle Meldungen löschen',
                            'popup'   => [
                                'caption' => 'Wirklich alle Meldungen der Anzeige löschen?',
                                'items'   => [
                                    [
                                        'type'    => 'Button',
                                        'caption' => 'Löschen',
                                        'onClick' => self::MODULE_PREFIX . '_DeleteAllMessages($id);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Es wurden alle Meldungen gelöscht!");'
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
                                        'onClick' => self::MODULE_PREFIX . '_DeleteAlarmMessages($id);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Es wurden alle Alarmmeldungen gelöscht!");'
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
                                        'onClick' => self::MODULE_PREFIX . '_DeleteStateMessages($id);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Es wurden alle Zustandsmeldungen gelöscht!");'
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
                                        'onClick' => self::MODULE_PREFIX . '_DeleteEventMessages($id);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Es wurden alle Ereignismeldungen gelöscht!");'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type'    => 'PopupButton',
                            'caption' => 'Archivdaten bereinigen',
                            'name'    => 'DataCleanUpConfigurationButton',
                            'visible' => $enabled,
                            'popup'   => [
                                'caption' => 'Wirklich alle Archivdaten bereinigen?',
                                'items'   => [
                                    [
                                        'type'    => 'Button',
                                        'caption' => 'Bereinigen',
                                        'onClick' => self::MODULE_PREFIX . '_CleanUpMessages($id);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Es wurden alle Daten bereinigt!");'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredReferences',
                    'caption'  => 'Registrierte Referenzen',
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
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredMessages',
                    'caption'  => 'Registrierte Nachrichten',
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

        $form['actions'][] =
            [
                'type'    => 'PopupAlert',
                'name'    => 'InfoMessage',
                'visible' => false,
                'popup'   => [
                    'closeCaption' => 'OK',
                    'items'        => [
                        [
                            'type'    => 'Label',
                            'name'    => 'InfoMessageLabel',
                            'caption' => '',
                            'visible' => true
                        ]
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