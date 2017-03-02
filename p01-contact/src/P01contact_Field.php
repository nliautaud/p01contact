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
     * Set field value
     *
     * Check if field is empty and required or
     * not empty but not valid.
     * @return string the error key, or empty
     */
    public function setValue($value)
    {
        // simple value
        if (!is_array($this->value)) {
            $field->value = $value;
            return;
        }
        // multiple-values
        // selections need to be an array
        $selections = $value;
        if (!is_array($selections)) {
            $selections = array($value);
        }
        // reset value selection
        foreach ($field->value as $key => $val) {
            $field->value[$key][2] = '';
        }
        // set value selection
        foreach ($selections as $selection) {
            foreach ($field->value as $key => $val) {
                if (trim($val[1]) == trim($selection)) {
                    $field->value[$key][2] = 'selected';
                }
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
        $value = $this->value;
        $disabled = $this->locked ? ' disabled="disabled"' : '';
        $required = $this->required ? ' required ' : '';

        $html  = '<div class="field ' . $type.$required. '">';
        if ($this->type != 'askcopy') {// not needed here, the value say everything
            $html .= $this->htmlLabel($id);
        }

        switch ($type) {
            case 'textarea':
                $html .= '<textarea id="' . $id . '" rows="10" ';
                $html .= 'name="' . $name . '"' . $disabled.$required;
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
                foreach ($this->value as $i => $v) {
                    $value = !empty($v[1]) ? ' ' . $v[1] : '';
                    $selected = !empty($v[2]) && $v[2] == 'selected' ? ' checked' : '';
                    $html .= '<input id="' . $id . '_option' . $i . '"';
                    $html .= ' type="checkbox" name="' . $name . '[' . $i . ']"';
                    $html .= ' value="' . $value . '"' . $disabled.$required.$selected;
                    $html .= ' />' . $value;
                }
                break;
            case 'select':
                $html .= '<select id="' . $id . '" name="' . $name . '"' . $disabled.$required . '>';
                foreach ($this->value as $i => $v) {
                    $value = !empty($v[1]) ? ' ' . $v[1] : ' Default';
                    $selected = !empty($v[2]) && $v[2] == 'selected' ? 'selected="selected"' : '';
                    $html .= '<option id="' . $id . '_option' . $i . '" value="' . $value;
                    $html .= '"' . $selected . ' >' . $value . '</option>';
                }
                $html.= '</select>';
                break;
            case 'radio':
                foreach ($this->value as $i => $v) {
                    $value = !empty($v[1]) ? ' ' . $v[1] : ' Default';
                    $selected = !empty($v[2]) && $v[2] == 'selected' ? ' checked' : '';
                    $html .= '<input id="' . $id . '_option' . $i . '" type="radio" ';
                    $html .= 'name="' . $name . '" value="' . $value . '"';
                    $html .= $disabled.$required.$selected . ' />' . $value;
                }
                break;
            default:
                $html .= '<input id="' . $id . '" ';
                $html .= 'name="' . $name . '" type="'.$type.'" ';
                $html .= 'value="' . $value . '"' . $disabled.$required . ' />';
                break;
        }
        $html .= '</div>';
        return $html;
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
