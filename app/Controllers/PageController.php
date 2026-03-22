<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\PageRenderer;

class PageController extends Controller
{
    public function show(): void
    {
        $slug = trim((string) $this->request->getParam('slug', ''), '/');

        if ($slug === '') {
            $this->response->setStatus(404);
            $this->render('pages/404', [
                'pageTitle' => 'Page Not Found',
                'seoDescription' => 'The requested page could not be found.',
                'globalCss' => '',
                'pageCss' => '',
                'pageJs' => '',
            ], 'main');
            return;
        }

        $renderer = new PageRenderer();
        $payload = $renderer->renderPublicBySlug($slug);

        if ($payload === null) {
            $this->response->setStatus(404);
            $this->render('pages/404', [
                'pageTitle' => 'Page Not Found',
                'seoDescription' => 'The requested page could not be found.',
                'globalCss' => '',
                'pageCss' => '',
                'pageJs' => '',
            ], 'main');
            return;
        }

        $this->render('pages/show', [
            'page' => $payload['page'],
            'contentHtml' => $payload['content_html'],
            'pageTitle' => $payload['meta']['title'],
            'seoDescription' => $payload['meta']['description'],
            'globalCss' => $payload['design_css_variables'],
            'pageCss' => $payload['css'],
            'pageJs' => $payload['js'],
        ], 'main');
    }
}
