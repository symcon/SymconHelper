<?php

declare(strict_types=1);

trait HelperColorDevice
{
    public static function cmykToHex($c, $m, $y, $k)
    {
        $c = max(0, min(100, $c));
        $m = max(0, min(100, $m));
        $y = max(0, min(100, $y));
        $k = max(0, min(100, $k));

        $r = 255 * (1 - $c / 100) * (1 - $k / 100);
        $g = 255 * (1 - $m / 100) * (1 - $k / 100);
        $b = 255 * (1 - $y / 100) * (1 - $k / 100);

        $r = round($r);
        $g = round($g);
        $b = round($b);

        return self::rgbToHex($r, $g, $b);
    }

    public static function hslToRGB($h, $s, $l)
    {
        $h /= 360; // Normalize hue to 0-1

        if ($s == 0) {
            $r = $g = $b = $l; // Achromatic (gray)
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = self::hueToRgb($p, $q, $h + 1 / 3);
            $g = self::hueToRgb($p, $q, $h);
            $b = self::hueToRgb($p, $q, $h - 1 / 3);
        }
        return self::rgbToHex(
            round($r * 255),
            round($g * 255),
            round($b * 255),
        );
    }
    private static function rgbToHex($r, $g, $b)
    {
        return ($r << 16) + ($g << 8) + $b;
    }

    private static function getColorBrightness($variableID)
    {
        return self::getColorBrightnessByValue(self::getColorValue($variableID));
    }

    private static function getColorBrightnessByValue($rgbValue)
    {
        if (($rgbValue < 0) || ($rgbValue > 0xFFFFFF)) {
            return 0;
        }

        $red = intval($rgbValue >> 16);
        $green = intval(($rgbValue % 0x10000) >> 8);
        $blue = intval($rgbValue % 0x100);

        $maxColor = max($red, $green, $blue);
        return (floatval($maxColor) / 255.0) * 100;
    }

    private static function computeColorBrightness($variableID, $brightness)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        $computeLegacy = function () use (&$rgbValue, $variableID, $targetVariable)
        {
            if ($targetVariable['VariableType'] != VARIABLETYPE_INTEGER) {
                return false;
            }

            $rgbValue = GetValueInteger($variableID);
        };

        if (!function_exists('IPS_GetVariablePresentation')) {
            $success = $computeLegacy();
            if ($success === false) {
                return false;
            }
        } else {
            $presentation = IPS_GetVariablePresentation($variableID);
    
            if (empty($presentation)) {
                return false;
            }
    
            switch ($presentation['PRESENTATION']) {
                case VARIABLE_PRESENTATION_LEGACY:
                    $success = $computeLegacy();
                    if ($success === false) {
                        return false;
                    }
                    break;
    
                case VARIABLE_PRESENTATION_COLOR:
                    if ($targetVariable['VariableType'] == VARIABLETYPE_INTEGER) {
                        $rgbValue = GetValueInteger($variableID);
                        break;
                    } elseif ($targetVariable['VariableType'] == VARIABLETYPE_STRING) {
                        $rgbValue = self::encodedStringToRGB(GetValueString($variableID), $presentation['ENCODING']);
                        break;
                    } else {
                        return false;
                    }
            }
        }


        if (($rgbValue < 0) || ($rgbValue > 0xFFFFFF)) {
            return false;
        }

        $brightness = min(100.0, $brightness);
        $brightness = max(0.0, $brightness);

        $red = intval($rgbValue >> 16);
        $green = intval(($rgbValue % 0x10000) >> 8);
        $blue = intval($rgbValue % 0x100);

        $previousBrightness = self::getColorBrightness($variableID);

        $newRed = 0;
        $newGreen = 0;
        $newBlue = 0;

