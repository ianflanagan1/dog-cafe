<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Error;
use App\Exceptions\InputValidationException;
use App\Http\Attributes\Get;
use App\Http\Attributes\Post;
use App\Http\DTOs\ViewParameters;
use App\Http\FormRequests\SuggestRequest;
use App\Http\Response\Response;
use App\Repositories\SuggestRepository;
use App\Session\FormTokenHandler;
use App\Types\StandardTypes;

/**
 * @phpstan-import-type Inputs from StandardTypes
 * @phpstan-import-type SuggestValidated from SuggestRequest
 */
readonly class SuggestController extends Controller
{
    protected const string SUGGEST_ROUTE = '/suggest';

    #[Get(self::SUGGEST_ROUTE)]
    /**
     * Render a fresh Add Suggestion page.
     */
    public function suggestGet(FormTokenHandler $formTokenHandler): ViewParameters
    {
        return $this->render(
            $formTokenHandler,
            200,
        );
    }

    #[Post(self::SUGGEST_ROUTE)]
    /**
     * Handle a posted suggestion. On failure, render the Add Suggestion page with errors and prefilled input.
     * On success, redirect to the success page.
     */
    public function suggestPost(FormTokenHandler $formTokenHandler, SuggestRepository $suggestRepository): ViewParameters
    {
        if (!$formTokenHandler->isValidToken(self::SUGGEST_ROUTE, SuggestRequest::getFormToken('form_token'))) {
            Response::redirect(self::SUGGEST_ROUTE);
        }

        try {
            /** @var SuggestValidated $validated */
            $validated = SuggestRequest::validate();

        } catch (InputValidationException $e) {
            return $this->render(
                $formTokenHandler,
                422,
                $e->getInputs(),
                $e->getErrors()
            );
        }

        $suggestRepository->add($validated['suggest']);

        return new ViewParameters(
            'main',
            'suggestSuccess',
            'suggestSuccess',
            [
                'search'    => false,
                'canonical' => self::SUGGEST_ROUTE,
            ],
            [
                'canonical' => self::SUGGEST_ROUTE,
            ],
            [],
            201,
        );
    }

    /**
     * @param FormTokenHandler $formTokenHandler
     * @param int<1, max> $status
     * @param Inputs $inputs
     * @param list<Error> $errors
     * @return ViewParameters
     */
    protected function render(
        FormTokenHandler $formTokenHandler,
        int $status = 200,
        array $inputs = [],
        array $errors = [],
    ): ViewParameters {
        return new ViewParameters(
            'main',
            'suggest',
            'suggest',
            [
                'search'    => false,
                'canonical' => self::SUGGEST_ROUTE,
            ],
            [
                'canonical' => self::SUGGEST_ROUTE,
            ],
            [
                'formToken' => $formTokenHandler->createToken(self::SUGGEST_ROUTE),
                'prefilled' => $inputs,
                'errors'    => $errors,
            ],
            $status,
        );
    }
}
