<?php


class tms__base_model {

    /**
     * @return array
     */
    public function getFieldNames(){
        return array_keys($this->DATA);
    }

    /**
     * @param text $name
     * @return bool
     */
    public function isFieldExist($name=null){
        return isset($this->DATA[$name]);
    }
}