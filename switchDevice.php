<?php

declare(strict_types=1);

include_once __DIR__ . '/variablePresentation.php';

trait HelperSwitchDevice
{
    use HelperVariablePresentation;

    private static function getSwitchCompatibility($variableID, $requireAction = true)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != VARIABLETYPE_BOOLEAN) {
            return 'Boolean required';
        }

        if ($requireAction && !HasAction($variableID)) {
            return 'Action required';
        }

        return 'OK';
    }

    private static function getSwitchValue($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $value = GetValue($variableID);

        $presentation = self::resolvePresentation($variableID);

        // Revert value for reversed profile
        if (($presentation !== false) && $presentation['reversed']) {
            $value = !$value;
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

        if ($targetVariable['VariableType'] != VARIABLETYPE_BOOLEAN) {
            return false;
        }

        $presentation = self::resolvePresentation($variableID);

        // Revert value for reversed profile
        if (($presentation !== false) && $presentation['reversed']) {
            $value = !$value;
        }

        return RequestActionEx($variableID, $value, 'VoiceControl');
    }
}
