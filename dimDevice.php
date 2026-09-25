<?php

declare(strict_types=1);

include_once __DIR__ . '/variablePresentation.php';

trait HelperDimDevice
{
    use HelperVariablePresentation;

    private static function getDimCompatibility($variableID, $requireAction = true)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != VARIABLETYPE_INTEGER && $targetVariable['VariableType'] != VARIABLETYPE_FLOAT) {
            return 'Integer/Float required';
        }

        $presentation = self::resolvePresentation($variableID);
        if ($presentation === false) {
            return 'Presentation required';
        }

        switch ($presentation['kind']) {
            case 'legacy':
                if (!$presentation['profileExists']) {
                    return 'Profile required';
                }
                if (($presentation['max'] - $presentation['min']) <= 0) {
                    return 'Profile not dimmable';
                }
                break;

            case 'slider':
            case 'valuePresentation':
            case 'shutter':
                if (($presentation['max'] - $presentation['min']) <= 0) {
                    return 'Presentation not dimmable';
                }
                break;

            default:
                return 'Unsupported presentation';
        }

        if ($requireAction && !HasAction($variableID)) {
            return 'Action required';
        }

        return 'OK';
    }

    private static function getDimValue($variableID, $overrides = [])
    {
        $presentation = self::resolvePresentation($variableID);
        if ($presentation === false) {
            return 0;
        }

        switch ($presentation['kind']) {
            case 'legacy':
                if (!$presentation['profileExists']) {
                    return 0;
                }
                break;

            case 'slider':
            case 'valuePresentation':
            case 'shutter':
                break;

            default:
                //Return 0 like every other failure path, so callers can safely do arithmetic on the result
                return 0;
        }

        $maxValue = $overrides['MAX'] ?? $presentation['max'];
        $minValue = $overrides['MIN'] ?? $presentation['min'];
        $reversed = $overrides['REVERSED'] ?? $presentation['reversed'];

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

        return RequestActionEx($variableID, $absoluteValue, 'VoiceControl');
    }

    private static function percentToAbsolute($variableID, $value, $overrides = [])
    {
        $presentation = self::resolvePresentation($variableID);
        if ($presentation === false) {
            return false;
        }

        switch ($presentation['kind']) {
            case 'legacy':
                if (!$presentation['profileExists']) {
                    return false;
                }
                break;

            case 'slider':
            case 'valuePresentation':
            case 'shutter':
                break;

            default:
                return false;
        }

        $maxValue = $overrides['MAX'] ?? $presentation['max'];
        $minValue = $overrides['MIN'] ?? $presentation['min'];
        $reversed = $overrides['REVERSED'] ?? $presentation['reversed'];

        if ($reversed) {
            $value = 100 - $value;
        }

        if (($maxValue - $minValue) <= 0) {
            return false;
        }

        return round((max(0, min($value, 100)) / 100) * ($maxValue - $minValue) + $minValue, 5);
    }
}
