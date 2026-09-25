<?php

declare(strict_types=1);

include_once __DIR__ . '/variablePresentation.php';

trait HelperGetNumberDevice
{
    use HelperVariablePresentation;

    private static function getGetNumberCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if (!in_array($targetVariable['VariableType'], [VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT])) {
            return 'Integer/Float required';
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

        $presentation = self::resolvePresentation($variableID);
        if (($presentation !== false) && ($targetVariable['VariableType'] == VARIABLETYPE_FLOAT) && ($presentation['digits'] !== null)) {
            $value = round($value, $presentation['digits']);
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

        if (!in_array($targetVariable['VariableType'], [VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT])) {
            return 'Integer/Float required';
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

        return RequestActionEx($variableID, $value, 'VoiceControl');
    }
}

trait HelperNumberDevice
{
    use HelperSetNumberDevice;
    use HelperGetNumberDevice;
}
