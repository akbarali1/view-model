<?php
declare(strict_types=1);

namespace Akbarali\ViewModel;

use Akbarali\ViewModel\Presenters\ApiResponse;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

interface ViewModelContract
{
    public function toView(string $viewName, array $additionalParams = []): Factory|View|Application;

    public function toJsonApi(): ApiResponse;
}
