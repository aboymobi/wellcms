<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
 */

function form_radio_yes_no($name, $checked = 0)
{
    $checked = intval($checked);
    return form_radio($name, array(1 => lang('yes'), 0 => lang('no')), $checked);
}

function form_radio($name, $arr, $checked = 0, $disabled = FALSE)
{
    empty($arr) && $arr = array(lang('no'), lang('yes'));
    $s = '';

    foreach ((array)$arr as $k => $v) {
        $add = $k == $checked ? ' checked="checked"' : '';
        $add .= $disabled == TRUE ? ' disabled' : '';
        $s .= "<label class=\"custom-input custom-radio\"><input type=\"radio\" name=\"$name\" value=\"$k\"$add /> $v</label> &nbsp; \r\n";
    }
    return $s;
}

function form_checkbox($name, $checked = 0, $txt = '', $val = 1)
{
    $add = $checked ? ' checked="checked"' : '';
    $s = "<label class=\"custom-input custom-checkbox mr-2\"><input type=\"checkbox\" name=\"$name\" value=\"$val\" $add /> $txt</label>";
    return $s;
}

// form_multi_checkbox('flag', array('k1'=>'v1','k2'=>'v2'), array('v1','v2'))
// name  选项内容  被选中选项(选项内容的键名)
function form_multi_checkbox($name, $arr, $checked = array())
{
    $s = '';
    foreach ($arr as $k => $v) {
        $ischecked = in_array($k, $checked) ? ' checked="checked"' : '';
        $_name = $name . '[' . $k . ']';
        $s .= "<label class=\"custom-input custom-checkbox pr-2 mr-1\"><input type=\"checkbox\" name=\"$_name\" value=\"$k\" $ischecked /> $v </label> ";
    }
    return $s;
}

function form_select($name, $arr, $checked = 0, $id = TRUE, $disabled = FALSE)
{
    if (empty($arr)) return '';
    $idadd = $id === TRUE ? "id=\"$name\"" : ($id ? "id=\"$id\"" : '');
    $add = $disabled == TRUE ? ' disabled="disabled"' : '';
    $s = "<select name=\"$name\" class=\"custom-select w-auto\" $idadd $add> \r\n";
    $s .= form_options($arr, $checked);
    $s .= "</select> \r\n";
    return $s;
}

function form_options($arr, $checked = 0)
{
    $s = '';
    foreach ((array)$arr as $k => $v) {
        $add = $k == $checked ? ' selected="selected"' : '';
        $s .= "<option value=\"$k\"$add>$v</option> \r\n";
    }
    return $s;
}

function form_text($name, $value, $width = FALSE, $holdplacer = '')
{
    $style = '';
    if ($width !== FALSE) {
        is_numeric($width) AND $width .= 'px';
        $style = " style=\"width: $width\"";
    }
    $s = "<input type=\"text\" name=\"$name\" id=\"$name\" placeholder=\"$holdplacer\" value=\"$value\" class=\"form-control\"$style />";
    return $s;
}

function form_hidden($name, $value)
{
    $s = "<input type=\"hidden\" name=\"$name\" id=\"$name\" value=\"$value\" />";
    return $s;
}

function form_textarea($name, $value, $holdplacer = '', $width = FALSE, $height = FALSE)
{
    $style = '';
    if ($width !== FALSE) {
        is_numeric($width) AND $width .= 'px';
        is_numeric($height) AND $height .= 'px';
        $style = " style=\"width: $width; height: $height; \"";
    }
    $s = "<textarea name=\"$name\" id=\"$name\" placeholder=\"$holdplacer\" class=\"form-control\" $style>$value</textarea>";
    return $s;
}

function form_password($name, $value, $width = FALSE)
{
    $style = '';
    if ($width !== FALSE) {
        is_numeric($width) AND $width .= 'px';
        $style = " style=\"width: $width\"";
    }
    $s = "<input type=\"password\" name=\"$name\" id=\"$name\" class=\"form-control\" value=\"$value\" $style />";
    return $s;
}

// form_time('start', '18:00') 为空则当前时间
function form_time($name, $value = 0, $width = FALSE)
{
    $style = '';
    if ($width !== FALSE) {
        is_numeric($width) AND $width .= 'px';
        $style = " style=\"width: $width\"";
    }
    $value = $value ? $value : date('H:i');
    $s = "<input type=\"time\" name=\"$name\" id=\"$name\" class=\"form-control\" value=\"$value\" $style />";
    return $s;
}

// form_date('start', '2018-07-05') 为空则当前日期
function form_date($name, $value = 0, $width = FALSE)
{
    $style = '';
    if ($width !== FALSE) {
        is_numeric($width) AND $width .= 'px';
        $style = " style=\"width: $width\"";
    }
    $value = $value ? $value : date('Y-m-d');
    $s = "<input type=\"date\" name=\"$name\" id=\"$name\" class=\"form-control\" value=\"$value\" $style />";
    return $s;
}

/**用法
 *
 * echo form_radio_yes_no('radio1', 0);
 * echo form_checkbox('aaa', array('无', '有'), 0);
 *
 * echo form_radio_yes_no('aaa', 0);
 * echo form_radio('aaa', array('无', '有'), 0);
 * echo form_radio('aaa', array('a'=>'aaa', 'b'=>'bbb', 'c'=>'ccc', ), 'b');
 *
 * echo form_select('aaa', array('a'=>'aaa', 'b'=>'bbb', 'c'=>'ccc', ), 'a');
 */

?>