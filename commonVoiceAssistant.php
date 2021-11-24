<?php

declare(strict_types=1);

trait CommonVoiceAssistant
{
    protected $registry = null;

    public function Create()
    {
        parent::Create();

        //Each accessory is allowed to register properties for persistent data
        $this->registry->registerProperties();

        $this->RegisterPropertyBoolean('ShowExpertDevices', false);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Sanity checks for IDs and verify that connect is active
        $this->SetStatus($this->registry->getStatus());

        $objectIDs = $this->registry->getObjectIDs();

        if (method_exists($this, 'GetReferenceList')) {
            $refs = $this->GetReferenceList();
            foreach ($refs as $ref) {
                $this->UnregisterReference($ref);
            }

            foreach ($objectIDs as $id) {
                // Skip 0 = nothing selected
                if ($id === 0) {
                    continue;
                }
                $this->RegisterReference($id);
            }
        }
    }

    public function GetConfigurationForm()
    {
        return json_encode([
            'elements'     => $this->registry->getConfigurationForm(),
            'translations' => $this->registry->getTranslations(),
            'status'       => [
                [
                    'code'    => 102,
                    'icon'    => 'active',
                    'caption' => 'Symcon Connect is OK!'
                ],
                [
                    'code'    => 104,
                    'icon'    => 'inactive',
                    'caption' => 'Symcon Connect is not active!'
                ],
                [
                    'code'    => 200,
                    'icon'    => 'error',
                    'caption' => 'The IDs of the devices seem to be broken. Either some devices have the same ID or IDs are not numeric.'
                ]
            ]
        ]);
    }

    public function UIUpdateExpertVisibility(bool $ShowExpertDevices)
    {
        foreach ($this->registry->getExpertPanelNames() as $panelName) {
            $this->UpdateFormField($panelName, 'visible', $ShowExpertDevices);
        }
    }

    public function UIUpdateNextID(array $ListValues)
    {
        $this->registry->updateNextID($ListValues,
            function ($Field, $Parameter, $Value)
            {
                $this->UpdateFormField($Field, $Parameter, $Value);
            }
        );
    }

    public function UIRepairIDs(array $ListValues)
    {
        $this->registry->repairIDs($ListValues,
            function ($Field, $Parameter, $Value)
            {
                $this->UpdateFormField($Field, $Parameter, $Value);
            }
        );
        echo $this->Translate('IDs updated. Apply changes to save the fixed IDs.');
    }

    public function UIStartDeviceSearch(array $ListValues)
    {
        $this->registry->searchDevices($ListValues,
            function ($Field, $Parameter, $Value)
            {
                $this->UpdateFormField($Field, $Parameter, $Value);
            }
        );
    }

    public function UIAddSearchedDevices(array $CurrentDevices, array $NewDevices)
    {
        $this->registry->addDevices($CurrentDevices, $NewDevices,
            function ($Field, $Parameter, $Value)
            {
                $this->UpdateFormField($Field, $Parameter, $Value);
            }
        );
    }
}

trait CommonConnectVoiceAssistant
{
    use CommonVoiceAssistant
    {
        ApplyChanges as private CommonApplyChanges;
        GetConfigurationForm as private CommonGetConfigurationForm;
    }

    public function ApplyChanges()
    {
        $this->CommonApplyChanges();

        $connectID = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}')[0];
        $this->RegisterMessage($connectID, IM_CHANGESTATUS);
    }

    public function GetConfigurationForm()
    {
        $configurationForm = json_decode($this->CommonGetConfigurationForm(), true);

        $newStatuses = [
            [
                'code'    => 102,
                'icon'    => 'active',
                'caption' => 'Symcon Connect is OK!'
            ],
            [
                'code'    => 104,
                'icon'    => 'inactive',
                'caption' => 'Symcon Connect is not active!'
            ],
            [
                'code'    => 200,
                'icon'    => 'error',
                'caption' => 'The IDs of the devices seem to be broken. Either some devices have the same ID or IDs are not numeric.'
            ]
        ];

        foreach ($newStatuses as $newStatus) {
            $statusFound = false;
            for ($i = 0; $i < count($configurationForm['status']); $i++) {
                if ($configurationForm['status'][$i]['code'] === $newStatus['code']) {
                    $statusFound = true;
                    $configurationForm['status'][$i] = $newStatus;
                    break;
                }
            }
            if (!$statusFound) {
                $configurationForm['status'][] = $newStatus;
            }
        }

        return json_encode($configurationForm);
    }

    public function MessageSink($Timestamp, $SenderID, $MessageID, $Data)
    {
        // Update status if the status of the Connect Control changes
        $this->SetStatus($this->registry->getStatus());
    }
}