        if ($previousBrightness != 0) {
            $newRed = intval($red * ($brightness / $previousBrightness));
            $newGreen = intval($green * ($brightness / $previousBrightness));
            $newBlue = intval($blue * ($brightness / $previousBrightness));
        }
        // If the color was black before (which is the only possibility for its brightness = 0), just dim white
        else {
            $newRed = intval(0xff * ($brightness / 100));
            $newGreen = $newRed;
            $newBlue = $newRed;
        }

        return self::rgbToHex($newRed, $newGreen, $newBlue);
    }

    private static function setColorBrightness($variableID, $brightness)
    {
        return self::colorDevice($variableID, self::computeColorBrightness($variableID, $brightness));
    }

    private static function getColorCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        if (!HasAction($variableID)) {
            return 'Action required';
        }

        $targetVariable = IPS_GetVariable($variableID);
        $variableType = $targetVariable['VariableType'];

        if (!function_exists('IPS_GetVariablePresentation')) {
            if ($variableType != VARIABLETYPE_INTEGER) {
                return 'Integer required';
            }
            $profileName = '';
            if ($targetVariable['VariableCustomProfile'] != '') {
                $profileName = $targetVariable['VariableCustomProfile'];
            } else {
                $profileName = $targetVariable['VariableProfile'];
            }
            if ($profileName != '~HexColor') {
                return '~HexColor profile required';
            }
        } else {
            $presentation = IPS_GetVariablePresentation($variableID);

            if (empty($presentation)) {
                return 'Presentation required';
            }

            switch ($presentation['PRESENTATION']) {
                case VARIABLE_PRESENTATION_LEGACY:
                    if ($variableType != VARIABLETYPE_INTEGER) {
                        return 'Integer required';
                    }
                    if ($presentation['PROFILE'] != '~HexColor') {
                        return '~HexColor profile required';
                    }
                    break;

                case VARIABLE_PRESENTATION_COLOR:
                    if (!in_array($variableType, [VARIABLETYPE_INTEGER, VARIABLETYPE_STRING])) {
                        return 'Integer or String required';
                    }
                    break;

                default:
                    return 'Presentation Legacy or Color required';

            }
        }

        return 'OK';
    }

    private static function getColorValue($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 0;
        }

        $targetVariable = IPS_GetVariable($variableID);

        $legacyValue = function () use ($targetVariable, $variableID)
        {
            if ($targetVariable['VariableType'] != VARIABLETYPE_INTEGER) {
                return 0;
            }

            $value = GetValueInteger($variableID);

            if (($value < 0) || ($value > 0xFFFFFF)) {
                return 0;
            }

            return $value;
        };
        if (!function_exists('IPS_GetVariablePresentation')) {
            return $legacyValue();
        } else {
            $presentation = IPS_GetVariablePresentation($variableID);
            if (empty($presentation)) {
                return 0;
            }

            switch ($presentation['PRESENTATION']) {
                case VARIABLE_PRESENTATION_LEGACY:

                case VARIABLE_PRESENTATION_COLOR:
                    if ($targetVariable['VariableType'] == VARIABLETYPE_INTEGER) {
                        $value = GetValueInteger($variableID);

                        if (($value < 0) || ($value > 0xFFFFFF)) {
                            return 0;
                        }

                        return $value;
                    } elseif ($targetVariable['VariableType'] == VARIABLETYPE_STRING) {
                        return self::encodedStringToRGB(GetValueString($variableID), $presentation['ENCODING']);
                    } else {
                        return 0;
                    }

                    // No break. Add additional comment above this line if intentional
                default:
                    return 0;
            }
        }
    }

    private static function colorDevice($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        if (!HasAction($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if (!function_exists('IPS_GetVariablePresentation')) {
            if ($targetVariable['VariableType'] != VARIABLETYPE_INTEGER) {
                return false;
            }

            if (($value < 0) || ($value > 0xFFFFFF)) {
                return false;
            }
        } else {
            $presentation = IPS_GetVariablePresentation($variableID);
            if (empty($presentation)) {
                return false;
            }
            switch ($presentation['PRESENTATION']) {
                case VARIABLE_PRESENTATION_LEGACY:
                    if ($targetVariable['VariableType'] != VARIABLETYPE_INTEGER) {
                        return false;
                    }

                    if (($value < 0) || ($value > 0xFFFFFF)) {
                        return false;
                    }
                    break;

                case VARIABLE_PRESENTATION_COLOR:
                    if ($targetVariable['VariableType'] == VARIABLETYPE_INTEGER) {
                        if (($value < 0) || ($value > 0xFFFFFF)) {
                            return false;
                        }
                        break;
                    }
                    if ($targetVariable['VariableType'] == VARIABLETYPE_STRING) {
                        $red = ($value >> 16) & 0xFF;
                        $green = ($value >> 8) & 0xFF;
                        $blue = $value & 0xFF;
                        $value = self::rgbToJson($red, $green, $blue, $presentation['ENCODING']);
                    } else {
                        return false;
                    }
                    break;

                default:
                    return false;
            }
        }

        return RequestActionEx($variableID, $value, 'VoiceControl');
    }

    private static function rgbToHSB($rgbValue)
    {
        $red = intval($rgbValue >> 16);
        $green = intval(($rgbValue % 0x10000) >> 8);
        $blue = intval($rgbValue % 0x100);

        // Conversion algorithm from http://www.docjar.com/html/api/java/awt/Color.java.html
        $cMax = max($red, $green, $blue);
        $cMin = min($red, $green, $blue);

        $brightness = floatval($cMax) / 255.0;

        $saturation = 0;
        if ($cMax != 0) {
            $saturation = floatval($cMax - $cMin) / floatval($cMax);
        }

        $hue = 0;
        if ($saturation != 0) {
            $redC = floatval($cMax - $red) / floatval($cMax - $cMin);
            $greenC = floatval($cMax - $green) / floatval($cMax - $cMin);
            $blueC = floatval($cMax - $blue) / floatval($cMax - $cMin);

            switch ($cMax) {
                case $red:
                    $hue = $blueC - $greenC;
                    break;

                case $green:
                    $hue = 2 + $redC - $blueC;
                    break;

                case $blue:
                    $hue = 4 + $greenC - $redC;
                    break;
            }

            $hue /= 6;

            if ($hue < 0) {
                $hue += 1;
            }
        }
        return [
            'hue'        => $hue * 360,
            'saturation' => $saturation,
            'brightness' => $brightness
        ];
    }

    private static function hsbToRGB($hue, $saturation, $brightness)
    {
        $prepareValue = function ($value)
        {
            return intval($value * 255 + 0.5);
        };

        // Conversion algorithm from http://www.docjar.com/html/api/java/awt/Color.java.html
        if ($saturation == 0.0) {
            $colorValue = $prepareValue($brightness);
            return self::rgbToHex($colorValue, $colorValue, $colorValue);
        } else {
            $huePercentage = $hue / 360;
            $h = ($huePercentage - floor($huePercentage)) * 6;
            $f = $h - floor($h);
            $p = $brightness * (1 - $saturation);
            $q = $brightness * (1 - ($saturation * $f));
            $t = $brightness * (1 - ($saturation * (1 - $f)));
            switch (intval($h)) {
                case 0:
                    return self::rgbToHex(
                        $prepareValue($brightness),
                        $prepareValue($t),
                        $prepareValue($p)
                    );

                case 1:
                    return self::rgbToHex(
                        $prepareValue($q),
                        $prepareValue($brightness),
                        $prepareValue($p)
                    );

                case 2:
                    return self::rgbToHex(
                        $prepareValue($p),
                        $prepareValue($brightness),
                        $prepareValue($t)
                    );

                case 3:
                    return self::rgbToHex(
                        $prepareValue($p),
                        $prepareValue($q),
                        $prepareValue($brightness)
                    );

                case 4:
                    return self::rgbToHex(
                        $prepareValue($t),
                        $prepareValue($p),
                        $prepareValue($brightness)
                    );

                case 5:
                    return self::rgbToHex(
                        $prepareValue($brightness),
                        $prepareValue($p),
                        $prepareValue($q)
                    );
            }
        }
        return 0;
    }

    private static function hueToRgb($p, $q, $t)
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
        return $p;
    }

    private static function rgbToJson($r, $g, $b, $encoding)
    {
        switch ($encoding) {
            case 0: // RGB
                return json_encode(['r' => $r, 'g' => $g, 'b' => $b]);
                break;
            case 1: //CMYK
                // Normalize RGB values to 0-1 range
                $r = $r / 255;
                $g = $g / 255;
                $b = $b / 255;

                // Calculate K (black)
                $k = 1 - max($r, $g, $b);

                // If K is 1, then C, M, and Y are 0.  Handle this edge case.
                if ($k == 1) {
                    return json_encode(['c' => 0, 'm' => 0, 'y' => 0, 'k' => 100]);
                }

                // Calculate C, M, and Y
                $c = (1 - $r - $k) / (1 - $k);
                $m = (1 - $g - $k) / (1 - $k);
                $y = (1 - $b - $k) / (1 - $k);

                $c = round($c * 100);
                $m = round($m * 100);
                $y = round($y * 100);
                $k = round($k * 100);

                return json_encode(['c' => $c, 'm' => $m, 'y' => $y, 'k' => $k]);

            case 2: // HSV = HSB
                $hsb = self::rgbToHSB(self::rgbToHex($r, $g, $b));
                return json_encode(['h' => round($hsb['hue']), 's' => round($hsb['saturation'] * 100), 'v' => round($hsb['brightness'] * 100)]);
            case 3: // HSL
                // 1. Normalize RGB values to the range 0-1
                $r /= 255;
                $g /= 255;
                $b /= 255;

                // 2. Find min and max values
                $max = max($r, $g, $b);
                $min = min($r, $g, $b);
                $chroma = $max - $min;

                // 3. Calculate Lightness (L)
                $l = ($max + $min) / 2;

                // 4. Calculate Saturation (S)
                if ($chroma == 0) {
                    $s = 0; // Achromatic (gray) - no saturation
                } else {
                    $s = $l < 0.5 ? $chroma / ($max + $min) : $chroma / (2 - $max - $min);
                }

                // 5. Calculate Hue (H)
                if ($chroma == 0) {
                    $h = 0; // Achromatic (gray) - hue is undefined, typically set to 0
                } else {
                    switch ($max) {
                        case $r:
                            $h = ($g - $b) / $chroma;
                            if ($h < 0) {  // Make sure h is always positive.  Crucial!
                                $h += 6;
                            }
                            break;
                        case $g:
                            $h = ($b - $r) / $chroma + 2;
                            break;
                        case $b:
                            $h = ($r - $g) / $chroma + 4;
                            break;
                    }
                    $h *= 60; // Convert to degrees (0-360)
                }

                return json_encode([
                    'h' => round($h),
                    's' => round($s * 100),
                    'l' => round($l * 100)
                ]);

            default:
                throw new Exception("Unknown color encoding $encoding");
        }
    }

    private static function encodedStringToRGB($value, $encoding)
    {
        $decodedValue = json_decode($value, true);
        switch ($encoding) {
            case 0: // RGB
                return ($decodedValue['r'] << 16) + ($decodedValue['g'] << 8) + $decodedValue['b'];

            case 1: // CMYK
                return self::cmykToHex($decodedValue['c'], $decodedValue['m'], $decodedValue['y'], $decodedValue['k']);

            case 2: // HSV = HSB
                // We want to reuse existing code
                $rgb = self::hsbToRGB($decodedValue['h'], $decodedValue['s'], $decodedValue['v']);
                throw new Exception($decodedValue, $rgb);

            case 3: // HSL
                return self::hslToRGB($decodedValue['h'], $decodedValue['s'], $decodedValue['l']);

            default:
                throw new Exception("Unknown encoding: $encoding");
                break;
        }
    }
}
