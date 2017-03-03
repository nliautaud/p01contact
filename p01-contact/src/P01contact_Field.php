<?php
/**
 * p01-contact - A simple contact forms manager
 *
 * @link https://github.com/nliautaud/p01contact
 * @author Nicolas Liautaud
 * @package p01-contact
 */
namespace P01C;

class P01contactField
{
    private $form;

    public $id;
    public $type;
    public $title;
    public $description;
    public $value;
    public $selected_values;
    public $placeholder;
    public $required;
    public $locked;
    public $error;

    /**
     * @param Form $form the container form
     * @param int $id the field id
     * @param string $type the field type
     */
    public function __construct($form, $id, $type)
    {
        $this->form = $form;
        $this->id = $id;
        $this->type = $type;
    }

    /**
     * Set the field value or selected value
     *
     * @param mixed $new_value the value, or an array of selected values ids
     */
    public function setValue($new_value)
    {
        // simple value
        if (!is_array($this->value)) {
            $this->value = htmlentities($new_value, ENT_COMPAT, 'UTF-8', false);
            return;
        }
        // multiples-values (checkbox, radio, select)
        if (!is_array($new_value)) {
            $new_value = array($new_value);
        }
        foreach ($new_value as $i) {
            $this->selected_values[intval($i)] = true;
        }
    }

    /**
     * Reset the selected values by finding ones who starts or end with ":"
     */
    public function resetSelectedValues()
    {
        $this->selected_values = array();
        foreach ($this->value as $i => $val) {
            $value = preg_replace('`(^\s*:|:\s*$)`', '', $val, -1, $count);
            if ($count) {
                $this->value[$i] = $value;
                $this->selected_values[$i] = true;
            }
        }
    }

    /**
     * Check field value.
     */
    public function validate()
    {
        // empty and required
        if (empty($this->value) && $this->required) {
            $this->error = 'field_required';
            return;
        }
        // value blacklisted or not in whitelist
        if ($reason = $this->isBlacklisted()) {
            $this->error = 'field_' . $reason;
        }
        // not empty but not valid
        if (!empty($this->value) && !$this->isValid()) {
            $this->error = 'field_' . $this->type;
            return;
        }
    }

    /**
     * Check if field value is valid
     * Mean different things depending on field type
     * @return boolean
     */
    public function isValid()
    {
        switch ($this->type) {
            case 'email':
                return filter_var($this->value, FILTER_VALIDATE_EMAIL);
            case 'tel':
                $pattern = '`^\+?[-0-9(). ]{6,}$$`i';
                return preg_match($pattern, $this->value);
            case 'url':
                return filter_var($this->value, FILTER_VALIDATE_URL);
            case 'message':
                return strlen($this->value) > $this->form->config('message_len');
            case 'captcha':
                return $this->reCaptchaValidity($_POST['g-recaptcha-response']);
            case 'password':
                return $this->value == $this->required;
            default:
                return true;
        }
    }

    /**
     * Check if reCaptcha is valid
     * @return boolean
     */
    public function reCaptchaValidity($answer)
    {
        if (!$answer) {
            return false;
        }
        $params = [
            'secret'    => $this->form->config('recaptcha_secret_key'),
            'response'  => $answer
        ];
        $url = "https://www.google.com/recaptcha/api/siteverify?" . http_build_query($params);
        if (function_exists('curl_version')) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
        } else {
            $response = file_get_contents($url);
        }

        if (empty($response) || is_null($response)) {
            return false;
        }

