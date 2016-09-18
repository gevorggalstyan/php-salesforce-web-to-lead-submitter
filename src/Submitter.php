<?php

namespace GevorgGalstyan\SFW2LSubmitter;

use \GevorgGalstyan\SFW2LParser\Parser;

class Submitter
{
    public function __construct()
    {

    }

    private static function parse($w2l_file)
    {
        return Parser::parse($w2l_file);
    }

    private static function normalize_field($value, $field)
    {
        if ($field['tag'] == 'select') {
            if (gettype($value) == 'array' && !$field['multiple']) {
                $value = $value[0];
            }

            if (gettype($value) != 'array') {
                $other_value = '';
                $was_normalized = FALSE;
                foreach ($field['options'][0] as $normal_value) {
                    if (is_null($value) OR $value == '') {
                        $value = '';
                    } elseif (strtolower($normal_value['text']) ==
                        strtolower($value)
                    ) {
                        $value = $normal_value['text'];
                        $was_normalized = TRUE;
                    } elseif (strtolower($normal_value['text']) == 'other') {
                        $other_value = $normal_value['text'];
                    }
                }
                if (!$was_normalized && $other_value != '') {
                    $value = $other_value;
                }
                return $value;
            } else {
                $normalized_values = [];
                foreach ($value as $v) {
                    $other_value = '';
                    $was_normalized = FALSE;
                    if (!is_null($v) && $v != '') {
                        foreach ($field['options'][0] as $normal_value) {
                            $normal_text = str_replace(
                                '&amp;',
                                '&',
                                $normal_value['text']
                            );
                            if (strtolower($normal_text) == strtolower($v)) {
                                $normalized_values[] = $normal_text;
                                $was_normalized = TRUE;
                            } elseif (strtolower($normal_value['text']) ==
                                'other'
                            ) {
                                $other_value = $normal_value['text'];
                            }
                        }
                    }
                    if (!$was_normalized && $other_value != '') {
                        $normalized_values[] = $other_value;
                    }
                }
                return (implode('; ', array_unique($normalized_values)));
            }
        } else {
            return $value;
        }
    }

    private static function normalize($data, $structure)
    {
        foreach ($data as $key => $value) {
            foreach ($structure['fields'] as $field) {
                if ($key == $field['label']) {
                    $data[$key] = self::normalize_field($value, $field);
                }
            }
        }
        return $data;
    }

    private static function clean($data)
    {
        $clean_data = [];
        foreach ($data as $key => $field) {
            if (gettype($field) == 'array' && sizeof($field) > 0) {
                $clean_data[$key] = $field;
            } elseif (gettype($field) != 'array' && !is_null($field) &&
                $field !== ''
            ) {
                $clean_data[$key] = $field;
            }
        }
        return $clean_data;
    }

    private static function decode($data, $structure)
    {
        $decoded = [];
        $decoded['oid'] = $structure['oid'];
        foreach ($data as $key => $value) {
            foreach ($structure['fields'] as $k => $field) {
                if ($field['label'] == $key) {
                    $decoded[$k] = $value;
                }
            }
        }
        return $decoded;
    }

    public static function submit($data, $w2l_file)
    {
        $structure = self::parse($w2l_file);
        $lead = self::decode(
            self::clean(self::normalize($data, $structure)),
            $structure);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $structure['action']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($lead));
            curl_exec($ch);
            return TRUE;
        } catch (\Exception $ex) {
            return FALSE;
        }
    }
}