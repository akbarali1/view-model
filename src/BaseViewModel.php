<?php

namespace Akbarali\ViewModel;

use Akbarali\ViewModel\Presenters\ApiResponse;
use Akbarali\DataObject\DataObjectBase;
use App\DataObjects\Partner\PotentialPartnerData;
use App\ViewModels\Partner\PotentialPartnerViewModel;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    public static function fromDataObject(DataObjectBase $data): static
    {
        return new static($data);
    }

    /**
     * @param Builder $query
     * @param string  $methodName
     * @param array   $with
     * @param int     $chunkSize
     * @return StreamedResponse
     * @throws ViewModelException
     */
    public static function toCsv(Builder $query, string $methodName, array $with = [], int $chunkSize = 500): StreamedResponse
    {
        $tableName  = $query->getModel()->getTable();
        $csvData    = self::checkCSVData($methodName);
        $dataObject = new $csvData['dataObject'];
        $columns    = $csvData['columns'];
        $fields     = $csvData['fields'];
        $viewModel  = static::class;
        $headers    = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$tableName}_".Carbon::now()->format('dmY_His').".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        if (count($with) > 0) {
            $query->with($with);
        }

        $callback = function () use ($query, $columns, $fields, $viewModel, $dataObject, $chunkSize) {
            $file = fopen('php://output', 'wb');
            fwrite($file, $bom = chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns, ';');
            $query->chunk($chunkSize, function (Collection $items) use (&$file, $bom, $fields, $viewModel, $dataObject) {
                foreach ($items as $item) {
                    fwrite($file, $bom);
                    $vMod = new $viewModel($dataObject::fromModel($item));
                    $arr  = [];
                    foreach ($fields as $field) {
                        $arr[] = ($field instanceof \Closure) ? $field($vMod) : $vMod->{$field};
                    }

                    fputcsv($file, $arr, ';');
                }
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * @param string $methodName
     * @return array
     * @throws ViewModelException
     */
    private static function checkCSVData(string $methodName): array
    {
        //csvFields method exists in the child class
        if (!method_exists(static::class, $methodName)) {
            throw new ViewModelException('Method not found', ViewModelException::METHOD_NOT_FOUND);
        }

        $csvData = static::$methodName();

        //Columns
        if (!isset($csvData['columns'])) {
            throw new ViewModelException('Columns not found', ViewModelException::METHOD_NOT_FOUND);
        }

        if (!is_array($csvData['columns'])) {
            throw new ViewModelException('Columns must be array', ViewModelException::COLUMN_NOT_ARRAY);
        }

        if (count($csvData['columns']) === 0) {
            throw new ViewModelException('Columns count is 0', ViewModelException::COLUMN_ZERO);
        }

        //Fields
        if (!isset($csvData['fields'])) {
            throw new ViewModelException('Fields not found', ViewModelException::METHOD_NOT_FOUND);
        }

        if (!is_array($csvData['fields'])) {
            throw new ViewModelException('Fields must be array', ViewModelException::FIELD_NOT_ARRAY);
        }

        if (count($csvData['fields']) === 0) {
            throw new ViewModelException('Fields count is 0', ViewModelException::FIELD_ZERO);
        }

        if (count($csvData['columns']) !== count($csvData['fields'])) {
            throw new ViewModelException('Columns and fields count not equal', ViewModelException::COLUMNS_FIELDS_NOT_EQUAL);
        }

        //DataObject
        if (!isset($csvData['dataObject'])) {
            throw new ViewModelException('DataObject not found', ViewModelException::DATA_OBJECT_NOT_FOUND);
        }

        if (!(new $csvData['dataObject'] instanceof DataObjectBase)) {
            throw new ViewModelException('DataObject must be instance of DataObjectBase', ViewModelException::DATA_OBJECT_NOT_INSTANCE);
        }

        return $csvData;
    }
}
