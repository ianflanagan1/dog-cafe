<?php

declare(strict_types=1);

namespace App\Http;

use App\Exceptions\ViewNotFoundException;
use App\Http\DTOs\ViewParameters;
use App\Session\Auth;
use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 */
class View
{
    /**
     * @param Auth $auth
     * @param ViewParameters $viewParameters
     */
    public function __construct(
        protected Auth $auth,
        protected ViewParameters $viewParameters,
    ) {
        // Check if logged in
        $appUserId = $this->auth->id();

        if ($appUserId === null) {
            $this->viewParameters->layoutParameters['loggedIn'] = false;
            $this->viewParameters->layoutParameters['appUserPicture'] = null;
        } else {
            $this->viewParameters->layoutParameters['loggedIn'] = true;
            $this->viewParameters->layoutParameters['appUserPicture'] = $this->auth->picture();
        }
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function render(): string
    {
        http_response_code($this->viewParameters->status);
        header('Content-Type: text/html; charset=utf-8', true);

        $output = self::renderComponent(
            self::getAndCheckFilePath('layout', $this->viewParameters->layout),
            $this->viewParameters->layoutParameters
        );

        $output = self::insertComponent(
            $output,
            'head',
            self::getAndCheckFilePath('head', $this->viewParameters->head),
            $this->viewParameters->headParameters,
        );

        $output = self::insertComponent(
            $output,
            'body',
            self::getAndCheckFilePath('body', $this->viewParameters->body),
            $this->viewParameters->bodyParameters,
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
    protected static function insertComponent(string $output, string $type, string $filePath, array $parameters): string
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
    protected static function renderComponent(string $filePath, array $p): string
    {
        ob_start();

        include $filePath;

        return (string) ob_get_clean();
    }

    protected static function getAndCheckFilePath(string $type, string $name): string
    {
        $filePath = VIEW_PATH . '/' . $type . '/' . $name . '.php';

        if (!file_exists($filePath)) {
            throw new ViewNotFoundException();
        }

        return $filePath;
    }
}
