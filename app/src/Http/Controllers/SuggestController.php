<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Framework\Errors\Error;
use App\Framework\Http\Controller;
use App\Framework\Http\Response;
use App\Framework\Routing\Attributes\Get;
use App\Framework\Routing\Attributes\Post;
use App\Framework\Session\FormTokenHandler;
use App\Framework\Support\StandardTypes;
use App\Framework\Validation\InputValidationException;
use App\Framework\View\ViewRenderer;
use App\Http\FormRequests\SuggestRequest;
use App\Repositories\SuggestRepository;

/**
 * @phpstan-import-type Inputs from StandardTypes
 * @phpstan-import-type PosInt from StandardTypes
 * @phpstan-import-type SuggestValidated from SuggestRequest
 */
readonly class SuggestController extends Controller
{
    protected const string SUGGEST_ROUTE = '/suggest';

    #[Get(self::SUGGEST_ROUTE)]
    /**
     * Render a fresh Add Suggestion page.
     */
    public function suggestGet(ViewRenderer $viewRenderer, FormTokenHandler $formTokenHandler): Response
    {
        return $this->renderSuggestPage($viewRenderer, $formTokenHandler, 200, [], []);
    }

    #[Post(self::SUGGEST_ROUTE)]
    /**
     * Handle a posted suggestion. On failure, render the Add Suggestion page with errors and prefilled input.
     * On success, redirect to the success page.
     */
    public function suggestPost(
        SuggestRequest $request,
        ViewRenderer $viewRenderer,
        FormTokenHandler $formTokenHandler,
        SuggestRepository $suggestRepository,
    ): Response {
        if (!$formTokenHandler->isValidToken(self::SUGGEST_ROUTE, $request->formToken('form_token'))) {
            return Response::redirect(302, self::SUGGEST_ROUTE);
        }

        try {
            /** @var SuggestValidated $validated */
            $validated = $request->validate();

        } catch (InputValidationException $e) {
            return $this->renderSuggestPage($viewRenderer, $formTokenHandler, 422, $e->getInputs(), $e->getErrors());
        }

        $suggestRepository->add($validated['suggest']);

        return $viewRenderer->render(
            status: 201,
            page: 'suggestSuccess',
            layoutParameters: [
                'search'    => false,
                'canonical' => self::SUGGEST_ROUTE,
            ],
            headParameters: [
                'canonical' => self::SUGGEST_ROUTE,
            ],
            bodyParameters: [],
        );
    }

    /**
     * @param ViewRenderer $viewRenderer
     * @param FormTokenHandler $formTokenHandler
     * @param PosInt $status
     * @param Inputs $inputs
     * @param list<Error> $errors
     * @return Response
     */
    private function renderSuggestPage(
        ViewRenderer $viewRenderer,
        FormTokenHandler $formTokenHandler,
        int $status,
        array $inputs,
        array $errors,
    ): Response {
        return $viewRenderer->render(
            status: $status,
            page: 'suggest',
            layoutParameters: [
                'search'    => false,
                'canonical' => self::SUGGEST_ROUTE,
            ],
            headParameters: [
                'canonical' => self::SUGGEST_ROUTE,
            ],
            bodyParameters: [
                'formToken' => $formTokenHandler->createToken(self::SUGGEST_ROUTE),
                'prefilled' => $inputs,
                'errors'    => $errors,
            ],
        );
    }
}
