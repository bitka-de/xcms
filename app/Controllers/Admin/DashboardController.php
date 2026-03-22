<?php

namespace App\Controllers\Admin;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $this->response->html('<h1>Admin Dashboard</h1>');
        $this->response->send();
    }
}
