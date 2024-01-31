<?php

namespace Akbarali\ViewModel;

use Akbarali\ViewModel\Presenters\ApiResponse;
use Akbarali\DataObject\DataObjectBase;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class BaseViewModel implements ViewModelContract
{
    protected DataObjectBase $_data;

    protected array $_fields = [];

    public function __construct($data)
    {
        $this->_data = $data;
        $this->init();
    }

    abstract protected function populate();

    protected function init(): void
    {
        try {
            $class = new \ReflectionClass(static::class);

            $this->_fields = [];

            foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {

                if ($reflectionProperty->isStatic()) {
                    continue;
                }

                $field = $reflectionProperty->getName();

                $this->_fields[$field] = $reflectionProperty;
            }

            foreach ($this->_fields as $field => $validator) {

                $value = ($this->_data->{$field} ?? $validator->getDefaultValue() ?? null);

                $this->{$field} = $value;
            }
        } catch (\Exception $exception) {

        }

        $this->populate();
    }

    public function toView(string $viewName, array $additionalParams = []): Factory|View|Application
    {
        return view($viewName, array_merge(['item' => $this], $additionalParams));
    }

    /**
     * @throws ExceptionInterface
     */
    public function toJsonApi($toSnakeCase = true): Presenters\ApiResponse
    {
        return ApiResponse::getSuccessResponse($toSnakeCase ? $this->toSnakeCase($this) : $this);
    }

    /**
     * @throws ExceptionInterface
     */
    protected function toSnakeCase($data): array
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());

        return (new Serializer([$normalizer]))->normalize($data);
    }

    /**
     * @param      $array
     * @param      $locale
     * @param bool $json
     * @return string
     */
    protected function trans($array, $locale = null, bool $json = false): string
    {
        if ($json === true) {
            try {
                $array = (array)json_decode($array, false, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                return '';
            }
        }

        if (!$locale) {
            $locale = app()->getLocale();
        }

        return $array[$locale] ?? '';
    }
}
