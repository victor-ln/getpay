<?php

namespace App\Traits;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

trait ToastTrait
{
    public function updatedSuccess($message, $route, $params = [])
    {
        Session::flash('toast', [
            'type' => 'bg-success',
            'title' => 'Updated',
            'message' => $message,
        ]);

        return redirect()->route($route, $params);
    }

    public function updatedErr($message, $route, $params = [])
    {
        Session::flash('toast', [
            'type' => 'bg-danger',
            'title' => 'Updated',
            'message' => $message,
        ]);

        return redirect()->route($route, $params);
    }
}
