<?php

declare(strict_types=1);

trait HelperVariablePresentation
{
    //Normalizes the legacy profile and all variable presentations into one common structure:
    //kind: 'legacy', 'slider', 'valuePresentation', 'shutter', 'color', 'enumeration' or 'unsupported'
    //Returns false if the variable is missing or has no presentation at all
    private static function resolvePresentation($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $legacyPresentation = function ($profileName)
        {
            $presentation = [
                'kind'          => 'legacy',
                'profile'       => $profileName,
                'profileExists' => ($profileName != '') && IPS_VariableProfileExists($profileName),
                'min'           => null,
                'max'           => null,
                'digits'        => null,
                'stepSize'      => null,
                'reversed'      => (bool) preg_match('/\.Reversed$/', $profileName),
                'encoding'      => null,
                'options'       => null,
            ];

            if ($presentation['profileExists']) {
                $profile = IPS_GetVariableProfile($profileName);
                $presentation['min'] = $profile['MinValue'];
                $presentation['max'] = $profile['MaxValue'];
                $presentation['digits'] = $profile['Digits'];
                $presentation['stepSize'] = $profile['StepSize'];
                $presentation['options'] = $profile['Associations'];
            }

            return $presentation;
        };

        //Handling for versions prior to presentations being supported
        if (!function_exists('IPS_GetVariablePresentation')) {
            $targetVariable = IPS_GetVariable($variableID);
            if ($targetVariable['VariableCustomProfile'] != '') {
                return $legacyPresentation($targetVariable['VariableCustomProfile']);
            }
            return $legacyPresentation($targetVariable['VariableProfile']);
        }

        $presentation = IPS_GetVariablePresentation($variableID);
        if (empty($presentation)) {
            return false;
        }

        $normalized = [
            'kind'          => 'unsupported',
            'profile'       => '',
            'profileExists' => false,
            'min'           => null,
            'max'           => null,
            'digits'        => null,
            'stepSize'      => null,
            'reversed'      => false,
            'encoding'      => null,
            'options'       => null,
        ];

        switch ($presentation['PRESENTATION']) {
            case VARIABLE_PRESENTATION_LEGACY:
                return $legacyPresentation($presentation['PROFILE']);

            case VARIABLE_PRESENTATION_SLIDER:
                $normalized['kind'] = 'slider';
                $normalized['min'] = $presentation['MIN'];
                $normalized['max'] = $presentation['MAX'];
                $normalized['digits'] = $presentation['DIGITS'] ?? null;
                return $normalized;

            case VARIABLE_PRESENTATION_VALUE_PRESENTATION:
                $normalized['kind'] = 'valuePresentation';
                $normalized['min'] = $presentation['MIN'];
                $normalized['max'] = $presentation['MAX'];
                $normalized['digits'] = $presentation['DIGITS'] ?? null;
                return $normalized;

            case VARIABLE_PRESENTATION_SHUTTER:
                $normalized['kind'] = 'shutter';
                $normalized['min'] = $presentation['OPEN_OUTSIDE_VALUE'];
                $normalized['max'] = $presentation['CLOSE_INSIDE_VALUE'];
                //A closed value below the open value is simply reversed
                if ($normalized['min'] > $normalized['max']) {
                    $normalized['reversed'] = true;
                    $k = $normalized['min'];
                    $normalized['min'] = $normalized['max'];
                    $normalized['max'] = $k;
                }
                return $normalized;

            case VARIABLE_PRESENTATION_COLOR:
                $normalized['kind'] = 'color';
                $normalized['encoding'] = $presentation['ENCODING'] ?? 0;
                return $normalized;

            case VARIABLE_PRESENTATION_ENUMERATION:
                $normalized['kind'] = 'enumeration';
                $normalized['options'] = json_decode($presentation['OPTIONS'], true);
                return $normalized;

            default:
                return $normalized;
        }
    }
}
