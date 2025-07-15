<?php

declare(strict_types=1);

trait HelperGetNumberDevice
{
    private static function getGetNumberCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if (!in_array($targetVariable['VariableType'], [VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT])) {
            return 'Int/Float required';
        }

        return 'OK';
    }

    private static function getNumberValue($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);
        $value = GetValue($variableID);
        $legacyValue = function ($profileName) use ($value, $targetVariable)
        {
            if (($targetVariable['VariableType'] == 2 /* Float */) && ($profileName != '')) {
                $profile = IPS_GetVariableProfile($profileName);
                $value = round($value, $profile['Digits']);
            }

            return $value;
        };
        if (!function_exists('IPS_GetVariablePresentation')) {
            $profileName = '';
            if ($targetVariable['VariableCustomProfile'] != '') {
                $profileName = $targetVariable['VariableCustomProfile'] ?? '';
            } else {
                $profileName = $targetVariable['VariableProfile'] ?? '';
            }
            return $legacyValue($profileName);
        } else {
            $presentation = IPS_GetVariablePresentation($variableID);
            if (empty($presentation)) {
                return false;
            }
    
            switch ($presentation['PRESENTATION']) {
                case VARIABLE_PRESENTATION_LEGACY:
                    return $legacyValue($presentation['PROFILE']);
    
                    // No break. Add additional comment above this line if intentional
                case VARIABLE_PRESENTATION_SLIDER:
                    if (($targetVariable['VariableType'] == 2 /* Float */)) {
                        $value = round($value, $presentation['DIGITS']);
                    }
                    return $value;
    
                case VARIABLE_PRESENTATION_ENUMERATION:
                    return $value;
    
                default:
                    return false;
            }
        }
    }
}

trait HelperSetNumberDevice
{
    private static function getNumberCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if (!in_array($targetVariable['VariableType'], [VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT])) {
            return 'Int/Float required';
        }

        if (!HasAction($variableID)) {
            return 'Action required';
        }

        return 'OK';
    }

    private static function setNumberValue($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        if (!HasAction($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if (!in_array($targetVariable['VariableType'], [VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT])) {
            return false;
        }

        if (!(is_int($value) || is_float($value))) {
            return false;
        }

        return RequestActionEx($variableID, $value, 'VoiceControl');
    }
}

trait HelperNumberDevice
{
    use HelperSetNumberDevice;
    use HelperGetNumberDevice;
}
