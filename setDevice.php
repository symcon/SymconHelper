<?php

declare(strict_types=1);

trait HelperSetDevice
{
    private static function setDevice($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        if (!HasAction($variableID)) {
            return false;
        }

        return RequestActionEx($variableID, $value, 'VoiceControl');
    }
}
