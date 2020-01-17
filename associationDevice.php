<?php

declare(strict_types=1);

include_once __DIR__ . '/numberDevice.php';

trait HelperAssociationDevice
{
    use HelperNumberDevice;

    private static function getAssociationCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */ ) {
            return 'Int required';
        }

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        if (!IPS_VariableProfileExists($profileName)) {
            return 'Profile required';
        }

        $profile = IPS_GetVariableProfile($profileName);

        if (($profile['StepSize'] != 0) || (count($profile['Associations']) == 0)) {
            return 'No association profile';
        }

        // Initialize minimum and maximum one above/below legal maximum
        $minimumAssociation = count($profile['Associations']) + 1;
        $maximumAssociation = -1;
        foreach ($profile['Associations'] as $association) {
            if ($association['Value'] < 0) {
                return 'Negative associations not allowed';
            }

            if ($association['Value'] > $maximumAssociation) {
                $maximumAssociation = $association['Value'];
            }

            if ($association['Value'] < $minimumAssociation) {
                $minimumAssociation = $association['Value'];
            }
        }

        if (($maximumAssociation - $minimumAssociation + 1) != count($profile['Associations'])) {
            return 'Associations not enumerated';
        }

        if ($targetVariable['VariableCustomAction'] != '') {
            $profileAction = $targetVariable['VariableCustomAction'];
        } else {
            $profileAction = $targetVariable['VariableAction'];
        }

        if (!($profileAction > 10000)) {
            return 'Action required';
        }

        return 'OK';
    }

    private static function getAssociationNumber($variableID) {
        return self::getNumberValue($variableID);
    }

    private static function getAssociationString($variableID) {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        return GetValueFormatted($variableID);
    }

    private static function setAssociationNumber($variableID, $value) {
        return self::setNumberValue($variableID, $value);
    }

    private static function setAssociationString($variableID, $value) {

        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        if (!IPS_VariableProfileExists($profileName)) {
            return false;
        }

        $profile = IPS_GetVariableProfile($profileName);

        foreach ($profile['Associations'] as $association) {
            if (strcasecmp($association['Name'], $value)) {
                return self::setAssociationNumber($variableID, $association['Value']);
            }
        }

        // Fail, if no association was found
        return false;
    }

    private static function isValidAssociation($variableID, $value, $field) {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        if (!IPS_VariableProfileExists($profileName)) {
            return false;
        }

        $profile = IPS_GetVariableProfile($profileName);

        foreach ($profile['Associations'] as $association) {
            if (is_string($association[$field])) {
                if (strcasecmp($association[$field], $value)) {
                    return true;
                }
            }
            else {
                if ($association[$field] == $value) {
                    return true;
                }
            }
        }

        // Fail, if no association was found
        return false;
    }

    private static function isValidAssociationNumber($variableID, $value) {
        return self::isValidAssociation($variableID, $value, 'Value');
    }

    private static function isValidAssociationString($variableID, $value) {
        return self::isValidAssociation($variableID, $value, 'Name');
    }

    private static function incrementAssociation($variableID, $increment) {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        if (!IPS_VariableProfileExists($profileName)) {
            return false;
        }

        $profile = IPS_GetVariableProfile($profileName);
        $oneBased = true; // Is the first association a 1 or a 0?

        foreach ($profile['Associations'] as $association) {
            if ($association['Value'] == 0) {
                $oneBased = false;
                break;
            }
        }

        $currentValue = GetValue($variableID);

        // Convert one-based to zero-based
        if ($oneBased) {
            $currentValue--;
        }

        $newValue = ($currentValue + $increment) % count($profile['Associations']);

        if ($oneBased) {
            $newValue++;
        }

        return self::setAssociationNumber($variableID, $newValue);
    }
}
