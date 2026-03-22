<?php

namespace App\Controllers;

use App\Core\Controller;

class PageController extends Controller
{
    public function show()
    {
        $slug = $this->request->getParam('slug');
        $this->response->html('<h1>Page: ' . htmlspecialchars($slug) . '</h1>');
        $this->response->send();
    }
}
