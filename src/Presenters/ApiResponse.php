<?php

namespace Akbarali\ViewModel\Presenters;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\JsonResponse;

class ApiResponse implements Jsonable
{
    /**
     * @var bool
     */
    public bool $success;
    /**
     * @var int
     */
    public int $errorCode = 0;
    /**
     * @var string
     */
    public mixed $errorMessage = null;
    /** @var mixed|null */
    public array $errors = [];
    /**
     * @var mixed
     */
    public mixed $data = null;

    /**
     * @param string $errorMessage
     * @param int    $errorCode
     * @return ApiResponse
     */
    public static function getErrorResponse(string $errorMessage, int $errorCode = -1): ApiResponse
    {
        return (new self())->setAsError($errorMessage, $errorCode);
    }

    /**
     * @param mixed $data
     * @return ApiResponse
     */
    public static function getSuccessResponse(mixed $data): ApiResponse
    {
        $instance          = new self();
        $instance->success = true;
        $instance->data    = $data;

        return $instance;
    }

    public static function returnJsonErrorResponse(string $errorMessage, int $errorCode = -1, array $errors = []): JsonResponse
    {
        return (new self())->setAsError($errorMessage, $errorCode, $errors)->returnJson();
    }

    /**
     * @return JsonResponse
     */
    public function returnJson(): JsonResponse
    {
        return response()->json($this);
    }

    /**
     * @param string $errorMessage
     * @param int    $errorCode
     * @return $this
     */
    public function setAsError(string $errorMessage, int $errorCode = -1, array $errors = []): static
    {
        $this->success      = false;
        $this->errorCode    = $errorCode;
        $this->errorMessage = $errorMessage;
        if (count($errors) > 0) {
            $this->errors = $errors;
        }

        return $this;
    }

    public function toJson($options = 0): bool|string
    {
        if ($this->success) {
            return json_encode([
                "success" => $this->success,
                "data"    => $this->data,
            ]);
        }

        if (count($this->errors) === 0) {
            return json_encode([
                "success" => $this->success,
                "error"   => [
                    "code"    => $this->errorCode,
                    "message" => $this->errorMessage,
                ],
            ]);
        }

        return json_encode([
            "success" => $this->success,
            "error"   => [
                "code"    => $this->errorCode,
                "message" => $this->errorMessage,
                "errors"  => $this->errors,
            ],
        ]);
    }
}
