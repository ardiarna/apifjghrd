<?php

namespace App\Exceptions;

use App\Traits\ApiResponser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{

    use ApiResponser;
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if($exception instanceof ModelNotFoundException) {
            $modelSpasi = preg_replace('/(?<!\b)(?=[A-Z])/', ' ', class_basename($exception->getModel()));
            $message = $modelSpasi.' dengan id '.implode(', ', $exception->getIds()).' tidak ditemukan.';
            return $this->failRespNotFound($message);
        }

        if($exception instanceof ValidationException) {
            $messages = $exception->validator->errors()->all();
            $message = '';
            if(count($messages) > 1) {
                $message = implode(' ', $messages);
            } else if(count($messages) == 1) {
                $message = $messages[0];
            }
            return $this->failRespUnProcess($message);
        }

        if($exception instanceof FileException) {
            return $this->failResponse($exception->getMessage(), 500);
        }

        if($exception instanceof HttpException) {
            return $this->failResponse($exception->getMessage(), $exception->getStatusCode());
        }

        if($exception) {
            return $this->failResponse($exception->getMessage(), 500);
        }

        return parent::render($request, $exception);
    }
}
