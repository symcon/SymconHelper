<?php

declare(strict_types=1);

trait HelperDimDevice
{
    private static function getDimCompatibility($variableID, $requireAction = true)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */ && $targetVariable['VariableType'] != 2 /* Float */) {
            return 'Int/Float required';
        }

        $checkLegacy = function () use ($targetVariable)
        {
            $profileName = '';
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
        };

        if (!function_exists('IPS_GetVariablePresentation')) {
            $result = $checkLegacy();
            if (!empty($result)) {
                return $result;
            }
        } else {
            $presentation = IPS_GetVariablePresentation($variableID);

            switch ($presentation['PRESENTATION'] ?? 'Invalid presentation') {
                case VARIABLE_PRESENTATION_SLIDER:
                case VARIABLE_PRESENTATION_VALUE_PRESENTATION:
                    if (($presentation['MAX'] - $presentation['MIN']) <= 0) {
                        return 'Presentation not dimmable';
                    }
                    break;

                case VARIABLE_PRESENTATION_LEGACY:
                    $result = $checkLegacy();
                    if (!empty($result)) {
                        return $result;
                    }
                    break;

                default:
                    return 'Unsupported presentation';
            }
        }

        if ($targetVariable['VariableCustomAction'] != 0) {
            $profileAction = $targetVariable['VariableCustomAction'];
        } else {
            $profileAction = $targetVariable['VariableAction'];
        }

        if ($requireAction && !($profileAction > 10000)) {
            return 'Action required';
        }

        return 'OK';
    }

    private static function getDimValue($variableID, $overrides = [])
    {
        $targetVariable = IPS_GetVariable($variableID);

        // Handling for versions prior to presentations being supported
        if (!function_exists('IPS_GetVariablePresentation')) {
            if ($targetVariable['VariableCustomProfile'] != '') {
                $profileName = $targetVariable['VariableCustomProfile'];
            } else {
                $profileName = $targetVariable['VariableProfile'];
            }
            if (!IPS_VariableProfileExists($profileName)) {
                return 0;
            }

            $profile = IPS_GetVariableProfile($profileName);

            $reversed = preg_match('/\.Reversed$/', $profileName);
            $minValue = $profile['MinValue'];
            $maxValue = $profile['MaxValue'];

            if (($maxValue - $minValue) <= 0) {
                return 0;
            }

            $value = ((GetValue($variableID) - $minValue) / ($maxValue - $minValue)) * 100;

            // Revert value for reversed profile
            if ($reversed) {
                $value = 100 - $value;
            }

            return $value;
        }

        $presentation = IPS_GetVariablePresentation($variableID);
        if (empty($presentation)) {
            return 0;
        }

        $minValue = 0;
        $maxValue = 100;
        $reversed = false;

        switch ($presentation['PRESENTATION']) {
            case VARIABLE_PRESENTATION_LEGACY:
                $profileName = $presentation['PROFILE'];
                if (!IPS_VariableProfileExists($profileName)) {
                    return 0;
                }

                $profile = IPS_GetVariableProfile($profileName);

                $reversed = preg_match('/\.Reversed$/', $profileName);
                $minValue = $profile['MinValue'];
                $maxValue = $profile['MaxValue'];
                break;

            case VARIABLE_PRESENTATION_SLIDER:
            case VARIABLE_PRESENTATION_VALUE_PRESENTATION:
                $reversed = false;
                $minValue = $presentation['MIN'];
                $maxValue = $presentation['MAX'];
                break;

            default:
                return false;
        }

        $maxValue = $overrides['MAX'] ?? $maxValue;
        $minValue = $overrides['MIN'] ?? $minValue;
        $reversed = $overrides['REVERSED'] ?? $reversed;

        if (($maxValue - $minValue) <= 0) {
            return 0;
        }

        $value = round(((GetValue($variableID) - $minValue) / ($maxValue - $minValue)) * 100, 5);

        // Revert value for reversed profile
        if ($reversed) {
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

        if (!HasAction($variableID)) {
            return false;
        }

        // percentToAbsolute already verifies that the variable exists
        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] === VARIABLETYPE_INTEGER) {
            $absoluteValue = intval($absoluteValue);
        }
        else {
            $absoluteValue = floatval($absoluteValue);
        }

        return RequestActionEx($variableID, $absoluteValue, 'VoiceControl');
    }

    private static function percentToAbsolute($variableID, $value, $overrides = [])
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        $minValue = 0;
        $maxValue = 100;
        $reversed = false;

        $legacyCheck = function ($profileName) use (&$minValue, &$maxValue, &$reversed)
        {
            if (!IPS_VariableProfileExists($profileName)) {
                return false;
            }

            // Revert value for reversed profile
            $reversed = preg_match('/\.Reversed$/', $profileName);

            $profile = IPS_GetVariableProfile($profileName);

            $minValue = $profile['MinValue'];
            $maxValue = $profile['MaxValue'];
        };

        if (!function_exists('IPS_GetVariablePresentation')) {
            $profileName = '';
            if ($targetVariable['VariableCustomProfile'] != '') {
                $profileName = $targetVariable['VariableCustomProfile'];
            } else {
                $profileName = $targetVariable['VariableProfile'];
            }
            $result = $legacyCheck($profileName);
            if ($result === false) {
                return false;
            }
        } else {
            $presentation = IPS_GetVariablePresentation($variableID);
            if (empty($presentation)) {
                return false;
            }

            switch ($presentation['PRESENTATION']) {
                case VARIABLE_PRESENTATION_LEGACY:
                    $result = $legacyCheck($presentation['PROFILE']);
                    if ($result === false) {
                        return false;
                    }
                    break;

                case VARIABLE_PRESENTATION_SLIDER:
                case VARIABLE_PRESENTATION_VALUE_PRESENTATION:
                    $minValue = $presentation['MIN'];
                    $maxValue = $presentation['MAX'];
                    break;

                default:
                    return false;

            }
        }

        $maxValue = $overrides['MAX'] ?? $maxValue;
        $minValue = $overrides['MIN'] ?? $minValue;
        $reversed = $overrides['REVERSED'] ?? $reversed;

        if ($reversed) {
            $value = 100 - $value;
        }

        if (($maxValue - $minValue) <= 0) {
            return false;
        }

        return round((max(0, min($value, 100)) / 100) * ($maxValue - $minValue) + $minValue, 5);
    }
}
