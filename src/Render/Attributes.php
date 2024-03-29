<?php
declare(strict_types=1);

namespace Akbarali\ViewModel\Render;

trait Attributes
{

    //class
    public function class(string $string, int $child = 0): static
    {
        $this->documents['class'] = $string;

        return $this;
    }

    //id
    public function id(string $string): static
    {
        $this->documents['id'] = $string;

        return $this;
    }

    // for
    public function for(string $string): static
    {
        $this->documents['for'] = $string;

        return $this;
    }

    //name
    public function name(string $string): static
    {
        $this->documents['name'] = $string;

        return $this;
    }

    //placeholder
    public function placeholder(string $string): static
    {
        $this->documents['placeholder'] = $string;

        return $this;
    }

    //value
    public function value(mixed $string): static
    {
        $this->documents['value'] = $string;

        return $this;
    }

    //type
    public function type(string $string): static
    {
        $this->documents['type'] = $string;

        return $this;
    }

    //required
    public function required(bool $bool): static
    {
        $this->documents['required'] = $bool;

        return $this;
    }

    //checked
    public function checked(bool $bool): static
    {
        $this->documents['checked'] = $bool;

        return $this;
    }

    //selected
    public function selected(bool $bool): static
    {
        $this->documents['selected'] = $bool;

        return $this;
    }

    //multiple
    public function multiple(bool $bool): static
    {
        $this->documents['multiple'] = $bool;

        return $this;
    }

    //text
    public function text(string $string): static
    {
        $this->documents['text'] = $string;

        return $this;
    }

    //autocomplete
    public function autocomplete(string $string): static
    {
        $this->documents['autocomplete'] = $string;

        return $this;
    }

    //autofocus
    public function autofocus(bool $bool): static
    {
        $this->documents['autofocus'] = $bool;

        return $this;
    }

    //notClosed
    public function notClosed(): static
    {
        $this->documents['close'] = false;

        return $this;
    }


}
