<?php

namespace App\Http\Controllers;

use App\Models\Device;

abstract class Controller
{
    protected function currentDevice(): Device
    {
        /** @var Device $device */
        $device = request()->user();

        return $device;
    }
}
