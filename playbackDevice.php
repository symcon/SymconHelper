<?php

declare(strict_types=1);
define('PREVIOUS', 0);
define('STOP', 1);
define('PLAY', 2);
define('PAUSE', 3);
define('NEXT', 4);

trait HelperPlaybackDevice
{
    private static function getPlaybackCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */) {
            return 'Integer required';
        }

        if ($targetVariable['VariableCustomAction'] !== 0) {
            $profileAction = $targetVariable['VariableCustomAction'];
        } else {
            $profileAction = $targetVariable['VariableAction'];
        }

        if (!($profileAction > 10000)) {
            return 'Action required';
        }

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        switch ($profileName) {
            case '~Playback':
            case '~PlaybackPreviousNext':
                break;

            default:
                return '~Playback profile required';
        }

        return 'OK';
    }

    private static function activateCommand($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        if (!HasAction($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */) {
            return false;
        }

        return RequestActionEx($variableID, $value, 'VoiceControl');
    }

    private static function activatePrevious($variableID)
    {
        return self::activateCommand($variableID, PREVIOUS);
    }

    private static function activatePlay($variableID)
    {
        return self::activateCommand($variableID, PLAY);
    }

    private static function activatePause($variableID)
    {
        return self::activateCommand($variableID, PAUSE);
    }

    private static function activateStop($variableID)
    {
        return self::activateCommand($variableID, STOP);
    }

    private static function activateNext($variableID)
    {
        return self::activateCommand($variableID, NEXT);
    }

    private static function supportsPreviousNext($variableID)
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

        return $profileName === '~PlaybackPreviousNext';
    }
}
