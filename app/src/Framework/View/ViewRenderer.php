<?php

declare(strict_types=1);

namespace App\Framework\View;

use App\Framework\Http\Response;
use App\Framework\Session\Auth;
use App\Framework\Support\StandardTypes;

/**
 * Renders a page's head and body templates inside the layout, into an HTML Response.
 *
 * Adds the layout parameters every page needs (`loggedIn`, `appUserPicture`) from the current request's auth state.
 *
 * @phpstan-import-type PosInt from StandardTypes
 */
final readonly class ViewRenderer
{
    private const string LAYOUT = 'main';

    public function __construct(private Auth $auth)
    {
    }

    /**
     * @param PosInt $status
     * @param non-empty-string $page Directory under `pages/` holding the page's `head.php` and `body.php`
     * @param array<string, mixed> $layoutParameters
     * @param array<string, mixed> $headParameters
     * @param array<string, mixed> $bodyParameters
     */
    public function render(
        int $status,
        string $page,
        array $layoutParameters,
        array $headParameters,
        array $bodyParameters,
    ): Response {
        return new Response(
            $status,
            ['content-type' => 'text/html; charset=utf-8'],
            $this->renderHtml($page, $layoutParameters, $headParameters, $bodyParameters),
        );
    }

    /**
     * @param PosInt $status
     */
    public function error(int $status): Response
    {
        $canonical = '/' . $status;

        return $this->render(
            status: $status,
            page: 'error',
            layoutParameters: [
                'search'    => false,
                'canonical' => $canonical,
            ],
            headParameters: [
                'canonical' => $canonical,
                'status'    => $status,
            ],
            bodyParameters: [
                'status' => $status,
            ],
        );
    }

    /**
     * @param non-empty-string $page
     * @param array<string, mixed> $layoutParameters
     * @param array<string, mixed> $headParameters
     * @param array<string, mixed> $bodyParameters
     */
    private function renderHtml(string $page, array $layoutParameters, array $headParameters, array $bodyParameters): string
    {
        $appUserId = $this->auth->id();

        $layoutParameters = [
            ...$layoutParameters,
            'loggedIn'       => $appUserId !== null,
            'appUserPicture' => $appUserId !== null
                ? $this->auth->picture()
                : null,
        ];

        $output = self::renderComponent(
            self::getAndCheckFilePath('layout', self::LAYOUT),
            $layoutParameters,
        );

        $output = self::insertComponent(
            $output,
            'head',
            self::getAndCheckFilePath('head', $page),
            $headParameters,
        );

        $output = self::insertComponent(
            $output,
            'body',
            self::getAndCheckFilePath('body', $page),
            $bodyParameters,
        );

        return $output;
    }

    /**
     * @param string $filePath
     * @param array<string, mixed> $parameters
     * @param string $type
     * @param string $output
     * @return string
     */
    private static function insertComponent(string $output, string $type, string $filePath, array $parameters): string
    {
        $tag = '{{' . $type . '}}';
        $pos = strpos($output, $tag);

        if ($pos === false) {
            return $output;
        }

        return substr_replace(
            $output,
            self::renderComponent($filePath, $parameters),
            $pos,
            strlen($tag),
        );
    }

    /**
     * @param string $filePath
     * @param array<string, mixed> $p Parameters to manage inserts into the HTML template
     * @return string
     */
    private static function renderComponent(string $filePath, array $p): string
    {
        ob_start();

        include $filePath;

        return (string) ob_get_clean();
    }

    private static function getAndCheckFilePath(string $type, string $name): string
    {
        // layouts/{name}.php, pages/{name}/{head|body}.php
        $filePath = $type === 'layout'
            ? VIEW_PATH . '/layouts/' . $name . '.php'
            : VIEW_PATH . '/pages/' . $name . '/' . $type . '.php';

        if (!file_exists($filePath)) {
            throw new ViewNotFoundException();
        }

        return $filePath;
    }
}
