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

        if (!in_array($targetVariable['VariableType'], [1, 2] /* Int, Float */)) {
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

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        $value = GetValue($variableID);

        if (($targetVariable['VariableType'] == 2 /* Float */) && ($profileName != '')) {
            $profile = IPS_GetVariableProfile($profileName);

            $value = round($value, $profile['Digits']);
        }

        return $value;
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

        if (!in_array($targetVariable['VariableType'], [1, 2] /* Int, Float */)) {
            return 'Int/Float required';
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

    private static function setNumberValue($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableCustomAction'] != 0) {
            $profileAction = $targetVariable['VariableCustomAction'];
        } else {
            $profileAction = $targetVariable['VariableAction'];
        }

        if ($profileAction < 10000) {
            return false;
        }

        if (!in_array($targetVariable['VariableType'], [1, 2] /* Int, Float */)) {
            return false;
        }

        if (!(is_int($value) || is_float($value))) {
            return false;
        }

        if (IPS_InstanceExists($profileAction)) {
            IPS_RunScriptText('IPS_RequestAction(' . var_export($profileAction, true) . ', ' . var_export(IPS_GetObject($variableID)['ObjectIdent'], true) . ', ' . var_export($value, true) . ');');
        } elseif (IPS_ScriptExists($profileAction)) {
            IPS_RunScriptEx($profileAction, ['VARIABLE' => $variableID, 'VALUE' => $value, 'SENDER' => 'VoiceControl']);
        } else {
            return false;
        }

        return true;
    }
}

trait HelperNumberDevice
{
    use HelperSetNumberDevice;
    use HelperGetNumberDevice;
}
