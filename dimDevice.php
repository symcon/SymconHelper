<?php

declare(strict_types=1);

trait HelperDimDevice
{
    private static function getDimCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */ && $targetVariable['VariableType'] != 2 /* Float */) {
            return 'Int/Float required';
        }

        $presentation = IPS_GetVariablePresentation($variableID);
        if ($presentation['PRESENTATION'] == VARIABLE_PRESENTATION_SLIDER) {
            return 'OK';
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

        if (($profile['MaxValue'] - $profile['MinValue']) <= 0) {
            return 'Profile not dimmable';
        }

        if ($targetVariable['VariableCustomAction'] != 0) {
            $profileAction = $targetVariable['VariableCustomAction'];
        } else {
            $profileAction = $targetVariable['VariableAction'];
        }

        if (!($profileAction > 10000)) {
            return 'Action required';
        }

        return 'OK';
    }

    private static function getDimValue($variableID)
    {
        $targetVariable = IPS_GetVariable($variableID);

        $presentation = IPS_GetVariablePresentation($variableID);
        if (empty($presentation)) {
            return 0;
        }
        $profileName = '';
        switch ($presentation['PRESENTATION']) {
            case VARIABLE_PRESENTATION_LEGACY:
                $profileName = $presentation['PROFILE'];
                if (!IPS_VariableProfileExists($profileName)) {
                    return 0;
                }

                $profile = IPS_GetVariableProfile($profileName);

                $minValue = $profile['MinValue'];
                $maxValue = $profile['MaxValue'];
                break;

            case VARIABLE_PRESENTATION_SLIDER:
                $minValue = $presentation['MIN'];
                $maxValue = $presentation['MAX'];
                break;

            default:
                return false;

        }

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        if (($maxValue - $minValue) <= 0) {
            return 0;
        }

        $value = ((GetValue($variableID) - $minValue) / ($maxValue - $minValue)) * 100;

        // Revert value for reversed profile
        if (preg_match('/\.Reversed$/', $profileName)) {
            $value = 100 - $value;
        }

        return $value;
    }

    private static function dimDevice($variableID, $value)
    {
        $absoluteValue = self::percentToAbsolute($variableID, $value);

        if ($absoluteValue === false) {
            return false;
        }

        // percentToAbsolute already verifies that the variable exists
        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableCustomAction'] != 0) {
            $profileAction = $targetVariable['VariableCustomAction'];
        } else {
            $profileAction = $targetVariable['VariableAction'];
        }

        if ($profileAction < 10000) {
            return false;
        }

        if ($targetVariable['VariableType'] === VARIABLETYPE_INTEGER) {
            $absoluteValue = intval($absoluteValue);
        }
        else {
            $absoluteValue = floatval($absoluteValue);
        }

        if (IPS_InstanceExists($profileAction)) {
            IPS_RunScriptText('IPS_RequestAction(' . var_export($profileAction, true) . ', ' . var_export(IPS_GetObject($variableID)['ObjectIdent'], true) . ', ' . var_export($absoluteValue, true) . ');');
        } elseif (IPS_ScriptExists($profileAction)) {
            IPS_RunScriptEx($profileAction, ['VARIABLE' => $variableID, 'VALUE' => $absoluteValue, 'SENDER' => 'VoiceControl']);
        } else {
            return false;
        }

        return true;
    }

    private static function percentToAbsolute($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        $presentation = IPS_GetVariablePresentation($variableID);
        if (empty($presentation)) {
            return false;
        }

        switch ($presentation['PRESENTATION']) {
            case VARIABLE_PRESENTATION_LEGACY:
                $profileName = $presentation['PROFILE'];
                if (!IPS_VariableProfileExists($profileName)) {
                    return false;
                }

                // Revert value for reversed profile
                if (preg_match('/\.Reversed$/', $profileName)) {
                    $value = 100 - $value;
                }

                $profile = IPS_GetVariableProfile($profileName);

                $minValue = $profile['MinValue'];
                $maxValue = $profile['MaxValue'];
                break;

            case VARIABLE_PRESENTATION_SLIDER:
                $minValue = $presentation['MIN'];
                $maxValue = $presentation['MAX'];
                break;

            default:
                return false;

        }

        if (($maxValue - $minValue) <= 0) {
            return false;
        }

        return (max(0, min($value, 100)) / 100) * ($maxValue - $minValue) + $minValue;
    }
}
