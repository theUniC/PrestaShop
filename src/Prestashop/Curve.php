<?php

namespace Prestashop;

class Curve
{
    protected $values = array();
    protected $label;
    protected $type;
    /** @prototype void public function setValues($values) */
    public function setValues($values)
    {
        $this->values = $values;
    }
    public function getValues($time_mode = false)
    {
        ksort($this->values);
        $string = '';
        foreach ($this->values as $key => $value) {
            $string .= '[' . addslashes((string) $key) . ($time_mode ? '000' : '') . ',' . (double) $value . '],';
        }

        return '{data:[' . rtrim($string, ',') . ']' . (!empty($this->label) ? ',label:"' . $this->label . '"' : '') . '' . (!empty($this->type) ? ',' . $this->type : '') . '}';
    }
    /** @prototype void public function setPoint(float $x, float $y) */
    public function setPoint($x, $y)
    {
        $this->values[(string) $x] = (double) $y;
    }
    public function setLabel($label)
    {
        $this->label = $label;
    }
    public function setType($type)
    {
        $this->type = '';
        if ($type == 'bars') {
            $this->type = 'bars:{show:true,lineWidth:10}';
        }
        if ($type == 'steps') {
            $this->type = 'lines:{show:true,steps:true}';
        }
    }
    public function getPoint($x)
    {
        if (array_key_exists((string) $x, $this->values)) {
            return $this->values[(string) $x];
        }
    }
}
