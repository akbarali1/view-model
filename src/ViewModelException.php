<?php
declare(strict_types=1);

namespace Akbarali\ViewModel;

use Throwable;

class ViewModelException extends \Exception
{


    /**
     * OperationException constructor.
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public const        METHOD_NOT_FOUND = -404;
    public const        COLUMN_NOT_ARRAY = -1000;
    public const        COLUMN_ZERO      = -1001;

    public const FIELD_NOT_ARRAY          = -2000;
    public const FIELD_ZERO               = -2001;
    public const COLUMNS_FIELDS_NOT_EQUAL = -3000;
    public const DATA_OBJECT_NOT_FOUND    = -4000;
    public const DATA_OBJECT_NOT_INSTANCE = -4001;


}
