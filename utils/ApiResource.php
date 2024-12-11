<?php

namespace utils;

use utils\HttpResponses;

class ApiResource
{
    const NO_ERROR = 0;
    const METHOD_ERROR = 1;
    const VERIFICATION_ERROR = 2;
    public int $error = self::NO_ERROR;
    public $resources;
    private $verification;
    public function __construct(
        readonly string|null $method = null,
        callable|null $verification = null,
        array|callable $resources = [],
    ) {
        $this->verification = $verification;
        $this->resources = $resources;
    }

    public function verificar(): int
    {
        if (is_null($this->verification) || call_user_func($this->verification)) {
            if (is_null($this->method) || $this->method === Request::$METHOD) return self::NO_ERROR;
            return self::METHOD_ERROR;
        } else {
            return self::VERIFICATION_ERROR;
        }
    }

    public function sendError()
    {
        if ($this->error === self::METHOD_ERROR) HttpResponses::Method_Not_Allowed("Metodo no permitido");
        elseif ($this->error === self::VERIFICATION_ERROR) HttpResponses::Not_Found("recurso no encontrado");
    }

    public function process(bool $mainProcess = true): int
    {
        $this->error = $this->verificar();
        if ($mainProcess && $this->error !== self::NO_ERROR) $this->sendError();
        if ($this->error === self::NO_ERROR) {
            if (is_array($this->resources)) {
                foreach ($this->resources as $resource) {
                    $this->error = $resource->process(false);
                }
            } else {
                $this->error = call_user_func($this->resources);
            }
        };
        if ($mainProcess && $this->error !== self::NO_ERROR) $this->sendError();
        return $this->error;
    }
}
