<?php

namespace Akbarali\ViewModel;

use Akbarali\DataObject\DataObjectCollection;
use Akbarali\ViewModel\Presenters\ApiResponse;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PaginationViewModel implements ViewModelContract
{
    protected DataObjectCollection $dataCollection;
    protected string               $viewModel;

    public LengthAwarePaginator $pagination;

    /**
     * @param DataObjectCollection $dataCollection
     * @param string               $viewModel
     */
    public function __construct(DataObjectCollection $dataCollection, string $viewModel)
    {
        $this->dataCollection = $dataCollection;
        $this->viewModel      = $viewModel;

        $this->dataCollection->items->transform(fn($value) => new $viewModel($value));

        $parameters = (new Request)->getQueryString();
        $parameters = preg_replace('/&page(=[^&]*)?|^page(=[^&]*)?&?/', '', $parameters);
        $path       = url((new Request)->path()).(empty($parameters) ? '' : '?'.$parameters);

        $this->pagination = new LengthAwarePaginator($this->dataCollection->items, $this->dataCollection->totalCount, $this->dataCollection->limit, $this->dataCollection->page);
        $this->pagination->withPath($path);
    }

    public function toView(string $viewName, array $additionalParams = []): Factory|View|Application
    {
        return view($viewName, array_merge(['pagination' => $this->pagination, 'item' => $additionalParams]));
    }

    public function toJsonApi($toSnakeCase = true, array $additionalParams = []): ApiResponse
    {
        if ($toSnakeCase) {
            $data = [
                'items'      => $this->toSnakeCase($this->dataCollection->items),
                'page'       => $this->dataCollection->page,
                'limit'      => $this->dataCollection->limit,
                'totalCount' => $this->dataCollection->totalCount,
            ];
            if (count($additionalParams) > 0) {
                $data = array_merge($data, $additionalParams);
            }

            return ApiResponse::getSuccessResponse($data);
        }

        $data = [
            'items'      => $this->dataCollection->items,
            'page'       => $this->dataCollection->page,
            'limit'      => $this->dataCollection->limit,
            'totalCount' => $this->dataCollection->totalCount,
        ];
        if (count($additionalParams) > 0) {
            $data = array_merge($data, $additionalParams);
        }

        return ApiResponse::getSuccessResponse($data);
    }

    protected function toSnakeCase(iterable $items): array
    {
        $res = [];
        try {
            foreach ($items as $item) {
                $row = [];
                if (is_scalar($item)) {
                    $row = $item;
                } elseif (is_array($item)) {
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
                $res[] = $row;
            }

        } catch (\Exception $exception) {
        }

        return $res;
    }
}

