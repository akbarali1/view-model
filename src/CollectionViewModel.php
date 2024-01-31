<?php

namespace Akbarali\ViewModel;

use Akbarali\ViewModel\Presenters\ApiResponse;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CollectionViewModel implements ViewModelContract
{
    protected Collection $dataCollection;
    protected string     $viewModel;

    /**
     * @param iterable $dataCollection
     * @param string   $viewModel
     */
    public function __construct(iterable $dataCollection, string $viewModel)
    {
        $this->dataCollection = collect($dataCollection);
        $this->viewModel      = $viewModel;

        $this->dataCollection->transform(function ($value) use ($viewModel) {
            return new $viewModel($value);
        });
    }

    public function toView(string $viewName, array $additionalParams = []): Factory|View|Application
    {
        return view($viewName, array_merge(['items' => $this->dataCollection, 'item' => $additionalParams]));
    }

    public function toJsonApi($toSnakeCase = true): ApiResponse
    {
        if ($toSnakeCase) {
            return ApiResponse::getSuccessResponse($this->toSnakeCase($this->dataCollection));
        }

        return ApiResponse::getSuccessResponse($this->dataCollection);
    }

    /**
     * @param iterable $dataCollection
     * @param string   $viewModel
     * @return CollectionViewModel
     */
    public static function createFromArray(iterable $dataCollection, string $viewModel): CollectionViewModel
    {
        return new static($dataCollection, $viewModel);
    }

    protected function toSnakeCase(iterable $items): array
    {
        $res = [];
        try {
            foreach ($items as $item) {
                $row = [];
                if (is_scalar($item)) {
                    $row = $item;
                } else {
                    if (is_array($item)) {
                        foreach ($item as $itemKey => $itemVal) {
                            $row[Str::snake($itemKey)] = is_iterable($itemVal) ? $this->toSnakeCase($itemVal) : $itemVal;
                        }
                    } else {
                        $class      = new \ReflectionClass($item);
                        $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
                        foreach ($properties as $reflectionProperty) {
                            if ($reflectionProperty->isStatic()) {
                                continue;
                            }
                            $value                                           = $reflectionProperty->getValue($item);
                            $row[Str::snake($reflectionProperty->getName())] = is_iterable($value) ? $this->toSnakeCase($value) : $value;
                        }
                    }
                }
                $res[] = $row;
            }

        } catch (\Exception $exception) {
        }

        return $res;
    }
}

