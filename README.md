Dokumentatsiya endi yoziladi

```
composer require akbarali/view-model
```

# Use Pagination

1. Pagination

```php
   public function paginate(int $page = 1, $limit = 25, ?iterable $filters = null): DataObjectCollection
    {
        $model = OrganizationModel::applyEloquentFilters($filters)->latest();
        $model->select('organizations.*');

        $totalCount = $model->count();
        $skip       = $limit * ($page - 1);
        $items      = $model->skip($skip)->take($limit)->get();
        $items->transform(fn(OrganizationModel $value) => OrganizationData::createFromEloquentModel($value));

        return new DataObjectCollection($items, $totalCount, $limit, $page);
    }
```

2. OrganizationData

```php
class OrganizationData extends \Akbarali\DataObject\DataObjectBase
{
    public readonly ?int $id;
    public ?string      $name;
    public ?int         $inn;
    public ?int         $pinfl;
    public ?string      $description;
    public int          $is_type;    // 1 - Qora ro`yhatdagilar, 2 -  Oq ro`yhatdagilar
    public Carbon       $created_at; // Yaratilgan vaqti
}
```

3. Pagionation View model

```php
    public function index(string $type, Request $request): View
    {
        $filters = collect();
        $filters->push(OrganizationTypeFilter::getFilter($type));
        $dataObjectPagination = $this->paginate((int)$request->input('page', 1), 20, $filters);

        return (new PaginationViewModel($dataObjectPagination, OrganizationViewModel::class))->toView('organization.index', compact('type'));
    }
```

4. OrganizationViewModel

```php
class OrganizationViewModel extends \Akbarali\ViewModel\BaseViewModel
{
    public ?int     $id;
    public ?string  $name;
    public ?int     $inn;
    public ?int    $pinfl;
    public ?string $description;
    public int     $is_type;
    public ?string $created_at;
    public ?string $hDate;

    protected DataObjectBase|OrganizationData $_data;

    public function populate(): void
    {
        $this->hType = $this->getHType();
        $this->hDate = $this->_data->created_at->format('d.m.Y H:i');

    }

    public function getHType(): string
    {
        return match ($this->is_type) {
            1       => "Qora ro`yhatdagilar",
            2       => "Oq ro`yhatdagilar",
            default => "Noma`lum",
        };
    }

}
```

# First View Model

1. Organization get database

```php
public function getOrganization(int $id): OrganizationData
{
    /** @var OrganizationModel $item */
    $item = OrganizationModel::query()->find($id);
    if (!$item) {
        throw new OperationException("Organization not found");
    }
     return OrganizationData::createFromEloquentModel($item);
}
```

2. Find Organization

```php
public function edit(string $type, int $id): View
{
    $orgData   = $this->getOrganization($id);
    $viewModel = new OrganizationViewModel($orgData);

     return $viewModel->toView('organization.store', compact('type'));
}
```

# 1.7 version add viewmodel create empty

```php
$viewModel = OrganizationViewModel::createEmpty();

return $viewModel->toView('organization.store');
```

# 1.8 version add `fromDataObject` method

```php
$viewModel = OrganizationViewModel::fromDataObject($orgData);
```
# 1.9 version add `toCsv` method

```php
class PartnerData extends \Akbarali\DataObject\DataObjectBase
{
    public readonly int $id;
    public readonly int $agentId;
    public string       $fullName;
    public string       $phone;
    public ?string      $address;
    public ?string      $description;
    public Carbon       $createdAt;
}

class PartnerViewModel extends \Akbarali\ViewModel\BaseViewModel
{
    public ?int    $id;
    public ?string $fullName;
    public ?string $phone;
    public ?string $hPhone;
    public ?string $address;
    public ?string $description;
    public ?string $hDate;
    public ?string $agentName;

    protected DataObjectBase|PartnerData $_data;

    protected function populate(): void
    {
        $this->hDate     = $this->_data->createdAt->format('d.m.Y H:i');
        $this->agentName = $this->_data->agent->full_name ?? '';
        $phone           = (new Phone($this->_data->phone));
        $this->hPhone    = $phone->getFormatted();
    }

    protected static function csvData(): array
    {
        return [
            'dataObject' => PartnerData::class,
            'columns'    => [
                "ID",
                trans('all.full_name'),
                trans('all.phone'),
                trans('all.agent'),
                trans('all.address'),
                trans('all.description'),
                trans('all.created_at'),
                'Time UTC',
            ],
            'fields'     => [
                'id',
                'fullName',
                'hPhone',
                'agentName',
                'address',
                'description',
                'hDate',
                fn($item) => $this->_data->createdAt->format('Y-m-d H:i:s'),
            ],
        ];
    }
}

final class PotentialPartnerController extends Controller
{
    public function index(Request $request)
    {
        $filters = collect();
        $filters->push(DateFilter::getDateRangeFilter());
        $filters->push(AgentFilter::getFilterAgentId($request->user()));
        if ($request->get('filter') === 'export') {
          return PartnerViewModel::toCsv($this->getExportQueryPartner($filters), 'csvData');
        }
    }
    
    public function getExportQueryPartner($filters): Builder
    {
        $model = PartnerModel::applyEloquentFilters($filters)->latest();
        $model->select('partners.*');
        return $model;
    }
}
```