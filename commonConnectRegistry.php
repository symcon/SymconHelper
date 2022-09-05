<?php

declare(strict_types=1);

include_once __DIR__ . '/commonRegistry.php';

class CommonConnectRegistry extends CommonRegistry
{
    public function getTranslations(): array
    {
        $translations = parent::getTranslations();
        $translations['de']['Symcon Connect is not active!'] = 'Symcon Connect ist nicht aktiv!';
        $translations['de']['Symcon Connect is OK!'] = 'Symcon Connect ist OK!';

        return $translations;
    }

    public function getStatus()
    {
        $status = parent::getStatus();

        if ($status !== 102) {
            return $status;
        }

        //Check Connect availability
        $ids = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}');
        if (IPS_GetInstance($ids[0])['InstanceStatus'] != 102) {
            return 104;
        } else {
            return 102;
        }
    }
}
