<?php

namespace Akbarali\ViewModel\Presenters;

use Illuminate\Contracts\Support\Jsonable;
use Ramsey\Uuid\Uuid;

class JsonRpcResponse implements Jsonable
{
    public const JSON_RPC_INVALID_FORMAT = -32600;

    public string  $id;
    public mixed   $errorMessage = null;
    public int     $errorCode    = 0;
    public mixed   $data         = null;
    protected bool $success      = true;

    public static function getErrorResponse(string $id, string $errorMessage, int $errorCode = -32600): JsonRpcResponse
    {
        return (new self())->setAsError($id, $errorMessage, $errorCode);
    }

    public static function getSuccessResponse(string $id, mixed $data): JsonRpcResponse
    {
        $instance       = new self();
        $instance->id   = $id;
        $instance->data = $data;

        return $instance;
    }

    public function setAsError(string $id, string $errorMessage, int $errorCode = -32600): static
    {
        $this->success      = false;
        $this->errorMessage = $errorMessage;
        $this->errorCode    = $errorCode;

        return $this;
    }

    protected function getId(): string
    {
        return empty($this->id) ? Uuid::uuid4()->getHex()->toString() : $this->id;
    }

    public function toJson($options = 0): bool|string
    {
        if ($this->success) {
            return json_encode([
                "jsonrpc" => "2.0",
                "id"      => $this->getId(),
                "result"  => $this->data,
            ]);
        }

        return json_encode([
            "jsonrpc" => "2.0",
            "id"      => $this->getId(),
            "error"   => [
                "message" => $this->errorMessage,
                "code"    => $this->errorCode,
            ],
        ]);
    }
}
