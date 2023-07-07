<?php
/**
 * @project       Bewegungsmelderstatus/Bewegungsmelderstatus
 * @file          BWMS_MonitoredVariables.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait BWMS_MonitoredVariables
{
    /**
     * Determines automatically the variables of all existing motion detectors.
     *
     * @param int $DeterminationType
     * @param string $DeterminationValue
     * @return void
     * @throws Exception
     */
    public function DetermineMotionDetectorVariables(int $DeterminationType, string $DeterminationValue): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Auswahl: ' . $DeterminationType, 0);
        $this->SendDebug(__FUNCTION__, 'Identifikator: ' . $DeterminationValue, 0);

        $this->UpdateFormField('MotionDetectorProgress', 'minimum', 0);
        $maximumVariables = count(IPS_GetVariableList());
        $this->UpdateFormField('MotionDetectorProgress', 'maximum', $maximumVariables);

        $determineIdent = false;
        $determineProfile = false;

        //Determine variables first
        $determinedVariables = [];
        $passedVariables = 0;
        foreach (@IPS_GetVariableList() as $variable) {
            switch ($DeterminationType) {
                case 0: //Custom Ident
                    if ($DeterminationValue == '') {
                        $infoText = 'Abbruch, es wurde kein Identifikator angegeben!';
                        $this->UpdateFormField('InfoMessage', 'visible', true);
                        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
                        return;
                    } else {
                        $determineIdent = true;
                    }
                    break;

                case 1: //Ident: MOTION
                    $determineIdent = true;
                    break;

                case 2: //Custom Profile
                    if ($DeterminationValue == '') {
                        $infoText = 'Abbruch, es wurde kein Profilname angegeben!';
                        $this->UpdateFormField('InfoMessage', 'visible', true);
                        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
                        return;
                    } else {
                        $determineProfile = true;
                    }
                    break;

                case 3: //Profile: ~Motion
                case 4: //Profile: ~Motion.Reversed
                case 5: //Profile: ~Motion.HM
                    $determineProfile = true;
                    break;
            }

            $passedVariables++;
            $this->UpdateFormField('MotionDetectorProgress', 'visible', true);
            $this->UpdateFormField('MotionDetectorProgress', 'current', $passedVariables);
            $this->UpdateFormField('MotionDetectorProgressInfo', 'visible', true);
            $this->UpdateFormField('MotionDetectorProgressInfo', 'caption', $passedVariables . '/' . $maximumVariables);
            IPS_Sleep(25);

            ##### Ident

            //Determine via ident
            if ($determineIdent && !$determineProfile) {
                switch ($DeterminationType) {
                    case 0: //Custom ident
                        $objectIdents = $DeterminationValue;
                        break;

                    case 1: //Ident: MOTION
                        $objectIdents = 'MOTION';
                        break;

                }
                if (isset($objectIdents)) {
                    $objectIdents = str_replace(' ', '', $objectIdents);
                    $objectIdents = explode(',', $objectIdents);
                    foreach ($objectIdents as $objectIdent) {
                        $object = @IPS_GetObject($variable);
                        if ($object['ObjectIdent'] == $objectIdent) {
                            $name = @IPS_GetName($variable);
                            $address = '';
                            $parent = @IPS_GetParent($variable);
                            if ($parent > 1 && @IPS_ObjectExists($parent)) {
                                $parentObject = @IPS_GetObject($parent);
                                if ($parentObject['ObjectType'] == 1) { //1 = instance
                                    $name = strstr(@IPS_GetName($parent), ':', true);
                                    if (!$name) {
                                        $name = @IPS_GetName($parent);
                                    }
                                    $address = @IPS_GetProperty($parent, 'Address');
                                    if (!$address) {
                                        $address = '';
                                    }
                                }
                            }
                            $value = true;
                            if (IPS_GetVariable($variable)['VariableType'] == 1) {
                                $value = 1;
                            }
                            $primaryCondition[0] = [
                                'id'        => 0,
                                'parentID'  => 0,
                                'operation' => 0,
                                'rules'     => [
                                    'variable' => [
                                        '0' => [
                                            'id'         => 0,
                                            'variableID' => $variable,
                                            'comparison' => 0,
                                            'value'      => $value,
                                            'type'       => 0
                                        ]
                                    ],
                                    'date'         => [],
                                    'time'         => [],
                                    'dayOfTheWeek' => []
                                ]
                            ];
                            $determinedVariables[] = [
                                'Use'                => true,
                                'Designation'        => $name,
                                'Comment'            => $address,
                                'PrimaryCondition'   => json_encode($primaryCondition),
                                'SecondaryCondition' => '[]'];
                        }
                    }
                }
            }

            ##### Profile

            //Determine via profile
            if ($determineProfile && !$determineIdent) {
                switch ($DeterminationType) {
                    case 2: //Custom ident
                        $profileNames = $DeterminationValue;
                        break;

                    case 3:
                        $profileNames = '~Motion';
                        break;

                    case 4:
                        $profileNames = '~Motion.Reversed';
                        break;

                    case 5:
                        $profileNames = '~Motion.HM';
                        break;

                }
                if (isset($profileNames)) {
                    $profileNames = str_replace(' ', '', $profileNames);
                    $profileNames = explode(',', $profileNames);
                    foreach ($profileNames as $profileName) {
                        $variableData = IPS_GetVariable($variable);
                        if ($variableData['VariableCustomProfile'] == $profileName || $variableData['VariableProfile'] == $profileName) {
                            $name = @IPS_GetName($variable);
                            $address = '';
                            $parent = @IPS_GetParent($variable);
                            if ($parent > 1 && @IPS_ObjectExists($parent)) {
                                $parentObject = @IPS_GetObject($parent);
                                if ($parentObject['ObjectType'] == 1) { //1 = instance
                                    $name = strstr(@IPS_GetName($parent), ':', true);
                                    if (!$name) {
                                        $name = @IPS_GetName($parent);
                                    }
                                    $address = @IPS_GetProperty($parent, 'Address');
                                    if (!$address) {
                                        $address = '';
                                    }
                                }
                            }
                            $value = true;
                            if (IPS_GetVariable($variable)['VariableType'] == 1) {
                                $value = 1;
                            }
                            $primaryCondition[0] = [
                                'id'        => 0,
                                'parentID'  => 0,
                                'operation' => 0,
                                'rules'     => [
                                    'variable' => [
                                        '0' => [
                                            'id'         => 0,
                                            'variableID' => $variable,
                                            'comparison' => 0,
                                            'value'      => $value,
                                            'type'       => 0
                                        ]
                                    ],
                                    'date'         => [],
                                    'time'         => [],
                                    'dayOfTheWeek' => []
                                ]
                            ];
                            $determinedVariables[] = [
                                'Use'                => true,
                                'Designation'        => $name,
                                'Comment'            => $address,
                                'PrimaryCondition'   => json_encode($primaryCondition),
                                'SecondaryCondition' => '[]'];
                        }
                    }
                }
            }
        }

        //Get already listed variables
        $listedVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($determinedVariables as $determinedVariable) {
            if (array_key_exists('PrimaryCondition', $determinedVariable)) {
                $primaryCondition = json_decode($determinedVariable['PrimaryCondition'], true);
                if ($primaryCondition != '') {
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $determinedVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($determinedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                                //Check variable id with already listed variable ids
                                $add = true;
                                foreach ($listedVariables as $listedVariable) {
                                    if (array_key_exists('PrimaryCondition', $listedVariable)) {
                                        $primaryCondition = json_decode($listedVariable['PrimaryCondition'], true);
                                        if ($primaryCondition != '') {
                                            if (array_key_exists(0, $primaryCondition)) {
                                                if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                                    $listedVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                                    if ($listedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                                                        if ($determinedVariableID == $listedVariableID) {
                                                            $add = false;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                //Add new variable to already listed variables
                                if ($add) {
                                    $listedVariables[] = $determinedVariable;
                                }
                            }
                        }
                    }
                }
            }
        }
        if (empty($determinedVariables)) {
            $this->UpdateFormField('MotionDetectorProgress', 'visible', false);
            $this->UpdateFormField('MotionDetectorProgressInfo', 'visible', false);
            $infoText = 'Es wurden keinen Variablen gefunden!';
            $this->UpdateFormField('InfoMessage', 'visible', true);
            $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
            return;
        }
        //Sort variables by name
        array_multisort(array_column($listedVariables, 'Designation'), SORT_ASC, $listedVariables);
        @IPS_SetProperty($this->InstanceID, 'TriggerList', json_encode(array_values($listedVariables)));
        if (@IPS_HasChanges($this->InstanceID)) {
            @IPS_ApplyChanges($this->InstanceID);
        }
    }

    public function CheckMotionDetectorDeterminationValue(int $MotionDetectorDeterminationType): void
    {
        $visible = false;
        if ($MotionDetectorDeterminationType == 0) {
            $this->UpdateFormfield('MotionDetectorDeterminationValue', 'caption', 'Identifikator');
            $visible = true;
        }
        if ($MotionDetectorDeterminationType == 2) {
            $this->UpdateFormfield('MotionDetectorDeterminationValue', 'caption', 'Profilname');
            $visible = true;
        }
        $this->UpdateFormfield('MotionDetectorDeterminationValue', 'visible', $visible);
    }

    public function AssignMotionDetectorVariableProfile(): void
    {
        //Only assign a standard profile, a reversed profile must be assigned manually by the user!
        $listedVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        $maximumVariables = count($listedVariables);
        $this->UpdateFormField('AssignMotionDetectorVariableProfileProgress', 'minimum', 0);
        $this->UpdateFormField('AssignMotionDetectorVariableProfileProgress', 'maximum', $maximumVariables);
        $passedVariables = 0;
        foreach ($listedVariables as $variable) {
            $passedVariables++;
            $this->UpdateFormField('AssignMotionDetectorVariableProfileProgress', 'visible', true);
            $this->UpdateFormField('AssignMotionDetectorVariableProfileProgress', 'current', $passedVariables);
            $this->UpdateFormField('AssignMotionDetectorVariableProfileProgressInfo', 'visible', true);
            $this->UpdateFormField('AssignMotionDetectorVariableProfileProgressInfo', 'caption', $passedVariables . '/' . $maximumVariables);
            IPS_Sleep(250);
            $id = 0;
            //Primary condition
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                    }
                }
            }
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $object = IPS_GetObject($id)['ObjectType'];
                //0: Category, 1: Instance, 2: Variable, 3: Script, 4: Event, 5: Media, 6: Link)
                if ($object == 2) {
                    $variable = IPS_GetVariable($id)['VariableType'];
                    switch ($variable) {
                        //0: Boolean, 1: Integer, 2: Float, 3: String
                        case 0:
                            $profileName = 'MotionDetector.Bool';
                            break;

                        case 1:
                            $profileName = 'MotionDetector.Integer';
                            break;

                        default:
                            $profileName = '';
                    }
                    if (!empty($profileName)) {
                        //Assign profile
                        IPS_SetVariableCustomProfile($id, $profileName);
                        //Deactivate standard action
                        IPS_SetVariableCustomAction($id, 1);
                    }
                }
            }
        }
        if ($maximumVariables == 0) {
            $infoText = 'Es sind keine Variablen vorhanden!';
        } else {
            $this->UpdateFormField('AssignMotionDetectorVariableProfileProgress', 'visible', false);
            $this->UpdateFormField('AssignMotionDetectorVariableProfileProgressInfo', 'visible', false);
            $infoText = 'Variablenprofil wurde erfolgreich zugewiesen!';
        }
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
    }

    /**
     * Creates links of monitored variables.
     *
     * @param int $LinkCategory
     * @return void
     * @throws Exception
     */
    public function CreateVariableLinks(int $LinkCategory): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($LinkCategory == 1 || @!IPS_ObjectExists($LinkCategory)) {
            $this->UIShowMessage('Abbruch, bitte wählen Sie eine Kategorie aus!');
            return;
        }
        $icon = 'Motion';
        //Get all monitored variables
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        $maximumVariables = count($monitoredVariables);
        $this->UpdateFormField('VariableLinkProgress', 'minimum', 0);
        $this->UpdateFormField('VariableLinkProgress', 'maximum', $maximumVariables);
        $passedVariables = 0;
        $targetIDs = [];
        $i = 0;
        foreach ($monitoredVariables as $variable) {
            if ($variable['Use']) {
                $passedVariables++;
                $this->UpdateFormField('VariableLinkProgress', 'visible', true);
                $this->UpdateFormField('VariableLinkProgress', 'current', $passedVariables);
                $this->UpdateFormField('VariableLinkProgressInfo', 'visible', true);
                $this->UpdateFormField('VariableLinkProgressInfo', 'caption', $passedVariables . '/' . $maximumVariables);
                IPS_Sleep(200);
                //Primary condition
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($id > 1 && @IPS_ObjectExists($id)) {
                                $targetIDs[$i] = ['name' => $variable['Designation'], 'targetID' => $id];
                                $i++;
                            }
                        }
                    }
                }
            }
        }
        //Sort array alphabetically by device name
        sort($targetIDs);
        //Get all existing links (links have not an ident field, so we use the object info field)
        $existingTargetIDs = [];
        $links = @IPS_GetLinkList();
        if (!empty($links)) {
            $i = 0;
            foreach ($links as $link) {
                $linkInfo = @IPS_GetObject($link)['ObjectInfo'];
                if ($linkInfo == self::MODULE_PREFIX . '.' . $this->InstanceID) {
                    //Get target id
                    $existingTargetID = @IPS_GetLink($link)['TargetID'];
                    $existingTargetIDs[$i] = ['linkID' => $link, 'targetID' => $existingTargetID];
                    $i++;
                }
            }
        }
        //Delete dead links
        $deadLinks = array_diff(array_column($existingTargetIDs, 'targetID'), array_column($targetIDs, 'targetID'));
        if (!empty($deadLinks)) {
            foreach ($deadLinks as $targetID) {
                $position = array_search($targetID, array_column($existingTargetIDs, 'targetID'));
                $linkID = $existingTargetIDs[$position]['linkID'];
                if (@IPS_LinkExists($linkID)) {
                    @IPS_DeleteLink($linkID);
                }
            }
        }
        //Create new links
        $newLinks = array_diff(array_column($targetIDs, 'targetID'), array_column($existingTargetIDs, 'targetID'));
        if (!empty($newLinks)) {
            foreach ($newLinks as $targetID) {
                $linkID = @IPS_CreateLink();
                @IPS_SetParent($linkID, $LinkCategory);
                $position = array_search($targetID, array_column($targetIDs, 'targetID'));
                @IPS_SetPosition($linkID, $position);
                $name = $targetIDs[$position]['name'];
                @IPS_SetName($linkID, $name);
                @IPS_SetLinkTargetID($linkID, $targetID);
                @IPS_SetInfo($linkID, self::MODULE_PREFIX . '.' . $this->InstanceID);
                @IPS_SetIcon($linkID, $icon);
            }
        }
        //Edit existing links
        $existingLinks = array_intersect(array_column($existingTargetIDs, 'targetID'), array_column($targetIDs, 'targetID'));
        if (!empty($existingLinks)) {
            foreach ($existingLinks as $targetID) {
                $position = array_search($targetID, array_column($targetIDs, 'targetID'));
                $targetID = $targetIDs[$position]['targetID'];
                $index = array_search($targetID, array_column($existingTargetIDs, 'targetID'));
                $linkID = $existingTargetIDs[$index]['linkID'];
                @IPS_SetPosition($linkID, $position);
                $name = $targetIDs[$position]['name'];
                @IPS_SetName($linkID, $name);
                @IPS_SetInfo($linkID, self::MODULE_PREFIX . '.' . $this->InstanceID);
                @IPS_SetIcon($linkID, $icon);
            }
        }
        $this->UpdateFormField('VariableLinkProgress', 'visible', false);
        $this->UpdateFormField('VariableLinkProgressInfo', 'visible', false);
        $this->UIShowMessage('Die Variablenverknüpfungen wurden erfolgreich erstellt!');
    }

    /**
     * Updates the status.
     *
     * @return bool
     * false    = all motion detectors are idle
     * true     = at least one motion was detected
     *
     * @throws Exception
     */
    public function UpdateStatus(): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if (!$this->CheckForExistingVariables()) {
            return false;
        }

        ##### Update overall status

        $variables = json_decode($this->GetMonitoredVariables(), true);
        $actualOverallStatus = false;
        foreach ($variables as $variable) {
            if ($variable['ActualStatus'] == 1) {
                $actualOverallStatus = true;
            }
        }
        $this->SetValue('Status', $actualOverallStatus);

        $this->SetValue('LastUpdate', date('d.m.Y H:i:s'));

        ##### Update overview list for WebFront

        $string = '';
        if ($this->ReadPropertyBoolean('EnableSensorList')) {
            $string .= "<table style='width: 100%; border-collapse: collapse;'>";
            $string .= '<tr><td><b>Status</b></td><td><b>Name</b></td><td><b>Bemerkung</b></td><td><b>ID</b></td></tr>';
            //Sort variables by name
            array_multisort(array_column($variables, 'Name'), SORT_ASC, $variables);
            //Rebase array
            $variables = array_values($variables);
            $separator = false;
            if (!empty($variables)) {
                //Show motion detected first
                if ($this->ReadPropertyBoolean('EnableMotionDetected')) {
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 1) {
                                $separator = true;
                                $string .= '<tr><td>' . $variable['StatusText'] . '</td><td>' . $variable['Name'] . '</td><td>' . $variable['Comment'] . '</td><td>' . $id . '</td></tr>';
                            }
                        }
                    }
                }
                //Idle is next
                if ($this->ReadPropertyBoolean('EnableIdle')) {
                    //Check if we have an existing element for a spacer
                    $existingElement = false;
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 0) {
                                $existingElement = true;
                            }
                        }
                    }
                    //Add spacer
                    if ($separator && $existingElement) {
                        $string .= '<tr><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td></tr>';
                    }
                    //Add idle sensors
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 0) {
                                $string .= '<tr><td>' . $variable['StatusText'] . '</td><td>' . $variable['Name'] . '</td><td>' . $variable['Comment'] . '</td><td>' . $id . '</td></tr>';
                            }
                        }
                    }
                }
            }
            $string .= '</table>';
        }
        $this->SetValue('SensorList', $string);
        return $actualOverallStatus;
    }

    #################### Private

    private function CreateMotionDetectorVariableProfiles(): void
    {
        //Bool variable
        $profile = 'MotionDetector.Bool';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Untätig', 'Information', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, 'Bewegung erkannt', 'Motion', 0xFF0000);

        //Bool variable reversed
        $profile = 'MotionDetector.Bool.Reversed';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Bewegung erkannt', 'Motion', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, 'Untätig', 'Information', 0x00FF00);

        //Integer variable
        $profile = 'MotionDetector.Integer';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Untätig', 'Information', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, 'Bewegung erkannt', 'Motion', 0xFF0000);

        //Integer variable reversed
        $profile = 'MotionDetector.Integer.Reversed';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Bewegung erkannt', 'Motion', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, 'Untätig', 'Information', 0x00FF00);
    }

    /**
     * Checks for monitored variables.
     *
     * @return bool
     * false =  There are no monitored variables
     * true =   There are monitored variables
     * @throws Exception
     */
    private function CheckForExistingVariables(): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($monitoredVariables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                            return true;
                        }
                    }
                }
            }
        }
        $this->SendDebug(__FUNCTION__, 'Abbruch, Es werden keine Variablen überwacht!', 0);
        return false;
    }

    /**
     * Gets the monitored variables and their status.
     *
     * @return string
     * @throws Exception
     */
    private function GetMonitoredVariables(): string
    {
        $result = [];
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($monitoredVariables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $id = 0;
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id <= 1 || @!IPS_ObjectExists($id)) { //0 = main category, 1 = none
                            continue;
                        }
                    }
                }
            }
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $actualStatus = 0; //0 = idle
                $statusText = $this->ReadPropertyString('SensorListIdleText');
                if (IPS_IsConditionPassing($variable['PrimaryCondition']) && IPS_IsConditionPassing($variable['SecondaryCondition'])) {
                    $actualStatus = 1; //1 = motion detected
                    $statusText = $this->ReadPropertyString('SensorListMotionDetectedText');
                }
                $result[] = [
                    'ID'           => $id,
                    'Name'         => $variable['Designation'],
                    'Comment'      => $variable['Comment'],
                    'ActualStatus' => $actualStatus,
                    'StatusText'   => $statusText];
            }
        }
        return json_encode($result);
    }
}