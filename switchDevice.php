<?php

declare(strict_types=1);

trait HelperSwitchDevice
{
    private static function getSwitchCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != VARIABLETYPE_BOOLEAN) {
            return 'Bool required';
        }

        if (!HasAction($variableID)) {
            return 'Action required';
        }

        return 'OK';
    }

    private static function getSwitchValue($variableID)
    {
        $targetVariable = IPS_GetVariable($variableID);

        $value = GetValue($variableID);
        $presentation = IPS_GetVariablePresentation($variableID);
        if ($presentation['PRESENTATION'] === VARIABLE_PRESENTATION_LEGACY) {
            // Revert value for reversed profile
            if (preg_match('/\.Reversed$/', $presentation['PROFILE'])) {
                $value = !$value;
            }
        }
        return $value;
    }

    private static function switchDevice($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        if (!HasAction($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] == VARIABLETYPE_BOOLEAN) {
            $value = boolval($value);
        } else {
            return false;
        }

        $presentation = IPS_GetVariablePresentation($variableID);
        if (($presentation['PRESENTATION'] ?? 'No presentation') === VARIABLE_PRESENTATION_LEGACY) {
            // Revert value for reversed profile
            if (preg_match('/\.Reversed$/', $presentation['PROFILE'])) {
                $value = !$value;
            }
        }
        return RequestActionEx($variableID, $value, 'VoiceControl');
    }
}
