<?php

declare(strict_types=1);

trait HelperGetFloatDevice
{
    private static function getGetFloatCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 2 /* Float */) {
            return 'Float required';
        }

        return 'OK';
    }

    private static function getFloatValue($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        $legacyValue = function ($profileName) use (&$value)
        {
            if ($profileName != '') {
                $profile = IPS_GetVariableProfile($profileName);

                $value = round($value, $profile['Digits']);
            }
        };

        $value = GetValue($variableID);
        if (!function_exists('IPS_GetVariablePresentation')) {
            $profileName = '';
            if ($targetVariable['VariableCustomProfile'] != '') {
                $profileName = $targetVariable['VariableCustomProfile'];
            } else {
                $profileName = $targetVariable['VariableProfile'];
            }
            $legacyValue($profileName);
        } else {
            $presentation = IPS_GetVariablePresentation($variableID);
    
            switch ($presentation['PRESENTATION'] ?? 'No presentation') {
                case VARIABLE_PRESENTATION_LEGACY:
                    $legacyValue($presentation['PROFILE']);
                    break;
    
                case VARIABLE_PRESENTATION_SLIDER:
                    $value = round($value, $presentation['DIGITS']);
                    break;
    
                default:
                    break;
    
            }
            return $value;
        }
    }
}

trait HelperSetFloatDevice
{
    private static function getFloatCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != VARIABLETYPE_FLOAT) {
            return 'Float required';
        }

        if (!HasAction($variableID)) {
            return 'Action required';
        }

        return 'OK';
    }

    private static function setFloatValue($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if (!HasAction($variableID)) {
            return false;
        }

        if ($targetVariable['VariableType'] != VARIABLETYPE_FLOAT) {
            return false;
        }

        if (!(is_int($value) || is_float($value))) {
            return false;
        }

        return RequestActionEx($variableID, $value, 'VoiceControl');
    }
}

trait HelperFloatDevice
{
    use HelperSetFloatDevice;
    use HelperGetFloatDevice;
}