        return json_decode($response)->success;
    }

    /**
     * Check if field value is blacklisted
     *
     * Search for every comma-separated entry of every checklist
     * in value, and define if it should or should not be there.
     *
     * @return boolean
     */
    public function isBlacklisted()
    {
        $list = $this->form->config('checklist');
        foreach ($list as $i => $cl) {
            if ($cl->name != $this->type) {
                continue;
            }
            $content = array_filter(explode(',', $cl->content));
            foreach ($content as $avoid) {
                $found = preg_match("`$avoid`", $this->value);
                $foundBlacklisted = $found && $cl->type == 'blacklist';
                $notFoundWhitelisted = !$found && $cl->type == 'whitelist';
                if ($foundBlacklisted || $notFoundWhitelisted) {
                    return $cl->type;
                }
            }
        }
        return false;
    }

    /*
     * Return the html display of the field
     *
     * Manage field title, error message, and type-based display
     * @return string the <div>
     */
    public function html()
    {
        $id  = 'p01-contact' . $this->form->getId() . '_field' . $this->id;
        $name = 'p01-contact_fields[' . $this->id . ']';
        $type = $this->getGeneralType();
        $orig = $type != $this->type ? $this->type : '';
        $value = $this->value;
        $disabled = $this->locked ? ' disabled="disabled"' : '';
        $required = $this->required ? ' required ' : '';
        $placeholder = $this->placeholder ? ' placeholder="'.$this->placeholder.'"' : '';

        $is_single_option = is_array($this->value) && count($this->value) == 1;
        if ($is_single_option) {
            $html  = "<div class=\"field inline $type $orig $required\">";
        } else {
            $html  = "<div class=\"field $type $orig $required\">";
            $html .= $this->htmlLabel($id);
        }

        switch ($type) {
            case 'textarea':
                $html .= '<textarea id="' . $id . '" rows="10" ';
                $html .= 'name="' . $name . '"' . $disabled.$required.$placeholder;
                $html .= '>' . $value . '</textarea>';
                break;
            case 'captcha':
                $key = $this->form->config('recaptcha_public_key');
                if (!$key) {
                    break;
                }
                if ($this->form->getId() == 1) {
                    $html .= '<script src="https://www.google.com/recaptcha/api.js"></script>';
                }
                $html .='<div class="g-recaptcha" id="'.$id.'" data-sitekey="'.$key.'"></div>';
                break;
            case 'checkbox':
                $name = "{$name}[$i]";
                // post every checkboxes values
            case 'radio':
                $html .= '<div class="options">';
                foreach ($this->value as $i => $v) {
                    $selected = $this->isSelected($i) ? ' checked' : '';
                    $v = !empty($v) ? $v : 'Default';
                    $html .= '<label class="option">';
                    $html .= "<input id=\"{$id}_option{$i}\"";
                    $html .= " type=\"$type\" class=\"$type\" name=\"$name\"";
                    $html .= " value=\"$i\"$disabled$required$selected />$v";
                    $html .= '</label>';
                }
                $html .= '</div>';
                break;
            case 'select':
                $html .= "<select id=\"$id\" name=\"$name\"$disabled$required>";
                foreach ($this->value as $i => $v) {
                    $value = !empty($v) ? $v : 'Default';
                    $selected = $this->isSelected($i) ? ' selected="selected"' : '';
                    $html .= "<option id=\"{$id}_option{$i}\" value=\"$i\"$selected>";
                    $html .= $value . '</option>';
                }
                $html.= '</select>';
                break;
            default:
                $html .= '<input id="' . $id . '" ';
                $html .= 'name="' . $name . '" type="'.$type.'" ';
                $html .= 'value="' . $value . '"' . $disabled.$required.$placeholder . ' />';
                break;
        }
        $html .= '</div>';
        return $html;
    }
    /*
     * Return a html presentation of the field value.
     */
    public function htmlMail()
    {
        $gen_type = $this->getGeneralType();
        $properties = array();

        $html = '<table style="width: 100%; margin: 1em 0; border-collapse: collapse;">';

        // name
        $emphasis = $this->value ? 'font-weight:bold' : 'font-style:italic';
        $html .= "\n\n\n";
        $html .= '<tr style="background-color: #eeeeee">';
        $html .= '<td style="padding: .5em .75em"><span style="'.$emphasis.'">';
        $html .= $this->title ? $this->title : $this->type;
        $html .= '</span></td>';
        $html .= "\t\t";

        // properties
        $html .= '<td style="padding:.5em 1em; text-transform:lowercase; text-align:right; font-size:.875em; color:#888888; vertical-align: middle"><em>';
        if (!$this->value) {
            $html .= $this->form->lang('empty') . ' ';
        }
        if ($this->title) {
            $properties[] = $this->type;
        }
        if ($gen_type != $this->type) {
            $properties[] = $gen_type;
        }
        foreach (array('locked', 'required') as $property) {
            if ($this->$property) {
                $properties[] = $this->form->lang($property);
            }
        }
        if (count($properties)) {
            $html .= '(' . implode(', ', $properties) . ') ';
        }
        $html .= '#' . $this->id;
        $html .= '</em></td></tr>';
        $html .= "\n\n";

        // value
        if (!$this->value) {
            return $html . '</table>';
        }
        $html .= '<tr><td colspan=2 style="padding:0">';
        $html .= '<div style="padding:.5em 1.5em;border:1px solid #ccc">';
        switch ($gen_type) {
            case 'checkbox':
            case 'radio':
            case 'select':
                foreach ($this->value as $i => $v) {
                    if ($this->isSelected($i)) {
                        $html .= '<div>';
                        $checkmark = '&#9745;';
                    } else {
                        $html .= '<div style="color:#ccc; font-style:italic">';
                        $checkmark = '&#9744;';
                    }
                    $html .= '<span style="font-size:1.5em; vertical-align:middle; margin-right:.5em; font-style:normal">'.$checkmark.'</span>';
                    $html .= empty($v) ? 'Default' : $v;
                    $html .= "</div>\n";
                }
                break;
            default:
                $address = '~[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]~';
                $val = nl2br(preg_replace($address, '<a href="\\0">\\0</a>', $this->value));
                $html .= "<p style=\"margin:0\">$val</p>";
                break;
        }

        $html .= '</div></td></tr></table>';
        return $html;
    }

    private function isSelected($i)
    {
        return is_int($i) && is_array($this->selected_values) && isset($this->selected_values[$i]);
    }

    /*
     * Return the label of the field
     * @param string $for id of the target field
     * @return string the <div> (unclosed for captcha)
     */
    private function htmlLabel($for)
    {
        $html = '<label for="' . $for . '">';
        if ($this->title) {
            $html .= $this->title;
        } else {
            $html .= ucfirst($this->form->lang($this->type));
        }
        if ($this->description) {
            $html .= ' <em class="description">' . $this->description . '</em>';
        }
        if ($this->error) {
            $html .= ' <span class="error-msg">' . $this->form->lang($this->error) . '</span>';
        }
        $html .= '</label>';
        return $html;
    }

    /**
     * Return the general type of a field, even of specials fields.
     */
    private function getGeneralType()
    {
        $types = array(
            'name'    => 'text',
            'subject' => 'text',
            'message' => 'textarea',
            'askcopy' => 'checkbox'
        );
        if (isset($types[$this->type])) {
            return $types[$this->type];
        }
        return $this->type;
    }
}

function preint($arr, $return = false)
{
    $out = '<pre class="test" style="white-space:pre-wrap;">' . print_r(@$arr, true) . '</pre>';
    if ($return) {
        return $out;
    }
    echo $out;
}
function predump($arr)
{
    echo'<pre class="test" style="white-space:pre-wrap;">';
    var_dump($arr);
    echo'</pre>';
}
function unset_r($a, $i)
{
    foreach ($a as $k => $v) {
        if (isset($v[$i])) {
            unset($a[$k][$i]);
        }
    }
    return $a;
}
