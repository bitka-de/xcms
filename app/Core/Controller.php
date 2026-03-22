<?php

namespace App\Core;

abstract class Controller
{
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewEngine = new View();
        $content = $viewEngine->render($view, $data);

        if ($layout) {
            $layoutContent = $viewEngine->render('layouts/' . $layout, [
                'content' => $content,
                ...$data
            ]);
            $this->response->html($layoutContent);
        } else {
            $this->response->html($content);
        }

        $this->response->send();
    }

    protected function redirect(string $url): void
    {
        $this->response->redirect($url);
    }

    protected function json(array $data, int $status = 200): void
    {
        $this->response->json($data, $status);
        $this->response->send();
    }

    protected function notFound(): void
    {
        $this->response->setStatus(404);
        $this->render('pages/404', [], 'main');
    }
}
