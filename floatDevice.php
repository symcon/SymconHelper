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

        $presentation = IPS_GetVariablePresentation($variableID);

        $value = GetValue($variableID);
        switch ($presentation['PRESENTATION'] ?? 'No presentation') {
            case VARIABLE_PRESENTATION_LEGACY:
                $profileName = $presentation['PROFILE'];
                if ($profileName != '') {
                    $profile = IPS_GetVariableProfile($profileName);

                    $value = round($value, $profile['Digits']);
                }
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
