<?php

declare(strict_types=1);

trait HelperGetStringDevice
{
    private static function getGetStringCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 3 /* String */) {
            return 'String required';
        }

        return 'OK';
    }

    private static function getStringValue($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        return GetValue($variableID);
    }
}

trait HelperSetStringDevice
{
    private static function getStringCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 3 /* String */) {
            return 'String required';
        }

        if ($targetVariable['VariableCustomAction'] !== 0) {
            $profileAction = $targetVariable['VariableCustomAction'];
        } else {
            $profileAction = $targetVariable['VariableAction'];
        }

        if (!($profileAction > 10000)) {
            return 'Action required';
        }

        return 'OK';
    }

    private static function setStringValue($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        if (!HasAction($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != VARIABLETYPE_STRING) {
            return false;
        }

        if (!is_string($value)) {
            return false;
        }

        return RequestActionEx($variableID, $value, 'VoiceControl');
    }
}

trait HelperStringDevice
{
    use HelperSetStringDevice;
    use HelperGetStringDevice;
}
