<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $this->response->html('<h1>xcms Home</h1><p>HTTP infrastructure is working.</p>');
        $this->response->send();
    }
}
