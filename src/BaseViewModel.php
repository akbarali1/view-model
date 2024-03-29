<?php

namespace Akbarali\ViewModel;

use Akbarali\ViewModel\Presenters\ApiResponse;
use Akbarali\DataObject\DataObjectBase;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class BaseViewModel implements ViewModelContract
{
    protected array $_fields = [];

    public function __construct(
        protected DataObjectBase $_data
    ) {
        if (!($this->_data instanceof EmptyData)) {
            $this->init();
        }
    }

    abstract protected function populate();

    protected function init(): void
    {
        try {
            $this->_fields = DOCache::resolve(static::class, static function () {
                $class  = new \ReflectionClass(static::class);
                $fields = [];
                foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
                    if ($reflectionProperty->isStatic()) {
                        continue;
                    }
                    $field          = $reflectionProperty->getName();
                    $fields[$field] = $reflectionProperty;
                }

                return $fields;
            });

            foreach ($this->_fields as $field => $validator) {
                $value          = ($this->_data->{$field} ?? $validator->getDefaultValue() ?? null);
                $this->{$field} = $value;
            }
        } catch (\Exception $exception) {

        }

        $this->populate();
    }

    public function toView(string $viewName, ...$args): Factory|View|Application
    {
        return view($viewName, array_merge(['item' => $this], $args[0] ?? []));
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
     * @param string|array $array
     * @param ?string      $locale
     * @return string
     */
    protected function trans(string|array $array, ?string $locale = null): string
    {
        if (is_string($array)) {
            try {
                $array = (array)json_decode($array, false, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                return '';
            }
        }

        if (!$locale) {
            $locale = App::getLocale();
        }

        return $array[$locale] ?? '';
    }

    public static function createEmpty(): static
    {
        return new static(new EmptyData());
    }
}
