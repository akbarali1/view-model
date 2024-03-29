<?php
declare(strict_types=1);

namespace Akbarali\ViewModel\Render;

class FormBuilder
{
    use Attributes;

    public function __construct()
    {
        $this->documents['close'] = true;
    }

    protected array $documents = [];

    public function addTag(string $string, \Closure $param): static
    {
        $this->documents[][$string] = $param->call($this, new static());

        return $this;
    }

    //addChildren
    public function addChildren(string $string, \Closure $param): static
    {
        $this->documents['children'][][$string] = $param->call($this, new static());

        return $this;

    }

    //getForm
    public function getForm()
    {
        return $this->documents;
    }

    //render
    public function render()
    {
        return view('view-models.forms', ['tags' => $this->documents]);
    }

}
