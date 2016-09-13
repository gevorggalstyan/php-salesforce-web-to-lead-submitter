<?php

namespace GevorgGalstyan\SFW2LSubmitter;

use \GevorgGalstyan\SFW2LParser\Parser;

class Submitter
{
    public function __construct()
    {

    }

    private static function normalize_select_values($values, $acceptable_values, $is_multiple = FALSE)
    {
        if (!$is_multiple && gettype($values) == 'array') {
            $values = $values[0];
        }
        if (gettype($values) == 'string') {
            $other_value = '';
            $was_normalized = FALSE;
            foreach ($acceptable_values as $acceptable_value) {
                if (is_null($values) OR $values == '') {
                    $values = '';
                } elseif (strtolower($acceptable_value['text']) == strtolower($values)) {
                    $values = $acceptable_value['text'];
                    $was_normalized = TRUE;
                } elseif (strtolower($acceptable_value['text']) == 'other') {
                    $other_value = $acceptable_value['text'];
                }
            }
            if (!$was_normalized && $other_value != '') {
                $values = $other_value;
            }
            return $values;
        } elseif (gettype($values) == 'array') {
            if ($is_multiple) {
                $normalized_values = [];
                foreach ($values as $value) {
                    $other_value = '';
                    $was_normalized = FALSE;
                    if (!is_null($value) && $value != '') {
                        foreach ($acceptable_values as $acceptable_value) {
                            $fixed_acceptable_value_text = str_replace('&amp;', '&', $acceptable_value['text']);
                            if (strtolower($fixed_acceptable_value_text) == strtolower($value)) {
                                $normalized_values[] = $fixed_acceptable_value_text;
                                $was_normalized = TRUE;
                            } elseif (strtolower($acceptable_value['text']) == 'other') {
                                $other_value = $acceptable_value['text'];
                            }
                        }
                    }
                    if (!$was_normalized && $other_value != '') {
                        $normalized_values[] = $other_value;
                    }
                }
                $unique_array = array_unique($normalized_values);
                $array_string = implode('; ', $unique_array);
                return $array_string;
            }
        }
        return NULL;
    }

    private static function normalize_lead($sf_lead, $data_structure)
    {
        foreach ($sf_lead as $key => $value) {
            foreach ($data_structure['fields'] as $field) {
                if ($field['tag'] === 'select' && $key == $field['label']) {
                    $sf_lead[$key] = self::normalize_select_values($value, $field['options'][0], $field['multiple']);
                }
            }
        }
        return $sf_lead;
    }

    private static function minify_lead($lead_fields)
    {
        $minified_array = [];
        foreach ($lead_fields as $key => $lead_field) {
            if (gettype($lead_field) == 'array' && sizeof($lead_field) > 0) {
                $minified_array[$key] = $lead_field;
            }
            if (gettype($lead_field) != 'array' && !is_null($lead_field) && $lead_field !== '') {
                $minified_array[$key] = $lead_field;
            }
        }
        return $minified_array;
    }

    private static function decode_lead($lead_data, $data_structure)
    {
        $decoded_array = [];
        $decoded_array['oid'] = $data_structure['oid'];
        foreach ($lead_data as $key => $value) {
            foreach ($data_structure['fields'] as $k => $field) {
                if ($field['label'] == $key) {
                    $decoded_array[$k] = $value;
                }
            }
        }
        return $decoded_array;
    }

    public static function submit($lead, $web_to_load_html_file)
    {
        $data_structure = Parser::parse($web_to_load_html_file);
        $normalized_lead = self::normalize_lead($lead, $data_structure);
        $minified_lead = self::minify_lead($normalized_lead);
        $decoded_lead = self::decode_lead($minified_lead, $data_structure);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $data_structure['action']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,
                http_build_query($decoded_lead));
            curl_exec($ch);
            return TRUE;
        } catch (\Exception $ex) {
            return FALSE;
        }
    }
}