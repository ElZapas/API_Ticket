<?php

namespace utils;

class SendAnswer
{
    static final function JSON($data = null)
    {
        header("Content-type:application/json");
        echo json_encode($data);
        exit;
    }
    static final function empty(){
        exit;
    }
}
