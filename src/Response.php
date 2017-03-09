<?php
declare (strict_types=1);

namespace Snelling;

use League\Plates\Engine as View;

class Response
{

    private $view;

    private $body;

    private $headers;

    private $responseCode;

    /**
     * Response constructor.
     * @param string $viewsDirectory
     */
    public function __construct(string $viewsDirectory = '/')
    {
        $this->view    = new View($viewsDirectory);
        $this->headers = [];

        $this->setResponseCode(200);
        $this->setHeader('Access-Control-Allow-Origin', '*');
        $this->setHeader('Date', date('D, d M Y H:i:s \G\M\T'));
        $this->setHeader('Server', '');
        $this->setHeader('X-Powered-By', 'none');
    }

    public function csv(string $filename, array $headings = [], array $data = []): bool
    {
        $this->setHeader('Pragma', 'public');
        $this->setHeader('Expires', '0');
        $this->setHeader('Cache-Control', 'private');
        $this->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $this->setHeader('Content-Type', 'application/octet-stream');
        $this->setHeader('Content-Disposition', 'filename="' . $filename . '";');
        $this->setHeader('Content-Transfer-Encoding', 'binary');
        $this->outputHeaders();

        $file = fopen('php://output', 'wb');

        ob_start();
        fputcsv($file, $headings);

        if (!empty($data)) {
            foreach ($data as $item) {
                if (isset($item['id'])) {
                    unset($item['id']);
                }
                fputcsv($file, $item);
            }
        }

        echo ob_get_clean();

        return true;
    }

    /**
     * @param string $data
     * @return bool
     */
    public function html(string $data): bool
    {
        $this->setHeader('X-UA-Compatible', 'IE=Edge,chrome=1');
        $this->setHeader('X-XSS-Protection', '1; mode=block');
        $this->setHeader('X-Content-Type-Options', 'nosniff');
        $this->setHeader('Content-Type', 'text/html; charset=utf-8');
        $this->setBody($data);

        return $this->output();
    }

    /**
     * @param array $data
     * @return bool
     */
    public function json(array $data = []): bool
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->setHeader('Cache-Control', 'public, max-age=60');
        $this->setBody($json);

        return $this->output();
    }

    /**
     * @param int    $responseCode
     * @param string $details
     * @return bool
     */
    public function jsonError(int $responseCode = 500, string $details = '')
    {
        $this->setResponseCode($responseCode);

        return $this->json(
            [
                'status'  => $responseCode,
                'object'  => 'error',
                'details' => $details,
            ]
        );
    }

    /**
     * @return bool
     */
    public function output(): bool
    {
        $this->setHeader('ETag', md5($this->body));
        $this->outputHeaders();
        $this->outputBody();

        return true;
    }

    /**
     * @return bool
     */
    public function outputBody(): bool
    {
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
            ob_start('ob_gzhandler');
            echo $this->body;
            ob_end_flush();

            return true;
        }

        ob_start();
        echo $this->body;
        ob_end_flush();

        return true;
    }

    /**
     * @return bool
     */
    public function outputHeaders(): bool
    {
        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }

        return true;
    }
    
    /**
     * @param string $http_host
     * @param string $request_uri
     * @return bool
     */
    public function upgradeSSL(string $http_host = '', string $request_uri = ''): bool
    {
        $this->setResponseCode(301);
        $this->redirect('https://' . $http_host . $request_uri);

        return $this->outputHeaders();
    }

    /**
     * @param string $location
     * @return bool
     */
    public function redirect(string $location = '/'): bool
    {
        $this->setHeader('Location', $location);

        return $this->outputHeaders();
    }

    /**
     * @param string $template
     * @param array  $parameters
     * @param string $format
     * @return bool
     */
    public function render(string $template, array $parameters = [], string $format = 'html'): bool
    {
        $this->view->addData($parameters);
        $view = $this->view->render($template);
        if ($format === 'html') {
            return $this->html($view);
        }
        if ($format === 'json') {
            return $this->json($view);
        }
        if ($format === 'xml') {
            return $this->xml($view);
        }

        return false;
    }

    /**
     * @param string $data
     * @return bool
     */
    public function setBody(string $data): bool
    {
        $this->body = $data;

        return true;
    }

    /**
     * @param string $key
     * @param        $value
     * @return bool
     */
    public function setHeader(string $key, $value): bool
    {
        $this->headers[$key] = $value;

        return true;
    }

    /**
     * @param int $responseCode
     * @return bool
     */
    public function setResponseCode(int $responseCode = 200): bool
    {
        $this->responseCode = $responseCode;

        return true;
    }

    /**
     * @param string $viewsDirectory
     * @return bool
     */
    public function setViewsDirectory(string $viewsDirectory): bool
    {
        $this->view = new View($viewsDirectory);

        return true;
    }

    /**
     * @param string $data
     * @return bool
     */
    public function xml(string $data): bool
    {
        $this->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->setBody($data);

        return $this->output();
    }

    /**
     * @param array $data
     * @return bool
     */
    public function addViewData(array $data): bool
    {
        $this->view->addData($data);

        return true;
    }
}
