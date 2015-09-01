<?php


class tms__base_model {

    /**
     * @return array
     */
    public function getFieldNames(){
        return array_keys($this->DATA);
    }
}