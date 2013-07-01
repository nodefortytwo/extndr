<?php

class FormItemInstagramLocation extends FormItem {

    function render() {
    	return 'im an instagram location';
        if (!isset($this['text'])) {
            $this['text'] = 'Submit';
        }
        $html = '<div class="' . $this['class'] . '">';
        $html .= '<button type="submit" class="btn '.$this['style'].'">' . $this['text'] . '</button>';
        $html .= '</div>';
        return $html;
    }

}